<?php

declare(strict_types=1);

namespace App\Services\Navigation;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Row counts shown as badges next to sidebar entries, keyed by the entry's path.
 * Served from cache so the sidebar never runs COUNT queries on every page load.
 */
final class NavigationCountsService {
    /** Cache lifetime in seconds. */
    public const int CACHE_TTL_SECONDS = 90;

    public static function cacheKey(): string {
        return 'nav_counts';
    }

    /**
     * Cached nav-item path → row count.
     *
     * @return array<string, int>
     */
    public function counts(): array {
        return Cache::remember(
            self::cacheKey(),
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->compute(),
        );
    }

    /**
     * Compute all counts fresh.
     *
     * @return array<string, int>
     */
    public function compute(): array {
        return [
            '/customers' => Customer::count(),
            '/products' => Product::count(),
            '/categories' => Category::count(),
            '/orders' => Order::where('status', OrderStatus::Pending)->count(),
            '/users' => User::count(),
        ];
    }
}
