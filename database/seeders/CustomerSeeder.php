<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder {
    /**
     * Seed customers, a few of them soft-deleted to demo the trash view.
     */
    public function run(): void {
        Customer::factory()->count(50)->create();
        Customer::factory()->count(3)->trashed()->create();
    }
}
