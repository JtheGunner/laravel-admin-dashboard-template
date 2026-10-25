<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Generic, read-only API base. Provides the shared query logic
 * (search, includes, pagination, scoping) as protected helpers.
 *
 * Concrete controllers implement index()/show() themselves and return their
 * specific API resource there — this lets Scramble derive the response schema
 * per endpoint automatically (a dynamically resolved resource class would not
 * be statically analysable).
 *
 * Write access is deliberately not implemented yet; the routes are limited to
 * index/show. For writes, add store/update/destroy later — resolve
 * update/destroy through baseQuery() so that the scoping applies.
 *
 * @template TModel of Model
 */
abstract class ApiResourceController extends Controller {
    /** @return class-string<TModel> */
    abstract protected function modelClass(): string;

    /**
     * Columns that ?search= runs over.
     *
     * @return list<string>
     */
    protected function searchableColumns(): array {
        return [];
    }

    protected function defaultOrderColumn(): string {
        return 'id';
    }

    /**
     * Relations that are always eager-loaded.
     *
     * @return list<string>
     */
    protected function eagerLoad(): array {
        return [];
    }

    /**
     * Relations a consumer may load via ?include=a,b (whitelist).
     *
     * @return list<string>
     */
    protected function allowedIncludes(): array {
        return [];
    }

    /**
     * Allowed page sizes for ?perPage=.
     *
     * @return list<int>
     */
    protected function pageSizes(): array {
        return [10, 15, 25, 50, 100];
    }

    protected function perPage(Request $request): int {
        $value = (int) $request->input('perPage', 15);

        return in_array($value, $this->pageSizes(), true) ? $value : 15;
    }

    /**
     * Base query. Default: all rows (public, cross-user data for the
     * API consumer). To serve only a specific owner/public set,
     * constrain it per controller here — this automatically also protects
     * future update/destroy.
     *
     * @return Builder<TModel>
     */
    protected function baseQuery(): Builder {
        // Larastan's dynamic return-type extension collapses ::query() to
        // Builder<Model> for the template type, so bridge TModel explicitly.
        /** @var Builder<TModel> $query */
        $query = $this->modelClass()::query();

        return $query;
    }

    /**
     * Filtered, sorted, paginated list — basis for index().
     *
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function resolveCollection(Request $request): LengthAwarePaginator {
        $search = trim((string) $request->input('search', ''));
        $columns = $this->searchableColumns();

        return $this->baseQuery()
            ->with([...$this->eagerLoad(), ...$this->requestedIncludes($request)])
            ->when($search !== '' && $columns !== [], function (Builder $query) use ($search, $columns): void {
                $query->where(function (Builder $q) use ($search, $columns): void {
                    foreach ($columns as $index => $column) {
                        $index === 0
                            ? $q->where($column, 'like', "%{$search}%")
                            : $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->orderBy($this->defaultOrderColumn())
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    /** Resolve a single record — basis for show(). 404 if not found. */
    protected function resolveModel(Request $request, string $id): Model {
        return $this->baseQuery()
            ->with([...$this->eagerLoad(), ...$this->requestedIncludes($request)])
            ->findOrFail($id);
    }

    /**
     * Parse ?include=a,b and filter it against the whitelist. Disallowed
     * includes are silently ignored.
     *
     * @return list<string>
     */
    protected function requestedIncludes(Request $request): array {
        $requested = array_filter(array_map('trim', explode(',', (string) $request->query('include', ''))));

        return array_values(array_intersect($requested, $this->allowedIncludes()));
    }
}
