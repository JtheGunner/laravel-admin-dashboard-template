<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @extends ApiResourceController<Customer>
 */
class CustomerApiController extends ApiResourceController {
    #[QueryParameter('search', description: 'Full-text search over name, email, company and city.', type: 'string', required: false, infer: false)]
    #[QueryParameter('perPage', description: 'Entries per page (10, 15, 25, 50, 100; otherwise 15).', type: 'integer', required: false, infer: false)]
    #[QueryParameter('page', description: 'Page number.', type: 'integer', required: false, infer: false)]
    public function index(Request $request): AnonymousResourceCollection {
        return CustomerResource::collection($this->resolveCollection($request));
    }

    public function show(Request $request, string $id): CustomerResource {
        return new CustomerResource($this->resolveModel($request, $id));
    }

    protected function modelClass(): string {
        return Customer::class;
    }

    protected function searchableColumns(): array {
        return ['name', 'email', 'company', 'city'];
    }
}
