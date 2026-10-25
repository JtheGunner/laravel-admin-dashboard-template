---
description: Run PHPStan (Larastan) static analysis and summarise findings.
---

Run static analysis and report findings.

```bash
composer analyse
```

Larastan runs at level 5 (see `phpstan.neon`; `_ide_helper_models.php` is registered via `scanFiles`).
Group new findings by file with the rule/message. Prefer a real fix — typed relations/pivots,
`@param` narrowing, resource `@mixin` — over `@phpstan-ignore` or new `phpstan-baseline.neon` entries.
Only change code if the user asks.
