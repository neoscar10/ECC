<?php

namespace Tests\Feature\Shop;

use App\Models\Shop\Cart;
use App\Models\Shop\CartItem;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\UserAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SimpleProductStockTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->address = UserAddress::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_decrements_simple_product_stock_on_successful_order()
    {
        // 1. Create Simple Product
        $product = ShopProduct::factory()->create([
            'base_price' => 100,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        // 2. Add to Cart (No variations)
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'shop_product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'currency' => 'INR',
        ]);

        // 3. Place Order via API
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/shop/checkout/place-order', [
                'shipping_address_id' => $this->address->id,
                'billing_address_id' => $this->address->id,
                'payment_method' => 'cod', // mock
            ]);

        $response->assertStatus(201);
        
        // 4. Verify Stock Decrement
        $this->assertDatabaseHas('shop_products', [
            'id' => $product->id,
            'stock_qty' => 8, // 10 - 2
        ]);
    }

    /** @test */
    public function it_fails_when_simple_product_has_insufficient_stock()
    {
        // 1. Create Low Stock Product
        $product = ShopProduct::factory()->create([
            'base_price' => 100,
            'stock_qty' => 1, 
        ]);

        // 2. Add to Cart (Qty 2)
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'shop_product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100,
            'currency' => 'INR',
        ]);

        // 3. Place Order
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/shop/checkout/place-order', [
                'shipping_address_id' => $this->address->id,
                'billing_address_id' => $this->address->id,
            ]);

        // 4. Expect Error (409 Conflict logic was used in service, but Request might wrap it or global handler)
        // Service threw Exception code 409. 
        // Our global handler usually maps 409 to JSON error.
        $response->assertStatus(409)
            ->assertJsonFragment(['message' => "Insufficient stock for {$product->title}. Requested: 2, Available: 1"]);
            
        // 5. Verify Stock Unchanged
        $this->assertDatabaseHas('shop_products', [
            'id' => $product->id,
            'stock_qty' => 1,
        ]);
    }
}
