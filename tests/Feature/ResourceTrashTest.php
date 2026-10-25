<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Covers the generic trash feature of the ResourceController: display of deleted
 * entries, restore and permanent delete, including the bulk variants.
 */
class ResourceTrashTest extends TestCase {
    use RefreshDatabase;

    public function test_index_exposes_soft_delete_props_and_trashed_count(): void {
        $this->actingAs(User::factory()->create());

        Customer::factory()->create();
        Customer::factory()->create()->delete();

        $this->get(route('customers.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('softDeletes', true)
                ->where('trashedCount', 1)
                ->where('filters.trashed', false)
                ->has('items.data', 1));
    }

    public function test_index_trashed_returns_only_deleted_rows(): void {
        $this->actingAs(User::factory()->create());

        Customer::factory()->create();
        $deleted = Customer::factory()->create();
        $deleted->delete();

        $this->get(route('customers.index', ['trashed' => 1]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.trashed', true)
                ->has('items.data', 1)
                ->where('items.data.0.id', $deleted->id));
    }

    public function test_restore_brings_back_a_soft_deleted_row(): void {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();
        $customer->delete();

        $this->post(route('customers.restore', $customer->id))
            ->assertRedirect();

        $this->assertNull($customer->fresh()->deleted_at);
    }

    public function test_force_destroy_removes_the_row_permanently(): void {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();
        $customer->delete();

        $this->delete(route('customers.force-destroy', $customer->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_bulk_restore_and_bulk_force_destroy(): void {
        $this->actingAs(User::factory()->create());

        $a = Customer::factory()->create();
        $b = Customer::factory()->create();
        $a->delete();
        $b->delete();

        $this->post(route('customers.bulk-restore'), ['ids' => [$a->id]])
            ->assertRedirect();
        $this->assertNull($a->fresh()->deleted_at);
        $this->assertNotNull($b->fresh()->deleted_at);

        $this->post(route('customers.bulk-force-destroy'), ['ids' => [$b->id]])
            ->assertRedirect();
        $this->assertDatabaseMissing('customers', ['id' => $b->id]);
    }
}
