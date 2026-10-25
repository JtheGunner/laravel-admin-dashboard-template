<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database with demo users and example data.
     *
     * Idempotent for the demo users: existing accounts stay unchanged.
     */
    public function run(): void {
        foreach ([
            ['admin@example.com', 'Admin User', UserRole::Admin],
            ['editor@example.com', 'Editor User', UserRole::Editor],
            ['viewer@example.com', 'Viewer User', UserRole::Viewer],
        ] as [$email, $name, $role]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => 'password',
                    'role' => $role,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
