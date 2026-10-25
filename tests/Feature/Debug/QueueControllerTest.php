<?php

declare(strict_types=1);

namespace Tests\Feature\Debug;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/debug/queue');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('debug/queue/Index')
                ->has('pendingJobs')
                ->has('failedJobs')
        );
    }

    public function test_queue_page_requires_authentication(): void
    {
        $this->get('/debug/queue')->assertRedirect('/login');
    }

    public function test_pending_jobs_are_listed(): void
    {
        $user = User::factory()->create();

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\TestJob', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->get('/debug/queue');

        $response->assertInertia(
            fn ($page) => $page
                ->component('debug/queue/Index')
                ->where('pendingJobs.0.display_name', 'App\\Jobs\\TestJob')
                ->where('pendingJobs.0.queue', 'default')
        );
    }
}
