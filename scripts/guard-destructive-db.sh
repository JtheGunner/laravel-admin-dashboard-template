#!/bin/sh
#
# Claude Code PreToolUse (Bash) guard — hard-block destructive database commands
# that would wipe or rewrite a shared database. Defense-in-depth on top of
# the `permissions.deny` rules in .claude/settings.json: this catches any PHP
# binary or path (not just a literal `php artisan …`), e.g.
# `/opt/homebrew/opt/php@8.4/bin/php artisan migrate:fresh`.
#
# Blocks: migrate:fresh, migrate:reset, migrate:refresh, db:wipe.
# Exit 2 = block the tool call; the message on stderr is returned to the agent.
#
# It requires an actual `artisan <subcommand>` invocation, so harmless mentions
# (a commit message, a grep) are never blocked.

set -u

cmd="$(cat | jq -r '.tool_input.command // empty' 2>/dev/null)"
[ -n "$cmd" ] || exit 0

if printf '%s' "$cmd" | grep -Eq 'artisan[[:space:]]+(migrate:(fresh|reset|refresh)|db:wipe)([^A-Za-z0-9:]|$)'; then
    echo "BLOCKED: destructive database command detected (migrate:fresh / migrate:reset / migrate:refresh / db:wipe)." >&2
    echo "Destructive database commands are blocked in this project (see AGENTS.md, 'Database')." >&2
    echo "Refusing to run: $cmd" >&2
    exit 2
fi

exit 0
