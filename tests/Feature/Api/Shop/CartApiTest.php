<?php

namespace Tests\Feature\Api\Shop;

use App\Models\Shop\Cart;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariationGroup;
use App\Models\Shop\ShopProductVariationValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $simpleProduct;
    protected $varProduct;
    protected $colorGroup;
    protected $colorRed;
    protected $colorBlue;

    protected function setUp(): void
    {
        parent::setUp();

        // Create User & Auth
        $this->user = User::factory()->create();
        // Mock JWT or just use actingAs if strict JWT middleware not enforced in test env
        // Assuming 'auth:api' uses Passport or Sanctum or Tymon. 
        // If Tymon JWT, actingAs($user, 'api') usually works.
        $this->actingAs($this->user, 'api');

        // Create Simple Product
        $this->simpleProduct = ShopProduct::create([
            'title' => 'Simple Bat',
            'slug' => 'simple-bat',
            'base_price' => 100.00,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        // Create Variable Product
        $this->varProduct = ShopProduct::create([
            'title' => 'Jersey',
            'slug' => 'jersey',
            'base_price' => 500.00,
            'currency' => 'INR',
            'is_active' => true,
        ]);

        $this->colorGroup = ShopProductVariationGroup::create([
            'shop_product_id' => $this->varProduct->id,
            'name' => 'Color',
            'sort_order' => 1,
        ]);

        $this->colorRed = ShopProductVariationValue::create([
            'group_id' => $this->colorGroup->id,
            'caption' => 'Red',
            'price' => 500.00,
            'stock_qty' => 10,
            'is_default' => true,
            'sort_order' => 1,
        ]);

        $this->colorBlue = ShopProductVariationValue::create([
            'group_id' => $this->colorGroup->id,
            'caption' => 'Blue',
            'price' => 550.00, // Higher price
            'stock_qty' => 5,
            'is_default' => false,
            'sort_order' => 2,
        ]);
    }

    public function test_get_cart_returns_structure()
    {
        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'totals' => ['subtotal', 'total_items', 'currency'],
                    'items' => [],
                    'is_abandoned'
                ]
            ]);
    }

    public function test_add_item_simple()
    {
        $payload = [
            'product_id' => $this->simpleProduct->id,
            'quantity' => 2,
        ];

        $response = $this->postJson('/api/v1/cart/items', $payload);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.totals.total_items'));
        $this->assertEquals(200.00, $response->json('data.totals.subtotal'));
    }

    public function test_add_item_with_default_variation()
    {
        // No variations sent -> should pick Red (Default)
        $payload = [
            'product_id' => $this->varProduct->id,
            'quantity' => 1,
        ];

        $response = $this->postJson('/api/v1/cart/items', $payload);

        $response->assertStatus(200);
        $item = $response->json('data.items.0');
        $this->assertEquals('Red', $item['selected_variations'][0]['value_label']);
        $this->assertEquals(500.00, $item['unit_price']);
    }

    public function test_add_item_with_specific_variation()
    {
        // Pick Blue (Higher Price)
        $payload = [
            'product_id' => $this->varProduct->id,
            'quantity' => 1,
            'variation_value_ids' => [$this->colorBlue->id]
        ];

        $response = $this->postJson('/api/v1/cart/items', $payload);

        $response->assertStatus(200);
        $item = $response->json('data.items.0');
        $this->assertEquals('Blue', $item['selected_variations'][0]['value_label']);
        $this->assertEquals(550.00, $item['unit_price']); // Max(500, 550) = 550
    }

    public function test_add_item_stock_validation()
    {
        // Blue has stock 5. Request 6.
        $payload = [
            'product_id' => $this->varProduct->id,
            'quantity' => 6,
            'variation_value_ids' => [$this->colorBlue->id]
        ];

        $response = $this->postJson('/api/v1/cart/items', $payload);

        $response->assertStatus(409); // Conflict
    }

    public function test_merge_items()
    {
        // Add 2 Red
        $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->varProduct->id,
            'quantity' => 2,
            'variation_value_ids' => [$this->colorRed->id]
        ]);

        // Add 3 Red
        $response = $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->varProduct->id,
            'quantity' => 3,
            'variation_value_ids' => [$this->colorRed->id]
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items')); // Merged
        $this->assertEquals(5, $response->json('data.items.0.quantity'));
    }

    public function test_update_item_quantity()
    {
        // Setup cart
        $this->cartService()->addItem($this->user, $this->simpleProduct->id, 1);
        $cart = $this->cartService()->getCart($this->user);
        $itemId = $cart->items->first()->id;

        $response = $this->patchJson("/api/v1/cart/items/{$itemId}", [
            'quantity' => 5
        ]);

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.items.0.quantity'));
    }

    public function test_update_item_variation_merge()
    {
        // Setup: Cart has 1 Red and 1 Blue
        $this->cartService()->addItem($this->user, $this->varProduct->id, 1, [$this->colorRed->id]);
        $this->cartService()->addItem($this->user, $this->varProduct->id, 1, [$this->colorBlue->id]);
        
        $cart = $this->cartService()->getCart($this->user);
        $blueItem = $cart->items()->where('selection_signature', $this->colorBlue->id)->first(); // Signature is likely "ID" if only 1 var

        // Update Blue Item -> Change to Red
        // Should merge with existing Red item
        $response = $this->patchJson("/api/v1/cart/items/{$blueItem->id}", [
            'variation_value_ids' => [$this->colorRed->id]
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.items')); // Merged into Red
        $this->assertEquals(2, $response->json('data.items.0.quantity'));
        $this->assertEquals('Red', $response->json('data.items.0.selected_variations.0.value_label'));
    }

    public function test_abandoned_cart_logic()
    {
        Config::set('cart.abandoned_minutes', 30);
        
        $cart = $this->cartService()->getCart($this->user);
        $this->cartService()->addItem($this->user, $this->simpleProduct->id, 1);
        
        // Active
        $this->assertFalse($cart->fresh()->is_abandoned);

        // Travel time
        $cart->update(['last_activity_at' => now()->subMinutes(31)]);
        
        $response = $this->getJson('/api/v1/cart');
        $this->assertTrue($response->json('data.is_abandoned'));
    }

    protected function cartService()
    {
        return app(\App\Services\Shop\CartService::class);
    }
}
