<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder {
    /**
     * Seed products spread across the existing categories.
     */
    public function run(): void {
        $categoryIds = Category::query()->pluck('id');

        Product::factory()
            ->count(40)
            ->sequence(static fn ($sequence): array => ['category_id' => $categoryIds->random()])
            ->create();
    }
}
