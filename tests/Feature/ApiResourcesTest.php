<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResourcesTest extends TestCase {
    use RefreshDatabase;

    private const TOKEN = 'test-token';

    protected function setUp(): void {
        parent::setUp();

        config(['api.token' => self::TOKEN]);
    }

    /**
     * @return array<string, string>
     */
    private function auth(): array {
        return ['Authorization' => 'Bearer ' . self::TOKEN];
    }

    public function test_every_resource_requires_the_token(): void {
        foreach (['customers', 'products', 'categories', 'users'] as $resource) {
            $this->getJson("/api/v1/$resource")->assertUnauthorized();
        }
    }

    public function test_customers_can_be_listed_and_searched(): void {
        Customer::factory()->create(['name' => 'Findable Person']);
        Customer::factory()->create(['name' => 'Someone Else']);

        $this->getJson('/api/v1/customers?search=findable', $this->auth())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Findable Person');
    }

    public function test_products_include_the_category_only_when_requested(): void {
        $product = Product::factory()->create(['category_id' => Category::factory()->create(['name' => 'Gadgets'])->id]);

        $this->getJson("/api/v1/products/{$product->id}", $this->auth())
            ->assertOk()
            ->assertJsonMissingPath('data.category.name');

        $this->getJson("/api/v1/products/{$product->id}?include=category", $this->auth())
            ->assertOk()
            ->assertJsonPath('data.category.name', 'Gadgets');
    }

    public function test_categories_are_ordered_by_position(): void {
        Category::factory()->create(['name' => 'Second', 'position' => 2]);
        Category::factory()->create(['name' => 'First', 'position' => 1]);

        $this->getJson('/api/v1/categories', $this->auth())
            ->assertOk()
            ->assertJsonPath('data.0.name', 'First');
    }
}
