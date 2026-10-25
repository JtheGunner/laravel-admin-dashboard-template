#!/bin/sh
#
# Prints the path to a PHP >= 8.4 binary on stdout and exits 0.
# Exits 1 with no output when none is found — the caller decides how to react.
#
# Single source of truth for PHP resolution, shared by:
#   - .githooks/pre-commit          (IDE-helper + OpenAPI generation)
#   - scripts/claude-format-hook.sh (Claude Code format-on-edit hook)
#
# Rationale: a system `php` is often 8.3 and makes composer's platform check
# fatal for this project (requires >= 8.4). Prefer a suitable `php` already on
# PATH, else fall back to the common Homebrew locations.

if php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' 2>/dev/null; then
    command -v php
elif [ -x /opt/homebrew/opt/php@8.4/bin/php ]; then
    echo /opt/homebrew/opt/php@8.4/bin/php
elif [ -x /usr/local/opt/php@8.4/bin/php ]; then
    echo /usr/local/opt/php@8.4/bin/php
else
    exit 1
fi
