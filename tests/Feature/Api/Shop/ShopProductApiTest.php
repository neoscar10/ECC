<?php

namespace Tests\Feature\Api\Shop;

use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopTag;
use App\Models\Shop\ShopTagGroup;
use App\Models\Shop\ShopProductVariationGroup;
use App\Models\Shop\ShopProductVariationValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopProductApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_shop_products()
    {
        $product = ShopProduct::create([
            'title' => 'Test Product',
            'slug' => 'test-product',
            'base_price' => 100.00,
            'is_active' => true,
            'currency' => 'INR'
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.price_helpers.rule', 'MAX_SELECTED_VARIATION_PRICE');
    }

    public function test_can_filter_by_tags_and_logic()
    {
        $this->withoutExceptionHandling();
        // Tag Groups
        $brandGroup = ShopTagGroup::create(['name' => 'Brand', 'slug' => 'brand', 'sort_order' => 1]);
        $typeGroup = ShopTagGroup::create(['name' => 'Type', 'slug' => 'type', 'sort_order' => 2]);

        // Tags
        $nike = ShopTag::create(['name' => 'Nike', 'sort_order' => 1]);
        $adidas = ShopTag::create(['name' => 'Adidas', 'sort_order' => 2]);
        $bat = ShopTag::create(['name' => 'Bat', 'sort_order' => 1]);
        
        // Product 1: Nike Bat
        $p1 = ShopProduct::create(['title' => 'Nike Bat', 'slug' => 'nike-bat', 'base_price' => 100, 'is_active' => true]);
        $p1->tags()->attach($nike->id, ['shop_tag_group_id' => $brandGroup->id]);
        $p1->tags()->attach($bat->id, ['shop_tag_group_id' => $typeGroup->id]);

        // Product 2: Adidas Bat
        $p2 = ShopProduct::create(['title' => 'Adidas Bat', 'slug' => 'adidas-bat', 'base_price' => 100, 'is_active' => true]);
        $p2->tags()->attach($adidas->id, ['shop_tag_group_id' => $brandGroup->id]);
        $p2->tags()->attach($bat->id, ['shop_tag_group_id' => $typeGroup->id]);

        // Filter: Brand=Nike AND Type=Bat -> Should find P1 only
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/shop/products?tags[brand]={$nike->id}&tags[type]={$bat->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p1->id);
            
        // Filter: Brand=Adidas AND Type=Bat -> Should find P2
        $response2 = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/shop/products?tags[brand]={$adidas->id}&tags[type]={$bat->id}");

        $response2->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p2->id);
    }

    public function test_in_stock_filter_logic()
    {
        // Simple Product (Assumed In Stock)
        $simple = ShopProduct::create(['title' => 'Simple', 'slug' => 'simple', 'base_price' => 10, 'is_active' => true]);

        // Variable Product (Out of Stock)
        $varOut = ShopProduct::create(['title' => 'Var Out', 'slug' => 'var-out', 'base_price' => 10, 'is_active' => true]);
        $g1 = $varOut->variationGroups()->create(['name' => 'Size', 'presentation_type' => 'text']);
        $g1->values()->create(['caption' => 'S', 'price' => 10, 'stock_qty' => 0]);

        // Variable Product (In Stock)
        $varIn = ShopProduct::create(['title' => 'Var In', 'slug' => 'var-in', 'base_price' => 10, 'is_active' => true]);
        $g2 = $varIn->variationGroups()->create(['name' => 'Size', 'presentation_type' => 'text']);
        $g2->values()->create(['caption' => 'S', 'price' => 10, 'stock_qty' => 5]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products?in_stock=true');

        // Should return Simple and VarIn
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        
        $this->assertTrue($ids->contains($simple->id), 'Simple product should be in stock');
        $this->assertTrue($ids->contains($varIn->id), 'Product with stock > 0 should be visible');
        $this->assertFalse($ids->contains($varOut->id), 'Product with 0 stock should be hidden');
    }

    public function test_can_get_product_detail()
    {
        $product = ShopProduct::create(['title' => 'Detail P', 'slug' => 'detail-p', 'base_price' => 100, 'is_active' => true]);
        
        $group = $product->variationGroups()->create(['name' => 'Color', 'presentation_type' => 'color']);
        $val = $group->values()->create(['caption' => 'Red', 'price' => 100, 'stock_qty' => 10, 'is_default' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/shop/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.defaults.selected_values.' . $group->id, $val->id);
    }

    public function test_can_get_filters()
    {
        ShopCategory::create(['name' => 'Root', 'slug' => 'root', 'sort_order' => 1]);
        
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products/filters');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['categories', 'tag_groups', 'price_range', 'sort_options']]);
    }
    
    public function test_can_get_suggestions()
    {
        ShopProduct::create(['title' => 'Super Bat', 'slug' => 'super-bat', 'base_price' => 100]);
        
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products/suggestions?q=Super');
            
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.title', 'Super Bat');
    }
}
