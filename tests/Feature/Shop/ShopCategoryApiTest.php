<?php

namespace Tests\Feature\Shop;

use App\Models\Shop\ShopCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create user for auth
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_lists_active_root_categories_only()
    {
        $rootInactive = ShopCategory::create(['name' => 'Inactive Root', 'slug' => 'inactive', 'is_active' => false]);
        $rootActive = ShopCategory::create(['name' => 'Active Root', 'slug' => 'active', 'is_active' => true]);
        $child = ShopCategory::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $rootActive->id]);

        $response = $this->actingAs($this->user, 'api')
                         ->getJson('/api/v1/shop/categories');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonPath('data.0.id', $rootActive->id);
    }

    /** @test */
    public function it_lists_children_via_query_param()
    {
        $root = ShopCategory::create(['name' => 'Root', 'slug' => 'root']);
        $child1 = ShopCategory::create(['name' => 'C1', 'slug' => 'c1', 'parent_id' => $root->id]);
        $child2 = ShopCategory::create(['name' => 'C2', 'slug' => 'c2', 'parent_id' => $root->id]);

        $response = $this->actingAs($this->user, 'api')
                         ->getJson('/api/v1/shop/categories?parent_id=' . $root->id);

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_lists_children_via_endpoint()
    {
        $root = ShopCategory::create(['name' => 'Root', 'slug' => 'root']);
        $child = ShopCategory::create(['name' => 'C1', 'slug' => 'c1', 'parent_id' => $root->id]);

        $response = $this->actingAs($this->user, 'api')
                         ->getJson("/api/v1/shop/categories/{$root->id}/children");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.id', $child->id);
    }

    /** @test */
    public function it_returns_tree_structure()
    {
        $root = ShopCategory::create(['name' => 'Root', 'slug' => 'root']);
        $child = ShopCategory::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);
        $grandchild = ShopCategory::create(['name' => 'GC', 'slug' => 'gc', 'parent_id' => $child->id]);

        // Depth 2 should load Root -> Child (but maybe not grandchild fully inside resource unless we verify structure)
        // Adjusting expectation: Tree loads hierarchy. Resource collection usually recursive if defined.
        
        $response = $this->actingAs($this->user, 'api')
                         ->getJson('/api/v1/shop/categories/tree?depth=3');

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.id', $root->id)
                 ->assertJsonPath('data.0.children.0.id', $child->id)
                 ->assertJsonPath('data.0.children.0.children.0.id', $grandchild->id);
    }

    /** @test */
    public function it_returns_breadcrumb_on_show()
    {
        $root = ShopCategory::create(['name' => 'Root', 'slug' => 'root']);
        $child = ShopCategory::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);

        $response = $this->actingAs($this->user, 'api')
                         ->getJson("/api/v1/shop/categories/{$child->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $child->id)
                 ->assertJsonPath('data.breadcrumb.0.id', $root->id)
                 ->assertJsonPath('data.breadcrumb.0.slug', 'root');
    }

    /** @test */
    public function it_returns_404_for_missing_category()
    {
        $response = $this->actingAs($this->user, 'api')
                         ->getJson("/api/v1/shop/categories/9999");

        $response->assertStatus(404);
    }
}
