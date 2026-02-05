<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShopProductsApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_products()
    {
        ShopProduct::create([
            'title' => 'Test Bat',
            'slug' => 'test-bat',
            'base_price' => 1000,
            'description' => 'Desc',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'title', 'slug', 'base_price', 'currency', 'thumbnail_url', 'categories'
                    ]
                ]
            ]);
    }

    public function test_can_filter_products_by_category()
    {
        $cat1 = ShopCategory::create(['name' => 'Bats', 'slug' => 'bats']);
        $cat2 = ShopCategory::create(['name' => 'Balls', 'slug' => 'balls']);

        $p1 = ShopProduct::create([
            'title' => 'Bat 1', 'slug' => 'bat-1', 'base_price' => 100, 'is_active' => true
        ]);
        $p1->categories()->attach($cat1);

        $p2 = ShopProduct::create([
            'title' => 'Ball 1', 'slug' => 'ball-1', 'base_price' => 50, 'is_active' => true
        ]);
        $p2->categories()->attach($cat2);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products?category_ids=' . $cat1->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Bat 1']);
    }

    public function test_can_view_product_detail_with_variations()
    {
        $product = ShopProduct::create([
            'title' => 'Variant Bat',
            'slug' => 'variant-bat',
            'base_price' => 2000,
            'description' => 'Has sizes',
            'is_active' => true
        ]);

        $group = $product->variationGroups()->create([
            'name' => 'Size',
            'presentation_type' => 'text'
        ]);

        $group->values()->create([
            'caption' => 'SH',
            'price' => 2000, // Same as base
            'stock_qty' => 10,
            'is_default' => true
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/shop/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'variation_groups' => [
                        '*' => [
                            'name', 'values' => [
                                '*' => ['caption', 'price', 'stock_qty']
                            ]
                        ]
                    ]
                ]
            ])
            ->assertJsonFragment(['name' => 'Size', 'caption' => 'SH']);
    }
}
