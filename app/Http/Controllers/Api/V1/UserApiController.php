<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @extends ApiResourceController<User>
 */
class UserApiController extends ApiResourceController {
    #[QueryParameter('search', description: 'Full-text search over name and email.', type: 'string', required: false, infer: false)]
    #[QueryParameter('perPage', description: 'Entries per page (10, 15, 25, 50, 100; otherwise 15).', type: 'integer', required: false, infer: false)]
    #[QueryParameter('page', description: 'Page number.', type: 'integer', required: false, infer: false)]
    public function index(Request $request): AnonymousResourceCollection {
        return UserResource::collection($this->resolveCollection($request));
    }

    public function show(Request $request, string $id): UserResource {
        return new UserResource($this->resolveModel($request, $id));
    }

    protected function modelClass(): string {
        return User::class;
    }

    protected function searchableColumns(): array {
        return ['name', 'email'];
    }
}
