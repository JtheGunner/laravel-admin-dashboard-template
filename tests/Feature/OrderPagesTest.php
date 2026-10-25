<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class OrderPagesTest extends TestCase {
    use RefreshDatabase;

    public function test_index_lists_orders_with_customer_and_status(): void {
        $this->actingAs(User::factory()->create());
        Order::factory()->create();

        $this->get(route('orders.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('orders/Index')
                ->has('items.data', 1)
                ->has('items.data.0.customer_name')
                ->has('items.data.0.status_color'));
    }

    public function test_show_renders_the_order_with_its_line_items(): void {
        $this->actingAs(User::factory()->create());
        $order = Order::factory()->create();
        OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2, 'unit_price' => 10]);

        $this->get(route('orders.show', $order->id))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('orders/Show')
                ->where('item.id', $order->id)
                ->has('item.items', 1)
                ->where('item.items.0.line_total', '20.00'));
    }

    public function test_show_returns_404_for_an_unknown_order(): void {
        $this->actingAs(User::factory()->create());

        $this->get(route('orders.show', 999999))->assertNotFound();
    }
}
