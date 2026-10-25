<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * User administration (admins only, see the `manage-users` gate).
 *
 * @extends ResourceController<User>
 */
class UserController extends ResourceController {
    protected function modelClass(): string {
        return User::class;
    }

    protected function page(): string {
        return 'users/Index';
    }

    protected function singularLabel(): string {
        return 'User';
    }

    protected function searchableColumns(): array {
        return ['name', 'email'];
    }

    protected function defaultOrderColumn(): string {
        return 'name';
    }

    protected function filters(): array {
        return [
            'role' => [
                'label' => 'Role',
                'options' => UserRole::options(),
            ],
        ];
    }

    protected function extraProps(): array {
        return [
            'roleOptions' => UserRole::options(),
        ];
    }

    protected function rules(?int $id = null): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => $id === null
                ? ['required', 'string', 'min:8']
                : ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * Do not overwrite an existing password with an empty one on update.
     */
    protected function mutateData(array $data, ?int $id = null): array {
        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        return $data;
    }
}
