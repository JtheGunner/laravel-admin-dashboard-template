<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @extends ApiResourceController<Product>
 */
class ProductApiController extends ApiResourceController {
    #[QueryParameter('search', description: 'Full-text search over name and SKU.', type: 'string', required: false, infer: false)]
    #[QueryParameter('perPage', description: 'Entries per page (10, 15, 25, 50, 100; otherwise 15).', type: 'integer', required: false, infer: false)]
    #[QueryParameter('page', description: 'Page number.', type: 'integer', required: false, infer: false)]
    #[QueryParameter('include', description: 'Comma-separated relations: category.', type: 'string', required: false, infer: false)]
    public function index(Request $request): AnonymousResourceCollection {
        return ProductResource::collection($this->resolveCollection($request));
    }

    #[QueryParameter('include', description: 'Comma-separated relations (see index).', type: 'string', required: false, infer: false)]
    public function show(Request $request, string $id): ProductResource {
        return new ProductResource($this->resolveModel($request, $id));
    }

    protected function modelClass(): string {
        return Product::class;
    }

    protected function searchableColumns(): array {
        return ['name', 'sku'];
    }

    protected function allowedIncludes(): array {
        return ['category'];
    }
}
