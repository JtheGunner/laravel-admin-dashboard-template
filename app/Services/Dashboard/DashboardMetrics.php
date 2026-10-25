<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Support\DateTimeFormat;

use function array_map;
use function number_format;

/**
 * Read-model for the dashboard: purely read-only aggregations over the example
 * models. Each method returns a documented array shape ready for Inertia; the
 * controller only wires them together.
 */
class DashboardMetrics {
    private const int MONTHS = 6;

    private const int ACTIVITY_LIMIT = 15;

    private const int LOW_STOCK_THRESHOLD = 10;

    private const int LOW_STOCK_LIMIT = 6;

    /**
     * @return array{customers: int, products: int, orders: int, revenue: string}
     */
    public function kpis(): array {
        return [
            'customers' => Customer::count(),
            'products' => Product::count(),
            'orders' => Order::count(),
            'revenue' => number_format((float) Order::where('status', '!=', OrderStatus::Cancelled)->sum('total'), 2, '.', ','),
        ];
    }

    /**
     * Number of orders per month for the trailing months (oldest first).
     *
     * @return list<array{label: string, value: int}>
     */
    public function ordersByMonth(): array {
        $start = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $counts = Order::query()
            ->where('placed_at', '>=', $start)
            ->pluck('placed_at')
            ->countBy(static fn ($placedAt): string => $placedAt->format('Y-m'));

        $months = [];
        for ($i = 0; $i < self::MONTHS; $i++) {
            $month = $start->copy()->addMonths($i);
            $months[] = [
                'label' => $month->format('M'),
                'value' => (int) ($counts[$month->format('Y-m')] ?? 0),
            ];
        }

        return $months;
    }

    /**
     * @return list<array{title: string, color: string, count: int}>
     */
    public function ordersByStatus(): array {
        $counts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return array_map(
            static fn (OrderStatus $status): array => [
                'title' => $status->label(),
                'color' => $status->color(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ],
            OrderStatus::cases(),
        );
    }

    /**
     * Products that are running out of stock.
     *
     * @return array<int, array{id: int, name: string, sku: string, stock: int}>
     */
    public function lowStock(): array {
        return Product::query()
            ->where('stock', '<', self::LOW_STOCK_THRESHOLD)
            ->orderBy('stock')
            ->limit(self::LOW_STOCK_LIMIT)
            ->get(['id', 'name', 'sku', 'stock'])
            ->map(static fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $product->stock,
            ])
            ->values()
            ->all();
    }

    /**
     * Most recent audit-log entries, newest first.
     *
     * @return array<int, array{label: string, icon: string, color: string, subject: string, note: string|null, occurredAt: string, occurredAtHuman: string}>
     */
    public function recentActivity(): array {
        return ActivityLog::query()
            ->with('user')
            ->latest('created_at')
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(static fn (ActivityLog $entry): array => [
                'label' => ucfirst($entry->event),
                'icon' => match ($entry->event) {
                    'created' => 'Plus',
                    'updated' => 'Pencil',
                    'deleted' => 'Trash2',
                    default => 'Activity',
                },
                'color' => match ($entry->event) {
                    'created' => 'green',
                    'updated' => 'blue',
                    'deleted' => 'red',
                    default => 'slate',
                },
                'subject' => $entry->description,
                'note' => $entry->user?->name,
                'occurredAt' => DateTimeFormat::display($entry->created_at),
                'occurredAtHuman' => $entry->created_at->diffForHumans(),
            ])
            ->values()
            ->all();
    }
}
