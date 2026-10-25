<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory {
    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'icon' => fake()->randomElement(['Package', 'Shirt', 'Laptop', 'Home', 'Book', 'Dumbbell']),
            'color' => fake()->randomElement(['blue', 'green', 'purple', 'amber', 'sky']),
            'position' => 0,
        ];
    }
}
