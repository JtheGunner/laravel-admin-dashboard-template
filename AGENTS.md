# AGENTS.md

This file provides guidance to AI coding agents (Claude Code and any tool that reads `AGENTS.md`)
when working with code in this repository. `CLAUDE.md` is a symlink to this file — there is a single
source of truth, so a rule written here applies to every agent regardless of which filename it loads.

## What this is

A generic admin-dashboard template (Laravel 13 + Inertia 3 + Vue 3, PHP 8.4). The demo domain is a
small shop — **customers, products, categories, orders, activity log, users** — chosen only to
show the reusable building blocks: every table flavour (searchable/sortable, faceted filters, tree,
drag-and-drop sortable, soft-delete trash, read-only log, expandable rows), a detail page, forms
with every field type, a wizard, charts on the dashboard, Fortify auth with 2FA and passkeys, a
read-only REST API with generated OpenAPI docs, and Telescope for debugging.

Replace the demo domain with your own; keep the shared engines.

## Project conventions

- **Confirm package version before using its API.** PHP: `composer show --direct` (or `composer show vendor/package`); JS: check `package.json`. Never assume a version.
- **Activate the relevant domain skill** before working in that domain (e.g. `inertia-vue-development` when touching Vue pages, `laravel-specialist` for complex Laravel questions). Do not wait until stuck.
- **Use descriptive names.** `isRegisteredForDiscounts`, not `discount()`. Names should read as statements of fact.
- **Use `php artisan make:` to create all new files** — `php artisan make:class` for generic PHP classes. Pass `--no-interaction` to all Artisan commands. When creating a new model, also create a factory and seeder.
- **Do not create new top-level directories** without explicit approval.
- **Do not change application dependencies** (Composer or npm packages) without explicit approval.
- **Do not create documentation files** (`.md`, READMEs) unless explicitly requested.
- **Always use fully-qualified function calls for PHP built-in functions.** Every call to a PHP
  global/built-in function (e.g. `array_key_exists`, `strlen`, `in_array`, `count`, `implode`,
  `array_map`, `preg_match`, `sprintf`, …) must be written with a leading backslash (`\array_key_exists(…)`,
  `\in_array(…)`, etc.) **or** — preferred — declared via a `use function` import at the top of the file:

  ```php
  use function array_key_exists;
  use function in_array;
  ```

  This prevents PHP from performing a slow namespace-lookup fallback and makes the call site
  unambiguous. Never rely on an unqualified global function call inside a namespaced class.
- **Always import classes and interfaces explicitly.** Every class, interface, trait, or enum
  referenced in a file must be brought into scope with a `use` statement at the top — even when it
  lives in the same namespace. Do not use inline fully-qualified class names (`new \App\Models\Foo`)
  inside method bodies; always prefer `use App\Models\Foo;` at the top and then `new Foo()` at the
  call site. The only accepted exception is inside docblock `@var`/`@param`/`@return` annotations
  where the IDE helper requires it.

## Core principle: no code duplication (DRY)

**Avoiding code duplication is a hard requirement, not a nice-to-have — this applies to PHP, Vue,
TypeScript, config, and tests alike.** When the same logic, markup, validation, query, type, or
constant would appear in more than one place, extract it to a single shared home instead of copying
it: a base class or trait, a shared service, a helper/composable, a reusable Vue component, a shared
type, or a config value. **Prefer the extra effort of a good abstraction over a quick copy-paste** —
if doing it right means adding a base class, refactoring a caller, or generalising an existing
component, do that rather than duplicating.

Concretely, before writing new code:

- **Search first.** Look for existing code that already does (most of) what you need and reuse or
  extend it. The whole app is built on shared engines — `ResourceController` and `ResourceIndex.vue`
  exist precisely so sections don't re-implement CRUD. Fit new work into these seams instead of
  writing a parallel implementation.
- **Extract on the second occurrence.** The moment a block would exist in two places, factor it out.
  Don't wait for a third copy.
- **When you spot existing duplication near your change, consolidate it** rather than adding a third
  copy — leave the surrounding code less duplicated than you found it.

If a shared abstraction genuinely doesn't fit and duplication is the lesser evil, that is a
deliberate decision — call it out explicitly (in the PR/commit body or a code comment) with the
reason, rather than duplicating silently.

## Commands

```bash
composer dev          # everything at once: php serve + queue + pail logs + vite (HMR)
npm run dev           # Vite HMR only (port 5174 in dev container, else default)
npm run build         # production assets

php artisan test                                  # run PHP test suite (sqlite :memory:)
php artisan test --filter=CustomerCrudTest   # single test class/method
vendor/bin/pint --parallel                        # PHP code style (autofix)
vendor/bin/pint --dirty                           # autofix changed files only (faster for single-file edits)
vendor/bin/pint --parallel --test                 # PHP style check only

composer analyse                                  # Larastan/PHPStan static analysis (level 5)
composer analyse:baseline                         # regenerate phpstan-baseline.neon after clearing debt

npm run lint          # eslint --fix
npm run types:check   # vue-tsc --noEmit (typecheck .vue + .ts)
npm run format        # prettier --write resources/

npm run openapi:export   # regenerate docs/openapi.json from code (Scramble) — commit the result
npm run openapi:lint     # validate docs/openapi.json (Spectral)

composer ci:check     # full gate: eslint + prettier + vue-tsc + phpstan + pint + phpunit
```

`composer test` runs `pint --test` before `artisan test`, so a style violation fails the suite.

> PHP: the project requires **PHP ≥ 8.4**. A system `php` is often 8.3 and will make composer's
> platform check fatal. If `php` on PATH is too old, run artisan/tests via
> `/opt/homebrew/opt/php@8.4/bin/php` (the `.githooks/pre-commit` hook resolves an 8.4 binary the
> same way).

Static analysis is **Larastan** (PHPStan for Laravel) at `level: 5`, configured in `phpstan.neon`.
The generated `_ide_helper_models.php` is registered via `scanFiles` so model `@mixin IdeHelperXxx`
docblocks (and DB column types) resolve — keep it regenerated (`php artisan ide-helper:models -M`,
run automatically by the `.githooks/pre-commit` hook alongside `ide-helper:generate` / `ide-helper:meta`
and the OpenAPI export). **Use `-M` (models with `@mixin`), not `-N`** — the wrong flag floods PHPStan
with false "undefined method" errors. `phpstan-baseline.neon` is down to a handful of intentionally-kept
findings (encrypted-cast `DecryptException` catches PHPStan can't see as throwing, plus dormant
passkey/2FA code); each is explained in `phpstan.neon`. **Prefer a real fix** (typed relations/pivots,
`@param` narrowing, resource `@mixin`, adding a `default` throw arm to an incomplete `match`) **over
`@phpstan-ignore`, inline `/** @var */` casts, or new baseline entries.** Extending the baseline is a
last resort reserved for errors that are genuinely unfixable (e.g. a false positive from a package
PHPStan cannot analyse) — and even then, document the reason in a comment inside `phpstan-baseline.neon`.

CI (`.github/workflows/ci.yml`) runs four gate jobs on every pull request against `main` and again
on push to `main`, where they gate the image build: **`openapi`** (regenerate
the spec, fail on drift vs. committed `docs/openapi.json`, then Spectral-lint it), **`phpstan`**,
**`test`** (PHPUnit against sqlite `:memory:`), and **`frontend`** (vue-tsc + ESLint).
So: on any API/schema change, run `npm run openapi:export` and commit `docs/openapi.json`, or the PR
checks fail.

## Database

Migrations are the way to evolve the schema: write a proper additive migration and run `migrate`.
**Never run `migrate:fresh` / `migrate:reset` / `db:wipe`** against a database you care about
(a `.claude/settings.json` deny rule and `scripts/guard-destructive-db.sh` block them for agents).
For a throwaway local database use a fresh sqlite file and `php artisan migrate --seed`. Tests use
sqlite `:memory:` and are safe.

## Architecture: generic CRUD

Almost every "section" (skills, projects, languages, companies, genders, …) is a thin subclass of a
shared CRUD engine. To understand or add a section, read these four files first:

- `app/Http/Controllers/ResourceController.php` — abstract base. Implements `index` (paginated +
  searchable + server-side sorted), `store`/`update`/`destroy`, `reorder`, soft-delete flows
  (`restore`/`forceDestroy` + trashed queries), and bulk actions (`bulkDestroy`/`bulkRestore`/
  `bulkForceDestroy`). Subclasses override `modelClass()`, `page()`, `singularLabel()`,
  `searchableColumns()`, `rules()`, and optional hooks — including `defaultOrderColumn`,
  `eagerLoad`, `transformRow`, `mutateData`, `extraProps`, `baseQuery`, `afterStore`/`afterUpdate`,
  `sortableColumns`/`sortExpressions` + `orderByRelated` (server-side sorting; computed/related
  columns sort via correlated subqueries), `fileColumns`/`fileDisk`/`afterFilePersisted` (uploads via
  `persistFiles`), and `usesSoftDeletes`.
- `app/Http/Controllers/UserScopedResourceController.php` — extends the base, constrains `baseQuery()`
  to `Auth::id()` and injects `user_id` on create. **Security relies on this**: because update/destroy
  resolve the record through `baseQuery()`, scoping there automatically blocks cross-user access. Use it for
  any data that belongs to a single user.
- `resources/js/components/resource/ResourceIndex.vue` — the table, search, pagination, drag-&-drop,
  column-visibility menu, sort control, card fallback, and create/edit dialog. Pages just hand it
  `columns` + `fields` config.
- `resources/js/components/resource/ResourceFormFields.vue` — renders form inputs from a `FieldConfig[]`
  declaration (`FieldConfig` type in `resources/js/types/resource.ts`). Supported `type`s: `text`,
  `textarea`, `number`, `date`, `select`, `multiselect`, `checkbox`, `range` (slider), `file`/`image`,
  `icon` (`IconPickerField`), `color` (`ColorPickerField`), `richtext` (Tiptap `RichTextField`),
  `htmlOrText` (`HtmlOrTextField`), `repeater` (`RepeaterField`), and `section` (layout divider).

A typical Inertia page (`resources/js/pages/<section>/Index.vue`) is pure declaration: it defines a
`columns` array and a `fields` config, then renders `<ResourceIndex>`. See `user-skills/Index.vue`
paired with `UserSkillController.php` as the canonical example.

**Adding a section:** create the model, a controller extending `ResourceController` (or
`UserScopedResourceController` for profile-bound data), register `Route::resource(...)->only([...])`
in `routes/web.php` (add a `.reorder` POST route if the table has a `position` column), and add the
Vue Index page. Drag-and-drop sorting is automatic for any section with a `position` column.

**Read-only log views.** Append-only history tables (`company_mails`, `application_events`) are
surfaced under the **Logs** sidebar group as read-only lists (`app/Http/Controllers/Logs/`, pages under
`resources/js/pages/logs/`). Their controllers still extend `ResourceController`, but **only the
`index` GET route is registered** (no store/update/destroy), and the Vue page passes the `readonly`
prop to `<ResourceIndex>`. `readonly` hides the create button, the selection checkboxes (and thus the
bulk-action bar), the per-row edit/duplicate/delete actions and double-click-to-edit — leaving search,
sort, expand and pagination. Use this pattern for any further "just look at the rows" table. Newest-first
default ordering comes from overriding `defaultOrderDirection()` on the controller (`'desc'`).

**Web JSON endpoints validate manually.** The global exception handler renders JSON errors only for
`api/*` (see `bootstrap/app.php` `shouldRenderJsonWhen`). A `web` route that returns JSON must
`Validator::make(...)` and return `422` itself — it will not get an automatic JSON validation response.

## Frontend wiring (Wayfinder)

Routes and controller actions are **generated** into TypeScript by `@laravel/wayfinder` (Vite plugin),
not hand-written. `resources/js/routes/`, `resources/js/actions/`, and `resources/js/wayfinder/` are
generated output — import route helpers from there rather than hardcoding URL strings. They regenerate
when Vite runs. The `@/*` import alias maps to `resources/js/*`.

**Never hardcode URL strings in Vue files.** Every `router.get/post/delete(...)` call and every
`fetch('/...')` call must reference a Wayfinder-generated helper instead of a string literal.
Import from `@/actions/App/Http/Controllers/<ControllerName>` (action helpers) or `@/routes/<name>`
(named-route helpers). Example:

```typescript
// Wrong — hardcoded string:
router.post('/customers/export', { id: row.id })

// Correct — Wayfinder import:
import CustomerController from '@/actions/App/Http/Controllers/CustomerController'
router.post(CustomerController.export().url, { id: row.id })
```

Stack: Inertia 3 + Vue 3 `<script setup>` + TypeScript, Tailwind CSS v4, shadcn-vue components built
on reka-ui (see `components.json`), `vuedraggable` for sorting, Tiptap for rich text, ApexCharts
(`vue3-apexcharts`, see `components/dashboard/StatChart.vue`) for dashboard charts, `vue-sonner` for
toasts, and Lucide + Simple Icons for iconography.

> Never put a `LucideIcon` function into Inertia `page.props` (Inertia deep-clones props →
> `DataCloneError`). Icons crossing the props boundary (e.g. `BreadcrumbItem.icon`) are passed as
> **strings** and resolved through an `ICON_MAP`.

**Tooltips: use `<AppTooltip>` only.** `components/AppTooltip.vue` is the single sanctioned tooltip
(the styled hover tooltip on the table row actions). Wrap the target element:
`<AppTooltip text="…"><button …/></AppTooltip>`. Do **not** add a native `title=` attribute for a
hover hint (renders an unstyled browser tooltip) or hand-roll `Tooltip`/`TooltipContent` primitives —
route every tooltip through `AppTooltip` so they share one look. (A `title` on `<iframe>` is an
accessibility frame label, not a tooltip, and is fine.) Two exceptions: the vendored
`ui/sidebar/SidebarMenuButton` collapsed-state tooltip (already renders the same `TooltipContent`),
and a trigger that is *also* a `DropdownMenuTrigger`/`DialogTrigger` — do not wrap those in
`AppTooltip`. Tooltip and the menu/dialog each add their own Popper anchor + pointer handlers to the
trigger, and combining them reliably swallows the trigger's click in reka-ui (see the "Weitere
Aktionen" overflow trigger in `ResourceRowActions.vue`, which stays a plain trigger with an
`aria-label`).

**Form inputs get a white surface.** Input controls use `bg-white dark:bg-input/30` (not
`bg-transparent`), so they read as solid white fields in light mode instead of inheriting the
page/dialog background. Match this on any new input, textarea, select trigger, or editor surface.

**No duplication on the frontend either.** See [Core principle: no code
duplication](#core-principle-no-code-duplication-dry). Repeated markup/logic belongs in a shared
component or a composable (`resources/js/composables/`), repeated shapes in a shared type
(`resources/js/types/`). Index pages should stay pure declaration on top of `ResourceIndex` +
`ResourceFormFields` rather than hand-rolling a parallel table, dialog, or field renderer.

**No `any` in TypeScript.** Every prop, ref, computed, and function parameter must be concretely
typed. When the backend shape is non-trivial, define a named interface in `resources/js/types/`
and import it. Domain-specific types live in a dedicated file (e.g. `types/career-journey.ts`);
only truly generic helpers belong in `types/index.ts`. A bare `any` is never acceptable — use
`unknown` with a type-guard, or narrow to the actual shape.

**Vue components must have a single root element.** Activate the **`inertia-vue-development` skill**
when working on Vue client-side patterns.

**Inertia v3 key changes** (from v2):

- **`Inertia::lazy()` / `LazyProp` removed** — use `Inertia::optional()` instead.
- **Deferred props** — always pair with an empty state (pulsing/animated skeleton) while loading.
- **Axios removed** — use Inertia's built-in XHR client; install Axios separately only if required by a third-party library.
- **`router.cancel()` → `router.cancelAll()`.**
- **Event renames**: `invalid` → `httpException`, `exception` → `networkError`.
- **`future` config namespace removed** — all v2 future options are always enabled; no migration needed.
- **Prop types** (`optional`, `defer`, `merge`) work inside nested arrays via dot-notation paths.
- **SSR works automatically** in Vite dev mode via `@inertiajs/vite` — no separate Node.js server needed in development.
- **New v3 hooks**: `useHttp` (standalone HTTP requests), `useLayoutProps` (layout props), instant visits, optimistic updates with automatic rollback.
- v2 features still available: infinite scroll, merging props, polling, prefetching, once props, flash data.

## Architecture: REST API + OpenAPI

A read-only REST API lives under `app/Http/Controllers/Api/V1/`, routed in `routes/api.php` behind the
`api.token` middleware (`AuthenticateApiToken`, a static bearer token from `config/api.php` /
`API_TOKEN`; **fail-closed** — an empty token rejects every request). Version prefix `v1`; a future
`v2` becomes a sibling group. `ApiResourceController` is the shared base (analogous to the web
`ResourceController`); the concrete controllers return API resources.

Docs are **generated from code** by Scramble (`dedoc/scramble`, `config/scramble.php`) into
`docs/openapi.json`. Regenerate + commit it (`npm run openapi:export`) on any API change or the CI
`openapi` drift job fails the build. The API is meant for server-to-server consumers.

## Architecture: dashboard and navigation counts

- `DashboardController` + `App\Services\Dashboard\DashboardMetrics` — KPIs, orders by month/status, low stock and recent activity, rendered with ApexCharts.
- **Sidebar navigation counts** are served by `App\Services\Navigation\NavigationCountsService` (cached, short TTL). If you add a sidebar section with a badge, add its count query to `NavigationCountsService::compute()`.

## Environment selection

`bootstrap/app.php` picks the env file by HTTP host **before** anything is read: local hosts
(`localhost`, `127.0.0.1`, `*.test`, …) → `.env`; any other host → `.env.production`. This only
applies when `APP_ENV` is not already a real environment variable — CLI/queue/cron have no HTTP host,
so there `APP_ENV=production` must be exported explicitly. The app runs behind Traefik (TLS
termination), so proxies are trusted (`trustProxies(at: '*')`) to detect HTTPS correctly.

## Dates and timezones

Timestamps are stored in `app.timezone` (**UTC** — never change it, that would shift every stored
value). What users see is rendered in `app.display_timezone` (`APP_DISPLAY_TIMEZONE`, default
`Europe/Zurich`) and always reads `21 Aug 2026, 14:00`. Never call `->format('d.m.Y H:i')` on a
model date: use `App\Support\DateTimeFormat` (`display`, `displayDate`, `toInput`, `fromInput` for
`datetime-local` inputs). On the frontend `formatDateTime` / `formatDate` from `lib/resourceCell.ts`
use the same timezone (shared Inertia prop `displayTimezone`, set at boot); `datetime` columns and
fields need no extra code.

## Naming conventions (project exceptions)

Global naming rules apply (`~/.claude/rules/naming-conventions.md`). These framework-driven
names stay **snake_case** — do not "fix" them to camelCase, it would break the app:

- **PHPUnit test methods** — `test_application_is_blocked_without_email`. Intentional: the names
  read as sentences in the test report, and it is the style `php artisan make:test` generates.
- **DB columns / Eloquent attributes** — `application_sent_at`, `sso_password`, `user_id`.
- **Config, validation, and request-input keys** — e.g. `['website_template_id' => …]`, `rules()` keys.
- **Route names** — dotted/kebab (`customers.restore`).

(Enum case keys are TitleCase — see PHP coding conventions below.)

## Language (project delta)

Global language policy applies (`~/.claude/rules/language.md`). Project specifics:

- **App UI locale is English** — user-facing strings, labels, and enum `label()` output are English.
- Everything developer-facing is English too.

## PHP coding conventions

These are **mandatory** for all new and edited PHP code. They go beyond Pint (style) and align with
PHPStan level 5 — treat them as hard requirements, not suggestions.

- **Everything is typed.** Every method needs parameter types and a return type (`: void` included).
  Type every property. Declare constants with a type where the language allows (`const int MAX = 10;`).
  Prefer real types over docblocks; use docblocks only to add detail the signature can't express.
- **Specify array shapes.** A bare `array` type is not enough — always describe its contents with a
  PHPDoc generic or shape: `@param array<int, string> $ids`, `@return array<string, mixed>`, or a
  shape `array{id: int, name: string}`. This applies to params, returns, and typed properties.
- **Exceptions are accounted for.** Either catch an exception where it can be handled, or document it
  with an `@throws Fully\Qualified\Exception` tag on the method that lets it propagate. Never leave a
  throwing call silently undocumented.
- **Minimal string interpolation braces.** In interpolated strings use braces only when the parser
  needs them. Write `"%$search%"`, not `"%{$search}%"`; use `"{$obj->prop}"` / `"{$arr['key']}"`
  only where the simple form would be ambiguous.
- **Static closures by default.** When a closure does not use `$this`, declare it `static function`
  (or `static fn`). This applies to callbacks passed to collections, `array_map`, query builders, etc.
- **`declare(strict_types=1);`** at the top of every PHP file.
- **Constructor property promotion** for injected dependencies; mark them `private readonly` when the
  value never changes after construction.
- **Use `::class` references** (`SomeModel::class`) instead of hardcoded class-name strings.
- **Guard clauses / early returns** over deep nesting.
- **Merge `match` arms with the same result.** When several arms return the identical value, list the
  cases on one comma-separated arm (`self::A, self::B => 'x',`) rather than repeating the value on
  separate lines. Duplicated arm bodies are flagged as duplicate-arm inspection warnings.
- Before considering PHP work done, it must pass `vendor/bin/pint --parallel` and `composer analyse`.
- **`Model::preventLazyLoading` is active in non-production.** Any new relation access that isn't
  eager-loaded will throw a `LazyLoadingViolationException` during local development and in the test
  suite. Always declare eager loads in the controller's `eagerLoad()` hook rather than accessing
  relations lazily on individual rows.
- **Use named scopes instead of repeating inline `where()` filters.** When the same `where()` filter
  appears in more than one place for the same model, add a named scope (`scopeX()`) to the model and
  call `->x()` at every call-site (e.g. `Customer::scopeActive()`).
- **Always use curly braces** for control structures, even for single-line bodies.
- **No empty `__construct()`.** Do not leave empty zero-parameter constructors unless the constructor is intentionally private (constructor property promotion makes them unnecessary).
- **TitleCase for Enum keys** — `FavoritePerson`, `BestLake`, `Monthly`.
- **PHPDoc over inline comments.** Prefer PHPDoc blocks for documentation; only add an inline `//` comment for exceptionally complex logic that can't be expressed through naming alone.
- **AI/external-API routes carry throttle middleware.** Any route that proxies to an AI provider, a mail
  server, a web scraper, or another paid/rate-limited external service must include
  `->middleware('throttle:10,1')` (or a stricter limit). Do not add such routes without it.

## Testing conventions

- **Every change must be tested.** Write a new test or update an existing one before considering work done. Do not create tinker scripts or verification scripts when a test can prove the same thing.
- **Create tests with Artisan:** `php artisan make:test --phpunit {name}` for feature tests; add `--unit` for unit tests. If you encounter a test written in Pest, convert it to PHPUnit.
- **Use factories** when creating model instances in tests. Check for existing factory states before setting attributes manually.
- **Faker convention:** follow whichever style the surrounding tests use — `$this->faker->word()` or `fake()->randomDigit()`.
- **Cover happy paths, failure paths, and edge cases.**
- **Do not remove test files without explicit approval** — they are not temporary files.
- **Run the minimum number of tests** using `--filter`. After updating a test, run only that test:
  - Single test: `php artisan test --compact --filter=testName`
  - Single file: `php artisan test --compact tests/Feature/ExampleTest.php`
  - Full suite: `php artisan test --compact`

## Auth

Laravel Fortify: email/password login, registration, password reset, email verification, TOTP
two-factor authentication (with recovery codes) and passkeys (`laravel/passkeys`). The UI lives in
`pages/auth/*`, `pages/settings/Security.vue` and `components/security/*`. User administration is
gated by the `manage-users` Gate (`AppServiceProvider`).

## Laravel Boost guidelines

The Laravel Boost MCP server injects its guidelines (framework rules, `search-docs`, `database-query`,
`record-rule`, etc.) directly into each AI session as a system prompt — they do not need to live here.
Area-specific rules recorded with `record-rule` are stored under `.ai/rules/` (read that directory
before editing any file if it exists).

> Do not re-embed the generated `<laravel-boost-guidelines>` block here; Boost injects it per session.
