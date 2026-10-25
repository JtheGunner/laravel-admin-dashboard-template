<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class UserApiTest extends TestCase {
    use RefreshDatabase;

    private const TOKEN = 'test-token';

    protected function setUp(): void {
        parent::setUp();

        config(['api.token' => self::TOKEN]);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function apiGet(string $uri, array $headers = []): TestResponse {
        return $this->getJson($uri, array_merge([
            'Authorization' => 'Bearer ' . self::TOKEN,
        ], $headers));
    }

    public function test_index_requires_a_token(): void {
        User::factory()->create();

        $this->getJson('/api/v1/users')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_index_rejects_a_wrong_token(): void {
        $this->apiGet('/api/v1/users', ['Authorization' => 'Bearer wrong-token'])
            ->assertUnauthorized();
    }

    public function test_index_returns_paginated_users_without_secrets(): void {
        User::factory()->count(3)->create();

        $response = $this->apiGet('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'role', 'avatar_url']],
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ])
            ->assertJsonMissing(['password' => true])
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.two_factor_secret')
            ->assertJsonMissingPath('data.0.remember_token');
    }

    public function test_show_returns_a_single_user(): void {
        $user = User::factory()->create(['name' => 'Jane']);

        $this->apiGet("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Jane');
    }

    public function test_show_returns_404_for_unknown_id(): void {
        $this->apiGet('/api/v1/users/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_avatar_is_an_absolute_url_when_set(): void {
        $user = User::factory()->create(['profile_photo_path' => 'avatars/jane.jpg']);

        $this->apiGet("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.avatar_url', fn (?string $url) => is_string($url) && str_starts_with($url, 'http'));
    }
}
