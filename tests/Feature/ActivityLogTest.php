<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ActivityLogTest extends TestCase {
    use RefreshDatabase;

    public function test_log_lists_entries_newest_first(): void {
        $this->actingAs(User::factory()->create());
        $old = ActivityLog::factory()->create(['created_at' => now()->subDay()]);
        $new = ActivityLog::factory()->create(['created_at' => now()]);

        $this->get(route('logs.activity'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('logs/activity/Index')
                ->where('items.data.0.id', $new->id)
                ->where('items.data.1.id', $old->id));
    }

    public function test_log_is_read_only(): void {
        $this->assertNull(Route::getRoutes()->getByName('logs.activity.store'));
        $this->assertNull(Route::getRoutes()->getByName('logs.activity.destroy'));
    }
}
