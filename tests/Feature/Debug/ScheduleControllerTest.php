<?php

declare(strict_types=1);

namespace Tests\Feature\Debug;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/debug/schedule');

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('debug/schedule/Index')
                ->has('tasks')
        );
    }

    public function test_schedule_page_requires_authentication(): void
    {
        $this->get('/debug/schedule')->assertRedirect('/login');
    }

    public function test_run_rejects_unknown_command(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/debug/schedule/run', ['command' => 'php artisan not:registered']);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Unbekannter Befehl.']);
    }

    public function test_run_rejects_missing_command(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/debug/schedule/run', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['command']);
    }

    public function test_run_requires_authentication(): void
    {
        $this->post('/debug/schedule/run', ['command' => 'php artisan inspire'])
            ->assertRedirect('/login');
    }
}
