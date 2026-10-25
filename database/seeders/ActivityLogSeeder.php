<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder {
    /**
     * Seed audit-log entries attributed to the existing users.
     */
    public function run(): void {
        $userIds = User::query()->pluck('id');

        ActivityLog::factory()
            ->count(80)
            ->sequence(static fn ($sequence): array => ['user_id' => $userIds->random()])
            ->create();
    }
}
