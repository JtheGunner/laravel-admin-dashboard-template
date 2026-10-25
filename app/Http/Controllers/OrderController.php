<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Expandable-rows and detail-page example: status badges, a related customer and a
 * dedicated "show" page with the order's line items.
 *
 * @extends ResourceController<Order>
 */
class OrderController extends ResourceController {
    protected function modelClass(): string {
        return Order::class;
    }

    protected function page(): string {
        return 'orders/Index';
    }

    protected function showPage(): ?string {
        return 'orders/Show';
    }

    protected function singularLabel(): string {
        return 'Order';
    }

    protected function searchableColumns(): array {
        return ['number', 'notes'];
    }

    protected function defaultOrderColumn(): string {
        return 'placed_at';
    }

    protected function defaultOrderDirection(): string {
        return 'desc';
    }

    protected function eagerLoad(): array {
        return ['customer', 'items.product'];
    }

    protected function transformRow(Model $model): array {
        /** @var Order $model */
        $data = $model->toArray();
        $data['customer_name'] = $model->customer?->name;
        $data['status_label'] = $model->status->label();
        $data['status_color'] = $model->status->color();
        $data['items'] = $model->items->map(static fn ($item): array => [
            'id' => $item->id,
            'product_name' => $item->product?->name,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'line_total' => number_format($item->quantity * (float) $item->unit_price, 2, '.', ''),
        ])->all();

        return $data;
    }

    protected function sortExpressions(): array {
        return [
            'customer_name' => function (Builder $query, $direction): void {
                $this->orderByRelated($query, Customer::class, 'customer_id', 'name', $direction);
            },
        ];
    }

    protected function filters(): array {
        return [
            'status' => [
                'label' => 'Status',
                'options' => OrderStatus::options(),
            ],
        ];
    }

    protected function extraProps(): array {
        return [
            'customerOptions' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (Customer $customer): array => ['value' => $customer->id, 'label' => $customer->name])
                ->all(),
            'statusOptions' => OrderStatus::options(),
        ];
    }

    protected function rules(?int $id = null): array {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'number' => ['required', 'string', 'max:20', Rule::unique('orders', 'number')->ignore($id)],
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'total' => ['required', 'numeric', 'min:0'],
            'placed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
