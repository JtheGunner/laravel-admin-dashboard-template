<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory {
    private const array ADJECTIVES = ['Compact', 'Ergonomic', 'Wireless', 'Premium', 'Classic', 'Lightweight', 'Smart', 'Durable'];

    private const array NOUNS = ['Headphones', 'Backpack', 'Desk Lamp', 'Keyboard', 'Water Bottle', 'Sneakers', 'Monitor', 'Notebook', 'Jacket', 'Speaker'];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->randomElement(self::ADJECTIVES) . ' ' . fake()->randomElement(self::NOUNS) . ' ' . fake()->unique()->numberBetween(100, 999),
            'sku' => strtoupper(fake()->unique()->bothify('??-####')),
            'description' => '<p>' . fake()->paragraph() . '</p>',
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 200),
            'status' => fake()->randomElement(ProductStatus::cases()),
            'is_featured' => fake()->boolean(20),
            'rating' => fake()->numberBetween(1, 5),
            'image_path' => null,
        ];
    }

    public function active(): static {
        return $this->state(['status' => ProductStatus::Active]);
    }
}
