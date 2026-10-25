# syntax=docker/dockerfile:1
# Multi-stage build for Laravel + Inertia/Vue.
# Stage 1 builds Composer deps AND the frontend assets (PHP is required because the
# Vite Wayfinder plugin calls `php artisan`). Stage 2 is the slim runtime image.

############################################
# Stage 1 – build (PHP + Node together)
############################################
FROM php:8.4-cli-bookworm AS build

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip curl ca-certificates gnupg \
 && install-php-extensions pdo_mysql bcmath intl zip gd exif pcntl opcache ldap @composer \
 && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
 && apt-get install -y --no-install-recommends nodejs \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Composer deps first (layer cache); no scripts/autoloader yet since app code is missing.
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader

# Node deps (layer cache).
COPY package.json package-lock.json ./
RUN npm ci

# App code.
COPY . .

# Build-time env only for asset building (so `php artisan` can boot during `vite build`).
# Throwaway key – the real config comes from k8s env at runtime. The .env is removed
# afterwards, so it does NOT end up in the image.
RUN cp .env.example .env \
 && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
 && php artisan key:generate \
 && php artisan storage:link --force \
 && npm run build \
 && rm -f .env \
 && rm -rf node_modules

############################################
# Stage 2 – runtime (php artisan serve)
############################################
FROM php:8.4-cli-bookworm AS runtime

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql bcmath intl zip gd exif pcntl opcache ldap

# Ghostscript: used to compress uploaded certificate PDFs (App\Services\Media\PdfCompressor).
# Without it the compression silently no-ops (upload still succeeds, but no compressed sibling).
# Supervisor: runs the web server, the Laravel scheduler and a queue worker side by side (see docker/supervisord.conf).
RUN apt-get update \
 && apt-get install -y --no-install-recommends ghostscript supervisor \
 && rm -rf /var/lib/apt/lists/*

# The base image ships no php.ini, so PHP falls back to the 2M/8M upload defaults
# and a 128M memory_limit. Raise uploads so file uploads (e.g. Zeugnis-PDFs, app cap
# 10 MB) go through, and raise memory_limit so the Gmail IMAP sync has headroom when
# parsing large mail bodies.
RUN { \
      echo 'upload_max_filesize = 64M'; \
      echo 'post_max_size = 64M'; \
      echo 'memory_limit = 2048M'; \
    } > /usr/local/etc/php/conf.d/zz-uploads.ini \
 && { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=256'; \
      echo 'opcache.interned_strings_buffer=16'; \
      echo 'opcache.max_accelerated_files=20000'; \
      echo 'opcache.validate_timestamps=0'; \
      echo 'opcache.save_comments=1'; \
      echo 'opcache.huge_code_pages=1'; \
    } > /usr/local/etc/php/conf.d/zz-opcache.ini

WORKDIR /app

# Finished app incl. vendor/ and public/build (without node_modules / .env) from the build stage.
COPY --from=build --chown=www-data:www-data /app /app
RUN chmod -R 775 storage bootstrap/cache

# Process manager config: web server + Laravel scheduler.
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf

# Startup script: warms config/route caches with the real runtime env, then execs supervisord.
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# APP_ENV as a real env -> bootstrap/app.php skips host detection; the rest of the
# config (DB, APP_KEY, ...) comes from k8s env at runtime. PHP_CLI_SERVER_WORKERS
# gives the built-in server some parallelism.
ENV APP_ENV=production \
    PHP_CLI_SERVER_WORKERS=4

USER www-data
EXPOSE 8081

# IMPORTANT: no `migrate` at build time – run migrations against the target DB on deploy.
# supervisord runs `php artisan serve` (web), `php artisan schedule:work` (the
# every-minute Laravel scheduler) and `php artisan queue:work` (the queued-job worker,
# needs the jobs/failed_jobs tables) together; see docker/supervisord.conf.
CMD ["/entrypoint.sh"]
