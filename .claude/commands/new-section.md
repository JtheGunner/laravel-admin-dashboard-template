---
description: Scaffold a new CRUD section on the generic ResourceController + ResourceIndex engine.
argument-hint: <section-name> (e.g. suppliers)
---

Scaffold a new CRUD "section" for **$ARGUMENTS** following the project's generic CRUD engine.
Propose the plan first — do NOT write files until the user confirms.

Follow the "Adding a section" recipe in `AGENTS.md`. Reuse — never re-implement — these seams:

- **Controller:** extend `app/Http/Controllers/ResourceController.php` (or
  `app/Http/Controllers/UserScopedResourceController.php` for per-user data). Override
  `modelClass()`, `page()`, `singularLabel()`, `searchableColumns()`, `rules()`, and only the hooks you
  actually need (`defaultOrderColumn`, `eagerLoad`, `transformRow`, `sortableColumns`/`sortExpressions`,
  `fileColumns`, `usesSoftDeletes`, …).
- **Vue page:** `resources/js/pages/$ARGUMENTS/Index.vue` — pure declaration of `columns` + `fields`
  rendered by `resources/js/components/resource/ResourceIndex.vue` and `ResourceFormFields.vue`
  (`FieldConfig` type in `resources/js/types/resource.ts`).
- **Route:** register `Route::resource(...)->only([...])` in `routes/web.php` (add a `.reorder` POST
  route if the table has a `position` column — drag-and-drop sorting is then automatic).
- **Canonical reference pair:** `resources/js/pages/customers/Index.vue` +
  `app/Http/Controllers/CustomerController.php`.

Deliver: model + additive migration, controller, Vue Index page, and routes. Respect every PHP
convention in `AGENTS.md` (`declare(strict_types=1)`, full typing, array shapes, guard clauses, DRY).
After scaffolding, run `/ci` before considering it done.
