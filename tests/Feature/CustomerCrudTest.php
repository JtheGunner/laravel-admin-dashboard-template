<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CustomerCrudTest extends TestCase {
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_customers_and_supports_search(): void {
        $this->actingAs(User::factory()->create());

        Customer::factory()->create(['name' => 'Alice Example']);
        Customer::factory()->create(['name' => 'Bob Builder']);

        $this->get(route('customers.index', ['search' => 'alice']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('customers/Index')
                ->has('items.data', 1)
                ->where('items.data.0.name', 'Alice Example'));
    }

    public function test_store_creates_a_customer(): void {
        $this->actingAs(User::factory()->create());

        $this->post(route('customers.store'), [
            'name' => 'Carol',
            'email' => 'carol@example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', ['email' => 'carol@example.com']);
    }

    public function test_store_validates_required_fields_and_unique_email(): void {
        $this->actingAs(User::factory()->create());
        Customer::factory()->create(['email' => 'taken@example.com']);

        $this->post(route('customers.store'), ['email' => 'taken@example.com'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_update_allows_keeping_the_own_email(): void {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create(['email' => 'keep@example.com']);

        $this->put(route('customers.update', $customer->id), [
            'name' => 'Renamed',
            'email' => 'keep@example.com',
        ])->assertRedirect();

        $this->assertSame('Renamed', $customer->fresh()->name);
    }

    public function test_destroy_soft_deletes_the_customer(): void {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();

        $this->delete(route('customers.destroy', $customer->id))->assertRedirect();

        $this->assertSoftDeleted($customer);
    }

    public function test_bulk_destroy_soft_deletes_the_selected_customers(): void {
        $this->actingAs(User::factory()->create());
        $customers = Customer::factory()->count(2)->create();

        $this->post(route('customers.bulk-destroy'), ['ids' => $customers->pluck('id')->all()])
            ->assertRedirect();

        $customers->each(fn (Customer $customer) => $this->assertSoftDeleted($customer));
    }
}
