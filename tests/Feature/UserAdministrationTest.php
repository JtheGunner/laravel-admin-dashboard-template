<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdministrationTest extends TestCase {
    use RefreshDatabase;

    public function test_non_admins_cannot_open_user_administration(): void {
        $this->actingAs(User::factory()->editor()->create())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admins_can_create_a_user_with_a_role(): void {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('users.store'), [
            'name' => 'New Editor',
            'email' => 'new.editor@example.com',
            'password' => 'password123',
            'role' => 'editor',
        ])->assertRedirect();

        $this->assertSame(UserRole::Editor, User::where('email', 'new.editor@example.com')->firstOrFail()->role);
    }

    public function test_updating_without_a_password_keeps_the_existing_one(): void {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create();
        $hash = $user->password;

        $this->put(route('users.update', $user->id), [
            'name' => 'Renamed',
            'email' => $user->email,
            'role' => 'viewer',
        ])->assertRedirect();

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_role_must_be_a_known_value(): void {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('users.store'), [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'password123',
            'role' => 'superuser',
        ])->assertSessionHasErrors('role');
    }
}
