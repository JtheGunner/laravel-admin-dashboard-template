<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory {
    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->boolean(60) ? fake()->company() : null,
            'city' => fake()->city(),
            'country' => fake()->countryCode(),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    public function trashed(): static {
        return $this->state(['deleted_at' => now()]);
    }
}
