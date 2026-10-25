<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder {
    /**
     * Seed orders with line items; the order total is the sum of its items.
     */
    public function run(): void {
        $customers = Customer::query()->get();
        $products = Product::query()->get();

        Order::factory()
            ->count(150)
            ->sequence(static fn ($sequence): array => ['customer_id' => $customers->random()->id])
            ->create()
            ->each(static function (Order $order) use ($products): void {
                $items = $products->random(random_int(1, 4))->map(
                    static fn (Product $product): OrderItem => OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'unit_price' => $product->price,
                    ]),
                );

                $order->update([
                    'total' => $items->sum(static fn (OrderItem $item): float => $item->quantity * (float) $item->unit_price),
                ]);
            });
    }
}
