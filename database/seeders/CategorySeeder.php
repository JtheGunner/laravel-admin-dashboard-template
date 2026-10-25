<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder {
    /**
     * Seed a small category tree.
     */
    public function run(): void {
        $tree = [
            'Electronics' => ['icon' => 'Laptop', 'color' => 'blue', 'children' => ['Laptops', 'Phones', 'Audio']],
            'Clothing' => ['icon' => 'Shirt', 'color' => 'purple', 'children' => ['Shirts', 'Footwear']],
            'Home' => ['icon' => 'Home', 'color' => 'green', 'children' => ['Furniture', 'Kitchen']],
            'Books' => ['icon' => 'Book', 'color' => 'amber', 'children' => []],
        ];

        $position = 1;
        foreach ($tree as $name => $definition) {
            $parent = Category::factory()->create([
                'name' => $name,
                'icon' => $definition['icon'],
                'color' => $definition['color'],
                'position' => $position++,
            ]);

            foreach ($definition['children'] as $index => $childName) {
                Category::factory()->create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'icon' => $definition['icon'],
                    'color' => $definition['color'],
                    'position' => $index + 1,
                ]);
            }
        }
    }
}
