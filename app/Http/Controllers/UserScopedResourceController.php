<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD base for resources bound to the logged-in user
 * ("My profile"). List, update and destroy are automatically limited to
 * own records via the constrained baseQuery; on create,
 * user_id is set.
 *
 * @template TModel of Model
 *
 * @extends ResourceController<TModel>
 */
abstract class UserScopedResourceController extends ResourceController {
    /** Foreign-key column linking the row to its owner (override per table). */
    protected function userColumn(): string {
        return 'user_id';
    }

    /**
     * @return Builder<TModel>
     */
    protected function baseQuery(): Builder {
        return parent::baseQuery()->where($this->userColumn(), Auth::id());
    }

    protected function mutateData(array $data, ?int $id = null): array {
        $data = parent::mutateData($data, $id);

        if ($id === null) {
            $data[$this->userColumn()] = Auth::id();
        }

        return $data;
    }
}
