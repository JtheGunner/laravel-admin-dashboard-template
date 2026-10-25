<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProductCrudTest extends TestCase {
    use RefreshDatabase;

    public function test_index_exposes_options_facets_and_computed_columns(): void {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create(['name' => 'Gadgets']);
        Product::factory()->create(['category_id' => $category->id, 'status' => ProductStatus::Active]);

        $this->get(route('products.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('products/Index')
                ->has('categoryOptions', 1)
                ->has('statusOptions', 3)
                ->has('facets', 2)
                ->where('items.data.0.category_name', 'Gadgets')
                ->where('items.data.0.status_label', 'Active'));
    }

    public function test_status_facet_narrows_the_list(): void {
        $this->actingAs(User::factory()->create());
        Product::factory()->create(['status' => ProductStatus::Active]);
        Product::factory()->create(['status' => ProductStatus::Draft]);

        $this->get(route('products.index', ['filter' => ['status' => ['draft']]]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('items.data', 1));
    }

    public function test_store_persists_an_uploaded_image(): void {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->post(route('products.store'), [
            'name' => 'Widget',
            'sku' => 'WID-0001',
            'price' => 19.9,
            'stock' => 5,
            'status' => 'active',
            'rating' => 4,
            'image_path' => UploadedFile::fake()->image('widget.png'),
        ])->assertRedirect();

        $product = Product::where('sku', 'WID-0001')->firstOrFail();
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_store_rejects_an_invalid_status(): void {
        $this->actingAs(User::factory()->create());

        $this->post(route('products.store'), [
            'name' => 'Widget',
            'sku' => 'WID-0002',
            'price' => 1,
            'stock' => 1,
            'status' => 'nonsense',
            'rating' => 3,
        ])->assertSessionHasErrors('status');
    }
}
