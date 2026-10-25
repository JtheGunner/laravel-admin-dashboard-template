<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Validation\Rule;

/**
 * Standard table example: search, server-side sort, pagination, bulk actions and
 * soft deletes (trash view).
 *
 * @extends ResourceController<Customer>
 */
class CustomerController extends ResourceController {
    protected function modelClass(): string {
        return Customer::class;
    }

    protected function page(): string {
        return 'customers/Index';
    }

    protected function singularLabel(): string {
        return 'Customer';
    }

    protected function searchableColumns(): array {
        return ['name', 'email', 'company', 'city'];
    }

    protected function defaultOrderColumn(): string {
        return 'name';
    }

    protected function rules(?int $id = null): array {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
