#!/bin/sh
#
# Claude Code PostToolUse hook — auto-format the file that was just edited.
#
#   *.php                             -> Laravel Pint
#   *.ts *.tsx *.vue *.js *.mjs *.jsx -> Prettier (if under resources/) + ESLint --fix
#   *.css                             -> Prettier (if under resources/)
#
# The project splits formatting (Prettier, scoped to resources/) from linting
# (ESLint, repo-wide); the CI gate checks both, so the hook runs both.
#
# Reads the hook payload as JSON on stdin and formats only the single file in
# `.tool_input.file_path`. Non-blocking by design: it never fails the tool call
# (always exits 0) and stays silent on success, so it can't derail the agent.
#
# Matches the project's CI gate (`composer ci:check` -> pint --test + eslint),
# giving the agent instant style compliance instead of a late CI failure.

set -u

input="$(cat)"
file="$(printf '%s' "$input" | jq -r '.tool_input.file_path // empty' 2>/dev/null)"
[ -n "$file" ] || exit 0
[ -f "$file" ] || exit 0

# Repo root (this script lives in <root>/scripts/).
root="$(cd "$(dirname "$0")/.." && pwd)"

# Never format files outside the repository.
case "$file" in
    "$root"/*) ;;      # absolute path inside the repo
    /*) exit 0 ;;      # absolute path elsewhere -> skip
    *) ;;              # relative path (CWD is the project root) -> allow
esac

case "$file" in
    *.php)
        php_bin="$("$root/scripts/resolve-php84.sh" 2>/dev/null)" || exit 0
        [ -x "$root/vendor/bin/pint" ] && \
            "$php_bin" "$root/vendor/bin/pint" "$file" >/dev/null 2>&1
        ;;
    *.ts|*.tsx|*.vue|*.js|*.mjs|*.jsx|*.css)
        # Prettier only governs resources/ in this project (`format:check`); keep
        # it scoped there so generated/root files (openapi.json, composer.json) are
        # never touched. ESLint (`eslint .`) applies repo-wide to JS-family files.
        case "$file" in
            "$root"/resources/*|resources/*)
                [ -x "$root/node_modules/.bin/prettier" ] && \
                    "$root/node_modules/.bin/prettier" --write "$file" >/dev/null 2>&1
                ;;
        esac
        case "$file" in
            *.css) ;;   # CSS: Prettier only, no ESLint
            *)
                [ -x "$root/node_modules/.bin/eslint" ] && \
                    "$root/node_modules/.bin/eslint" --fix "$file" >/dev/null 2>&1
                ;;
        esac
        ;;
esac

exit 0
