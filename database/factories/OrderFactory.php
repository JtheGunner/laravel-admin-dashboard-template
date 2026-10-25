<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory {
    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'customer_id' => Customer::factory(),
            'number' => 'ORD-' . fake()->unique()->numerify('######'),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'total' => fake()->randomFloat(2, 10, 2000),
            'placed_at' => fake()->dateTimeBetween('-1 year'),
            'notes' => fake()->boolean(25) ? fake()->sentence() : null,
        ];
    }
}
