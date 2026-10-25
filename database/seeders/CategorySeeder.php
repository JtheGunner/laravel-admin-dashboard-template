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
            'Electronics' => ['Laptop', 'Phones', 'Audio'],
            'Clothing' => ['Shirt', 'Footwear'],
            'Home' => ['Furniture', 'Kitchen'],
            'Books' => [],
        ];

        $position = 1;
        foreach ($tree as $name => $children) {
            $parent = Category::factory()->create(['name' => $name, 'position' => $position++]);

            foreach ($children as $index => $childName) {
                Category::factory()->create([
                    'parent_id' => $parent->id,
                    'name' => $childName,
                    'position' => $index + 1,
                ]);
            }
        }
    }
}
