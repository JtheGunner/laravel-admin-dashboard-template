<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

use function in_array;

/**
 * Generic CRUD base for a resource. Concrete controllers provide the model,
 * the search/validation configuration and the Inertia page. Index/Store/Update/
 * Destroy run uniformly; the frontend uses <ResourceIndex> for them.
 *
 * @template TModel of Model
 */
abstract class ResourceController extends Controller {
    public function index(Request $request): Response {
        $search = trim((string) $request->input('search', ''));
        $columns = $this->searchableColumns();
        $perPage = $this->perPage($request);

        // Trash view: only allowed for SoftDeletes models; shows the (soft-)deleted
        // entries exclusively instead of the active ones.
        $trashed = $this->usesSoftDeletes() && $request->boolean('trashed');

        // Server-side sort, so the ordering spans all pages instead of only the
        // current one. Real DB columns (whitelisted) order directly; computed
        // columns (relation names, counts) go through sortExpressions(). An
        // unknown/absent sort keeps the default order.
        $sort = trim((string) $request->input('sort', ''));
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortExpressions = $this->sortExpressions();

        $searchWhen = static function (Builder $query) use ($search, $columns): void {
            $query->where(static function (Builder $q) use ($search, $columns): void {
                foreach ($columns as $index => $column) {
                    $index === 0
                        ? $q->where($column, 'like', "%$search%")
                        : $q->orWhere($column, 'like', "%$search%");
                }
            });
        };

        // A bare (no-eager-load) clone of the search-narrowed base query, kept for
        // cascading facet option counts. Must be captured before applyFilters() mutates $query.
        $preFilterQuery = ($trashed ? $this->onlyTrashedQuery() : $this->baseQuery())
            ->when($search !== '' && $columns !== [], $searchWhen);
        $this->narrowIndexQuery($preFilterQuery, $request);

        $query = (clone $preFilterQuery)
            ->with($this->eagerLoad());

        // Faceted (multi-select) filters: narrow the query by the declared columns
        // and capture the effective selection to echo back to the frontend.
        $filters = $this->filters();
        $activeFilters = $this->applyFilters($query, $request, $filters);

        $activeSort = $this->applySort($query, $sort, $direction, $sortExpressions);

        if ($this->paginates()) {
            $items = $query->paginate($perPage)
                ->withQueryString()
                ->through(fn (Model $model) => $this->transformRow($model));
        } else {
            // Full dataset for hierarchical/tree views, wrapped in the shape of a
            // paginator (the frontend expects the same structure but does not
            // render pagination).
            $rows = $query->get()->map(fn (Model $model) => $this->transformRow($model))->values();
            $items = [
                'data' => $rows,
                'links' => [],
                'from' => $rows->isEmpty() ? null : 1,
                'to' => $rows->isEmpty() ? null : $rows->count(),
                'total' => $rows->count(),
            ];
        }

        return Inertia::render($this->page(), [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'trashed' => $trashed,
                'sort' => $activeSort,
                'direction' => $activeSort !== null ? $direction : null,
                // Column the list falls back to when no explicit sort is active,
                // so the frontend sort control can show the default selection.
                'defaultSort' => $this->defaultSortKey(),
            ],
            'perPage' => $perPage,
            'pageSizes' => $this->pageSizes(),
            // Columns the table may be ordered by server-side: the model's real
            // DB columns plus any computed columns declared in sortExpressions().
            'sortableColumns' => array_values(array_unique([
                ...$this->sortableColumns(),
                ...array_keys($sortExpressions),
            ])),
            // Controls the trash toggle in the frontend. The counter reports the
            // number of (soft-)deleted records within the current baseQuery scope.
            'softDeletes' => $this->usesSoftDeletes(),
            'trashedCount' => $this->usesSoftDeletes()
                ? $this->onlyTrashedQuery()->count()
                : 0,
            // Faceted filter definitions + their currently active selection. The
            // frontend renders a filter menu from these and echoes the selection
            // back on search/sort/pagination. Empty when no filters are declared.
            'facets' => $this->facetProps($filters, $activeFilters, $preFilterQuery),
            ...$this->extraProps(),
        ]);
    }

    /**
     * Hook for section-specific request constraints that are not plain column facets
     * (e.g. a free-form exclusion expression). Applied to the search-narrowed query
     * before the facets, so option counts reflect it too. Default: none.
     *
     * @param  Builder<TModel>  $query
     */
    protected function narrowIndexQuery(Builder $query, Request $request): void {
    }

    public function store(Request $request): RedirectResponse {
        $data = $this->mutateData($request->validate($this->rules()));
        $data = $this->persistFiles($request, $data);

        $model = $this->modelClass()::create($data);
        $this->afterStore($model);

        return $this->redirectBack('created');
    }

    public function update(Request $request, string $id): RedirectResponse {
        $model = $this->baseQuery()->findOrFail($id);

        $data = $this->mutateData($request->validate($this->rules((int) $id)), (int) $id);
        $data = $this->persistFiles($request, $data, $model);

        // Raw (uncast) originals: avoids eagerly decrypting encrypted casts such
        // as an SSO password that may carry foreign ciphertext.
        $original = $model->getRawOriginal();
        $model->update($data);
        $this->afterUpdate($model, $original);

        return $this->redirectBack('updated');
    }

    public function destroy(string $id, Request $request): RedirectResponse {
        $this->baseQuery()->findOrFail($id)->delete();

        return $this->redirectBack('deleted', $this->indexRedirectFromRequest($request));
    }

    /**
     * Delete several entries at once. Scoped through baseQuery so that only
     * own/permitted records are affected; SoftDeletes apply as in destroy().
     */
    public function bulkDestroy(Request $request): RedirectResponse {
        $ids = $this->validatedIds($request);

        if ($ids === []) {
            return back();
        }

        $count = (clone $this->baseQuery())->whereIn('id', $ids)->count();
        $this->baseQuery()->whereIn('id', $ids)->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$count} entries deleted.",
        ]);

        return back();
    }

    /**
     * Restore a soft-deleted entry. Scoped through baseQuery so that only
     * own/permitted records are affected (SoftDeletes models only).
     */
    public function restore(string $id): RedirectResponse {
        $this->abortUnlessSoftDeletes();

        $affected = $this->onlyTrashedQuery()
            ->whereKey($id)
            ->update([$this->deletedAtColumn() => null]);

        abort_if($affected === 0, 404);

        return $this->redirectBack('restored');
    }

    /**
     * Permanently remove an entry from the database (no more SoftDelete).
     */
    public function forceDestroy(string $id): RedirectResponse {
        $this->abortUnlessSoftDeletes();

        $affected = $this->withTrashedQuery()
            ->whereKey($id)
            ->getQuery()
            ->delete();

        abort_if($affected === 0, 404);

        return $this->redirectBack('permanently deleted');
    }

    /**
     * Restore several soft-deleted entries at once.
     */
    public function bulkRestore(Request $request): RedirectResponse {
        $this->abortUnlessSoftDeletes();

        $ids = $this->validatedIds($request);
        if ($ids === []) {
            return back();
        }

        $count = $this->onlyTrashedQuery()
            ->whereIn('id', $ids)
            ->update([$this->deletedAtColumn() => null]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$count} entries restored.",
        ]);

        return back();
    }

    /**
     * Permanently delete several entries at once.
     */
    public function bulkForceDestroy(Request $request): RedirectResponse {
        $this->abortUnlessSoftDeletes();

        $ids = $this->validatedIds($request);
        if ($ids === []) {
            return back();
        }

        $count = $this->withTrashedQuery()
            ->whereIn('id', $ids)
            ->getQuery()
            ->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$count} entries permanently deleted.",
        ]);

        return back();
    }

    /**
     * Persist ordering (for sections with a `position` column). Expects an
     * ordered list of IDs; updates position scoped through baseQuery so that
     * foreign records cannot be modified.
     */
    public function reorder(Request $request): RedirectResponse {
        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
            'from' => ['nullable', 'integer', 'min:1'],
        ]);

        $pageIds = array_map('intval', $validated['ids'] ?? []);
        if ($pageIds === []) {
            return back();
        }

        // Fetch the full (deterministic) ordering of the sections and insert the
        // new ordering of the current page at its global position.
        $allIds = $this->baseQuery()
            ->orderBy($this->defaultOrderColumn())
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $offset = max(0, ((int) ($validated['from'] ?? 1)) - 1);
        array_splice($allIds, $offset, count($pageIds), $pageIds);

        // Renumber everything consecutively (1..N) and uniquely.
        $seen = [];
        $position = 1;
        foreach ($allIds as $id) {
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $this->baseQuery()->whereKey($id)->update(['position' => $position++]);
        }

        return back();
    }

    /** @return class-string<TModel> */
    abstract protected function modelClass(): string;

    /** Inertia page component, e.g. "customers/Index". */
    abstract protected function page(): string;

    /** Singular label for flash messages, e.g. "Customer". */
    abstract protected function singularLabel(): string;

    /**
     * Columns the search runs over.
     *
     * @return list<string>
     */
    abstract protected function searchableColumns(): array;

    /**
     * Validation rules. $id is set on update (for unique-ignore etc.).
     *
     * @return array<string, mixed>
     */
    abstract protected function rules(?int $id = null): array;

    protected function defaultOrderColumn(): string {
        return 'id';
    }

    /**
     * Direction applied to {@see defaultOrderColumn()} when no explicit sort is
     * requested. Defaults to ascending; append-only log views override this with
     * 'desc' to show the newest entries first.
     *
     * @return 'asc'|'desc'
     */
    protected function defaultOrderDirection(): string {
        return 'asc';
    }

    /**
     * The frontend sort key matching the default order, used to pre-fill the
     * sort control. Defaults to {@see defaultOrderColumn()}; override when the
     * displayed/sortable column key differs from the raw DB order column (e.g. a
     * computed `display_name` column ordered server-side by the `name` column).
     */
    protected function defaultSortKey(): string {
        return $this->defaultOrderColumn();
    }

    /**
     * Real DB columns the list may be ordered by server-side. Defaults to the
     * model's table columns; the frontend only sends a sort for columns it
     * declares `sortable` that are also server-sortable (guards against ordering
     * by an arbitrary/unknown column).
     *
     * @return list<string>
     */
    protected function sortableColumns(): array {
        return Schema::getColumnListing((new ($this->modelClass())())->getTable());
    }

    /**
     * Sort expressions for columns that are not plain DB columns (computed
     * values like relation names or aggregate counts). Maps the frontend sort
     * key to a callback that applies the ORDER BY to the query. These keys
     * become server-sortable in addition to sortableColumns().
     *
     * @return array<string, callable(Builder<TModel>, 'asc'|'desc'): void>
     */
    protected function sortExpressions(): array {
        return [];
    }

    /**
     * Declares the available faceted filters for this resource. Each key is the
     * database column name (or relation column, if handled by a custom query).
     * The `options` array provides the allowed values and their labels; only
     * valid options submitted by the client are accepted.
     *
     * @return array<string, array{label: string, type?: string, options?: list<array{value: int|string, label: string}>, default?: list<int|string>, nullValue?: int|string, cascade?: bool}>
     */
    protected function filters(): array {
        return [];
    }

    /**
     * Apply the declared faceted filters from the request onto the query and
     * return the effective per-column selection (the values actually in force), to
     * echo back to the frontend. A filter constrains its column only when a
     * non-empty proper subset of its options is selected; an empty or complete
     * selection means "no constraint" (all rows). When the request carries no
     * selection for a filter, its declared default applies.
     *
     * @param  Builder<TModel>  $query
     * @param  array<string, array{label: string, type?: string, options?: list<array{value: int|string, label: string}>, default?: list<int|string>, nullValue?: int|string, cascade?: bool}>  $filters
     * @return array<string, list<int|string>>
     */
    protected function applyFilters(Builder $query, Request $request, array $filters): array {
        $requested = $request->input('filter');
        $requested = is_array($requested) ? $requested : [];
        $active = [];

        foreach ($filters as $column => $def) {
            $isDate = ($def['type'] ?? 'select') === 'date';
            $optionValues = array_map(static fn (array $o): int|string => $o['value'], $def['options'] ?? []);
            $default = $def['default'] ?? [];

            if (array_key_exists($column, $requested)) {
                $rawList = is_array($requested[$column]) ? $requested[$column] : [$requested[$column]];
                $rawStrings = array_values(array_map(static fn (mixed $v): string => (string) $v, $rawList));

                if ($isDate) {
                    $selected = $rawStrings;
                } else {
                    // Keep only declared options, preserving their typed value.
                    $selected = array_values(array_filter(
                        $optionValues,
                        static fn (int|string $opt): bool => in_array((string) $opt, $rawStrings, true),
                    ));
                }
            } else {
                $selected = $default;
            }

            $active[$column] = $selected;

            // Constrain only for a non-empty proper subset (empty/full = all rows).
            // For date types, there are no predefined options, so any selection applies a constraint.
            if ($selected !== [] && ($isDate || count($selected) < count($optionValues))) {
                $this->applyFilterConstraint($query, $column, $selected, $def['nullValue'] ?? null);
            }
        }

        return $active;
    }

    /**
     * Constrain the query to the selected values of a single filter. The optional
     * `nullValue` sentinel is mapped to an `IS NULL` match (OR-combined with any
     * real values), so a nullable column can be filtered for "no value".
     *
     * @param  Builder<TModel>  $query
     * @param  list<int|string>  $selected
     */
    protected function applyFilterConstraint(Builder $query, string $column, array $selected, int|string|null $nullValue): void {
        $matchesNull = $nullValue !== null
            && in_array((string) $nullValue, array_map(static fn (int|string $v): string => (string) $v, $selected), true);

        $realValues = $nullValue === null
            ? $selected
            : array_values(array_filter($selected, static fn (int|string $v): bool => (string) $v !== (string) $nullValue));

        if (!$matchesNull) {
            $query->whereIn($column, $realValues);

            return;
        }

        $query->where(static function (Builder $q) use ($column, $realValues): void {
            if ($realValues !== []) {
                $q->whereIn($column, $realValues);
            }

            $q->orWhereNull($column);
        });
    }

    /**
     * Return the ISO date strings (YYYY-MM-DD) of days that have at least one row
     * in the given query, for use by the frontend date-picker to grey out empty days.
     * The supplied query already has sibling filter constraints applied.
     *
     * Override in controllers that declare a date-type filter and want this feature.
     * Use literal strings with DB::raw() inside the override to keep PHPStan happy.
     *
     * @param  Builder<TModel>  $baseQuery  Sibling-constrained query (no date filter applied).
     * @return list<string>|null ISO date strings, or null to disable the feature.
     */
    protected function availableDatesForFilter(string $filterKey, Builder $baseQuery): ?array {
        return null;
    }

    /**
     * Apply an ORDER BY over a related model's column via a correlated subquery
     * (no join, so the base SELECT stays clean). Intended for use inside
     * sortExpressions() to make relation-derived columns server-sortable.
     *
     * @param  Builder<TModel>  $query
     * @param  class-string<Model>  $relatedClass
     * @param  'asc'|'desc'  $direction
     */
    protected function orderByRelated(Builder $query, string $relatedClass, string $foreignKey, string $relatedColumn, string $direction): void {
        $related = new $relatedClass;
        $localTable = $query->getModel()->getTable();

        $query->orderBy(
            $relatedClass::query()
                ->select($relatedColumn)
                ->whereColumn(
                    $related->getTable() . '.' . $related->getKeyName(),
                    $localTable . '.' . $foreignKey,
                ),
            $direction,
        );
    }

    /**
     * Whether the list is paginated server-side. Hierarchical sections (tree)
     * need the full dataset and override this with false.
     */
    protected function paginates(): bool {
        return true;
    }

    /**
     * Allowed page sizes for the "per page" dropdown.
     *
     * @return list<int>
     */
    protected function pageSizes(): array {
        return [10, 15, 25, 50, 100, 250, 500, 1000, 2000];
    }

    protected function perPage(Request $request): int {
        $value = (int) $request->input('perPage', 15);

        return in_array($value, $this->pageSizes(), true) ? $value : 15;
    }

    /**
     * Base query of the resource. Subclasses (e.g. user-scoped) constrain it
     * here — this automatically also protects update/destroy against foreign access.
     *
     * @return Builder<TModel>
     */
    protected function baseQuery(): Builder {
        // Larastan's dynamic return-type extension collapses ::query()/newQuery()
        // to Builder<Model> for the template type, so bridge TModel explicitly.
        /** @var Builder<TModel> $query */
        $query = $this->modelClass()::query();

        return $query;
    }

    /**
     * Relations eager-loaded for the list.
     *
     * @return list<string>
     */
    protected function eagerLoad(): array {
        return [];
    }

    /**
     * Prepare a row for the frontend (e.g. add related names).
     *
     * @return array<string, mixed>
     */
    protected function transformRow(Model $model): array {
        return $model->toArray();
    }

    /**
     * Hook to adjust the validated data before saving.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateData(array $data, ?int $id = null): array {
        return $data;
    }

    /**
     * Hook fired after a record was created. Subclasses use it to record an
     * activity-log event etc. Default: no-op.
     */
    protected function afterStore(Model $model): void {
    }

    /**
     * Hook fired after a record was updated. $original holds the raw (uncast)
     * attribute values as they were *before* the update (from
     * {@see Model::getRawOriginal()}), so a subclass can diff changed fields.
     * Default: no-op.
     *
     * @param  array<string, mixed>  $original
     */
    protected function afterUpdate(Model $model, array $original): void {
    }

    /**
     * Additional props for the index page (e.g. dropdown options).
     *
     * @return array<string, mixed>
     */
    protected function extraProps(): array {
        return [];
    }

    /**
     * Inertia page component for the single-record detail view, e.g.
     * "customers/Show". Returns null by default — concrete controllers
     * opt in by overriding this method. show() aborts with 404 when null.
     */
    protected function showPage(): ?string {
        return null;
    }

    /**
     * Extra props merged into the show() response. Defaults to extraProps() so
     * the detail page receives the same option sets as the index; override to
     * add or replace props specific to the show view.
     *
     * @return array<string, mixed>
     */
    protected function showExtraProps(): array {
        return $this->extraProps();
    }

    /**
     * Full-page detail view for a single record. Resolves through baseQuery() so
     * UserScopedResourceController's user_id scope applies automatically — cross-
     * user access produces a 404. Aborts with 404 when showPage() is null.
     */
    public function show(string $id): Response {
        abort_unless($this->showPage() !== null, 404);

        $model = $this->baseQuery()
            ->with($this->eagerLoad())
            ->findOrFail($id);

        return Inertia::render($this->showPage(), [
            'item' => $this->transformRow($model),
            ...$this->showExtraProps(),
        ]);
    }

    /**
     * Columns holding an uploaded file, as `column => subdirectory`.
     * For these, an uploaded file is moved to the disk (fileDisk) on store/update
     * and the stored path is written into the column; an explicitly cleared field
     * removes the existing file. Fields that stay unchanged (the existing path
     * string) are not overwritten.
     *
     * @return array<string, string>
     */
    protected function fileColumns(): array {
        return [];
    }

    /** Disk that uploaded files are written to. */
    protected function fileDisk(): string {
        return 'public';
    }

    /**
     * Hook fired whenever persistFiles() changes a file column: after a new file
     * was stored ($newPath set) or after a field was cleared ($newPath null).
     * $oldPath is the previously stored path (or null). Subclasses use this to
     * maintain derived artifacts (e.g. a compressed sibling). Default: no-op.
     */
    protected function afterFilePersisted(string $column, ?string $newPath, ?string $oldPath, string $disk): void {
    }

    /**
     * Move uploaded files for the columns declared in fileColumns() onto the
     * disk and extend $data with the stored paths.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function persistFiles(Request $request, array $data, ?Model $existing = null): array {
        foreach ($this->fileColumns() as $column => $directory) {
            $disk = $this->fileDisk();
            $current = $existing?->getAttribute($column);

            if ($request->hasFile($column)) {
                // New file: replace the old one and store it under its original
                // name in a unique subdirectory (collision-free, but the base name
                // stays the real file name — for display in the frontend).
                if ($current) {
                    Storage::disk($disk)->delete($current);
                }
                $file = $request->file($column);
                $storedPath = $file->storeAs(
                    $directory . '/' . Str::random(20),
                    $this->safeUploadName($file->getClientOriginalName()),
                    $disk,
                ) ?: null;
                $data[$column] = $storedPath;
                $this->afterFilePersisted($column, $storedPath, $current ?: null, $disk);
            } elseif (array_key_exists($column, $data) && $data[$column] === null) {
                // Field explicitly cleared: remove the existing file.
                if ($current) {
                    Storage::disk($disk)->delete($current);
                }
                $data[$column] = null;
                $this->afterFilePersisted($column, null, $current ?: null, $disk);
            } else {
                // Unchanged (existing path string) → leave the column untouched.
                unset($data[$column]);
            }
        }

        return $data;
    }

    /**
     * Sanitises an uploaded file name into a safe but readable base name
     * (keeps letters/digits/spaces as well as . _ - and the extension).
     */
    protected function safeUploadName(string $name): string {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/[^\p{L}\p{N} ._-]+/u', '', $base) ?? '';
        $base = trim(preg_replace('/\s+/u', ' ', $base) ?? '');

        if ($base === '') {
            $base = 'file';
        }

        return $extension !== '' ? $base . '.' . strtolower($extension) : $base;
    }

    /** Whether the resource's model uses SoftDeletes. */
    protected function usesSoftDeletes(): bool {
        return in_array(SoftDeletes::class, class_uses_recursive($this->modelClass()), true);
    }

    /**
     * Query over ALL rows including soft-deleted ones (removes the SoftDelete scope).
     * Deliberately via withoutGlobalScope instead of the trait magic methods
     * withTrashed()/onlyTrashed(), so that the generic baseQuery() (Builder), which
     * is bound to no concrete model, stays statically analysable.
     *
     * @return Builder<TModel>
     */
    protected function withTrashedQuery(): Builder {
        return $this->baseQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }

    /**
     * Query over soft-deleted rows only.
     *
     * @return Builder<TModel>
     */
    protected function onlyTrashedQuery(): Builder {
        return $this->withTrashedQuery()->whereNotNull($this->deletedAtColumn());
    }

    /** Column holding the soft-delete timestamp (Laravel default: deleted_at). */
    protected function deletedAtColumn(): string {
        return 'deleted_at';
    }

    /**
     * Build the frontend facet props: each declared filter with its options narrowed
     * to only those that still produce results given the other active filter selections
     * (cascading / interdependent filters). Currently selected options are always kept
     * so the user can deselect them. Date filters are excluded from cascading because
     * they have no discrete options list.
     *
     * @param  array<string, array{label: string, type?: string, options?: list<array{value: int|string, label: string}>, default?: list<int|string>, nullValue?: int|string, cascade?: bool}>  $filters
     * @param  array<string, list<int|string>>  $active
     * @param  Builder<TModel>  $baseQuery  Search-narrowed query before any facet constraints are applied.
     * @return list<array{key: string, label: string, type: string, options: list<array{value: int|string, label: string, disabled?: bool}>, optionsTotal: int, selected: list<int|string>}>
     */
    private function facetProps(array $filters, array $active, Builder $baseQuery): array {
        $facets = [];

        foreach ($filters as $column => $def) {
            $allOptions = $def['options'] ?? [];
            $isDate = ($def['type'] ?? 'select') === 'date';
            $nullValue = $def['nullValue'] ?? null;
            $selected = $active[$column] ?? [];
            $selectedStrings = array_map('strval', $selected);

            $options = $allOptions;

            // Cascade: narrow options to those that still have rows when the sibling
            // filters are applied. Only for select-style filters with multiple siblings.
            // Filters with 'cascade' => false opt out (e.g. relation-based constraints
            // that have no direct column on the table to SELECT DISTINCT on).
            $cascadeEnabled = ($def['cascade'] ?? true) !== false;

            if (!$isDate && $cascadeEnabled && count($filters) > 1 && $allOptions !== []) {
                $sibling = clone $baseQuery;

                foreach ($filters as $otherCol => $otherDef) {
                    if ($otherCol === $column) {
                        continue;
                    }

                    $otherSelected = $active[$otherCol] ?? [];
                    $otherIsDate = ($otherDef['type'] ?? 'select') === 'date';
                    $otherOptionValues = array_map(static fn (array $o): int|string => $o['value'], $otherDef['options'] ?? []);

                    if ($otherSelected !== [] && ($otherIsDate || count($otherSelected) < count($otherOptionValues))) {
                        $this->applyFilterConstraint($sibling, $otherCol, $otherSelected, $otherDef['nullValue'] ?? null);
                    }
                }

                // One query: distinct values that exist under the sibling-filtered set.
                $present = $sibling->select($column)->distinct()->pluck($column);
                $presentStrings = $present
                    ->filter(static fn (mixed $v): bool => $v !== null)
                    ->map(static fn (mixed $v): string => match (true) {
                        $v instanceof BackedEnum => (string) $v->value,
                        is_bool($v) => (string) (int) $v,
                        default => (string) $v,
                    })
                    ->values()
                    ->all();
                $hasNull = $present->contains(null);

                // Mark options as disabled when sibling filters leave no matching rows for them.
                // Selected options are never disabled so the user can always deselect them.
                // Enabled options come first; disabled options are sorted to the end.
                $options = array_map(
                    static function (array $opt) use ($presentStrings, $hasNull, $nullValue, $selectedStrings): array {
                        $optStr = (string) $opt['value'];

                        if (in_array($optStr, $selectedStrings, true)) {
                            return $opt;
                        }

                        $isNullSentinel = $nullValue !== null && $optStr === (string) $nullValue;
                        $available = $isNullSentinel ? $hasNull : in_array($optStr, $presentStrings, true);

                        if (!$available) {
                            $opt['disabled'] = true;
                        }

                        return $opt;
                    },
                    $allOptions,
                );

                usort($options, static fn (array $a, array $b): int => (int) ($a['disabled'] ?? false) <=> (int) ($b['disabled'] ?? false));
            }

            // For date filters that declare a `dateColumn`: compute which calendar days
            // For date filters: ask the controller which calendar days have entries
            // (respecting sibling filter constraints) so the frontend can grey out
            // empty days. The sibling query is built here and passed to the hook.
            $availableDates = null;

            if ($isDate) {
                $sibling = clone $baseQuery;

                foreach ($filters as $otherCol => $otherDef) {
                    if ($otherCol === $column) {
                        continue;
                    }

                    $otherSelected = $active[$otherCol] ?? [];
                    $otherIsDate = ($otherDef['type'] ?? 'select') === 'date';
                    $otherOptionValues = array_map(static fn (array $o): int|string => $o['value'], $otherDef['options'] ?? []);

                    if ($otherSelected !== [] && ($otherIsDate || count($otherSelected) < count($otherOptionValues))) {
                        $this->applyFilterConstraint($sibling, $otherCol, $otherSelected, $otherDef['nullValue'] ?? null);
                    }
                }

                $availableDates = $this->availableDatesForFilter($column, $sibling);
            }

            $facets[] = [
                'key' => $column,
                'label' => $def['label'],
                'type' => $def['type'] ?? 'select',
                'options' => $options,
                // Full option count before cascading narrows the list; used by the
                // frontend to detect whether the filter is actively constraining results.
                'optionsTotal' => count($allOptions),
                'selected' => $selected,
                // Dates with at least one entry (ISO strings, null when not a date filter
                // or no dateColumn was declared).
                'availableDates' => $availableDates,
            ];
        }

        return $facets;
    }

    /**
     * Resolve and apply the requested sort. Returns the applied sort key (for
     * echoing back to the frontend), or null when no valid sort was requested
     * and the default order was used instead.
     *
     * @param  Builder<TModel>  $query
     * @param  'asc'|'desc'  $direction
     * @param  array<string, callable(Builder<TModel>, 'asc'|'desc'): void>  $expressions
     */
    private function applySort(Builder $query, string $sort, string $direction, array $expressions): ?string {
        // `id` as a stable tie-breaker keeps pagination deterministic.
        if ($sort !== '' && isset($expressions[$sort])) {
            $expressions[$sort]($query, $direction);
            $query->orderBy('id');

            return $sort;
        }

        if ($sort !== '' && in_array($sort, $this->sortableColumns(), true)) {
            $query->orderBy($sort, $direction)->orderBy('id');

            return $sort;
        }

        $query->orderBy($this->defaultOrderColumn(), $this->defaultOrderDirection());

        return null;
    }

    /** 404 when the trash actions are called on a non-SoftDelete model. */
    private function abortUnlessSoftDeletes(): void {
        abort_unless($this->usesSoftDeletes(), 404);
    }

    /**
     * Validated, integer ID list from a bulk request.
     *
     * @return list<int>
     */
    private function validatedIds(Request $request): array {
        $validated = $request->validate([
            'ids' => ['array'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_map('intval', $validated['ids'] ?? []));
    }

    private function redirectBack(string $verb, ?string $explicitTarget = null): RedirectResponse {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$this->singularLabel()} {$verb}.",
        ]);

        return $explicitTarget !== null ? redirect($explicitTarget) : back();
    }

    /**
     * Resolve an explicit redirect target for destroy() when the request
     * carries a truthy `return_to_index` flag, optionally with an
     * `index_query` query string. A record's own Show page passes these so
     * that deleting it there redirects to the resource's index (optionally
     * with the index filters the user came from) instead of `back()`, which
     * would otherwise bounce back to the just-deleted record's own (now
     * 404-ing) detail URL — that URL is the Referer for a delete triggered
     * from the Show page itself. (`return_to_index` is a separate flag rather
     * than an empty-string sentinel because the framework's
     * ConvertEmptyStringsToNull middleware turns empty query values to null,
     * making them indistinguishable from an absent parameter.)
     */
    private function indexRedirectFromRequest(Request $request): ?string {
        if (!$request->boolean('return_to_index')) {
            return null;
        }

        $routeBase = Str::beforeLast((string) $request->route()?->getName(), '.');

        if ($routeBase === '' || !Route::has("{$routeBase}.index")) {
            return null;
        }

        $indexUrl = route("{$routeBase}.index");
        $indexQuery = (string) $request->query('index_query', '');

        return $indexQuery === '' ? $indexUrl : "{$indexUrl}?{$indexQuery}";
    }
}
