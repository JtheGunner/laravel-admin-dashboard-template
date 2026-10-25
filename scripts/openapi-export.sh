#!/usr/bin/env bash
#
# Generates docs/openapi.json deterministically against a freshly migrated
# sqlite DB (see with-temp-sqlite.sh) — independent of the local MariaDB and the
# shared production DB. Scramble reads the DB schema (information_schema) for
# type inference.
#
# Usage:  bash scripts/openapi-export.sh   (or: npm run openapi:export)
set -euo pipefail

exec bash "$(dirname "$0")/with-temp-sqlite.sh" php artisan scramble:export
