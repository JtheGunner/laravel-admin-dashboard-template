<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryTreeTest extends TestCase {
    use RefreshDatabase;

    public function test_index_returns_the_whole_tree_unpaginated(): void {
        $this->actingAs(User::factory()->create());
        $root = Category::factory()->create(['position' => 1]);
        Category::factory()->count(20)->create(['parent_id' => $root->id]);

        $this->get(route('categories.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('items.data', 21)
                ->where('items.total', 21));
    }

    public function test_new_categories_are_appended_after_their_last_sibling(): void {
        $this->actingAs(User::factory()->create());
        $root = Category::factory()->create(['position' => 1]);
        Category::factory()->create(['parent_id' => $root->id, 'position' => 3]);

        $this->post(route('categories.store'), ['name' => 'Child', 'parent_id' => $root->id])
            ->assertRedirect();

        $this->assertSame(4, Category::where('name', 'Child')->firstOrFail()->position);
    }

    public function test_reorder_renumbers_the_siblings_of_one_parent(): void {
        $this->actingAs(User::factory()->create());
        $a = Category::factory()->create(['position' => 1]);
        $b = Category::factory()->create(['position' => 2]);

        $this->post(route('categories.reorder'), ['parentId' => null, 'ids' => [$b->id, $a->id]])
            ->assertRedirect();

        $this->assertSame(1, $b->fresh()->position);
        $this->assertSame(2, $a->fresh()->position);
    }

    public function test_a_category_cannot_be_its_own_parent(): void {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        $this->put(route('categories.update', $category->id), [
            'name' => $category->name,
            'parent_id' => $category->id,
        ])->assertSessionHasErrors('parent_id');
    }
}
