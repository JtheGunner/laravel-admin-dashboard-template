<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase {
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_aggregates_the_example_data(): void {
        $this->actingAs(User::factory()->create());
        Order::factory()->create(['status' => OrderStatus::Paid, 'total' => 100, 'placed_at' => now()]);
        Order::factory()->create(['status' => OrderStatus::Cancelled, 'total' => 50, 'placed_at' => now()]);
        Product::factory()->create(['stock' => 2]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('kpis.orders', 2)
                ->where('kpis.revenue', '100.00')
                ->has('ordersByMonth', 6)
                ->has('ordersByStatus', count(OrderStatus::cases()))
                ->has('lowStock', 1));
    }
}
