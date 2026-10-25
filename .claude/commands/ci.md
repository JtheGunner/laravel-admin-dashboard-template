---
description: Run the full local CI gate (lint, format, types, PHPStan, tests) and summarise failures.
---

Run the project's full local CI gate and report the result.

```bash
composer ci:check
```

This runs, in order: `npm run lint:check` (ESLint), `npm run format:check` (Prettier),
`npm run types:check` (vue-tsc), `composer analyse` (Larastan/PHPStan level 5), and `php artisan test`.

Summarise concisely: which stage failed (if any), the key error lines, and the exact command to
reproduce that one stage. If everything passes, say so in a single line. Do not fix anything unless
the user asks.
