<?php

namespace Tests\Feature\Shop;

use App\Models\Shop\ShopTag;
use App\Models\Shop\ShopTagGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopTagsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming public endpoints or simple auth. 
        // Based on Categories test, we might need auth.
        // Categories test used: $this->actingAs($user, 'api');
    }

    public function test_can_list_active_tag_groups()
    {
        ShopTagGroup::create(['name' => 'Active Group', 'slug' => 'active-group', 'is_active' => true, 'sort_order' => 1]);
        ShopTagGroup::create(['name' => 'Inactive Group', 'slug' => 'inactive-group', 'is_active' => false, 'sort_order' => 2]);

        $response = $this->getJson(route('api.v1.shop.tags.groups.index'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'active-group');
    }

    public function test_can_list_tags_by_group()
    {
        $group = ShopTagGroup::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        ShopTag::create(['group_id' => $group->id, 'name' => 'Nike', 'slug' => 'nike', 'is_active' => true]);
        ShopTag::create(['group_id' => $group->id, 'name' => 'Adidas', 'slug' => 'adidas', 'is_active' => true]);
        
        // Another group tag
        $group2 = ShopTagGroup::create(['name' => 'Size', 'slug' => 'size', 'is_active' => true]);
        ShopTag::create(['group_id' => $group2->id, 'name' => 'XL', 'slug' => 'xl', 'is_active' => true]);

        $response = $this->getJson(route('api.v1.shop.tags.index', ['group_id' => $group->id]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'adidas') // Alphabetical by default
            ->assertJsonPath('data.1.slug', 'nike');
    }

    public function test_can_get_group_detail()
    {
        $group = ShopTagGroup::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);

        $response = $this->getJson(route('api.v1.shop.tags.groups.show', $group->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $group->id)
            ->assertJsonPath('data.slug', 'brand');
    }

    public function test_can_get_tag_detail()
    {
        $group = ShopTagGroup::create(['name' => 'Brand', 'slug' => 'brand', 'is_active' => true]);
        $tag = ShopTag::create(['group_id' => $group->id, 'name' => 'Nike', 'slug' => 'nike', 'is_active' => true]);

        $response = $this->getJson(route('api.v1.shop.tags.show', $tag->id));

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.slug', 'nike')
            ->assertJsonPath('data.group_name', 'Brand');
    }

    public function test_404_for_non_existent_group_or_tag()
    {
        $this->getJson(route('api.v1.shop.tags.groups.show', 999))
            ->assertStatus(404);

        $this->getJson(route('api.v1.shop.tags.show', 999))
            ->assertStatus(404);
    }
}
