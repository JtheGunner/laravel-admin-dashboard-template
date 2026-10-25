<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @extends ApiResourceController<Category>
 */
class CategoryApiController extends ApiResourceController {
    #[QueryParameter('search', description: 'Search over the category name.', type: 'string', required: false, infer: false)]
    #[QueryParameter('perPage', description: 'Entries per page (10, 15, 25, 50, 100; otherwise 15).', type: 'integer', required: false, infer: false)]
    #[QueryParameter('page', description: 'Page number.', type: 'integer', required: false, infer: false)]
    public function index(Request $request): AnonymousResourceCollection {
        return CategoryResource::collection($this->resolveCollection($request));
    }

    public function show(Request $request, string $id): CategoryResource {
        return new CategoryResource($this->resolveModel($request, $id));
    }

    protected function modelClass(): string {
        return Category::class;
    }

    protected function searchableColumns(): array {
        return ['name'];
    }

    protected function defaultOrderColumn(): string {
        return 'position';
    }
}
