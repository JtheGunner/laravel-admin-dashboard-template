#!/usr/bin/env bash
#
# Runs a command against a throwaway, freshly migrated sqlite database.
#
# Tools that introspect the DB schema (Scramble, ide-helper) must not depend on
# the local MariaDB or the shared production DB: an unreachable or hijacked
# port (e.g. something else listening on 3306) makes them hang forever. sqlite +
# migrate yields the same schema locally and in CI, so output stays deterministic.
#
# Usage:  bash scripts/with-temp-sqlite.sh <command> [args...]
set -euo pipefail

cd "$(dirname "$0")/.."

DB_FILE="$(pwd)/database/temp-schema.$$.sqlite"
: > "$DB_FILE"
trap 'rm -f "$DB_FILE"' EXIT

export DB_CONNECTION=sqlite
export DB_DATABASE="$DB_FILE"

php artisan migrate --force --no-interaction >/dev/null
"$@"
