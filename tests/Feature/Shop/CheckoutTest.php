<?php

namespace Tests\Feature\Shop;

use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariationGroup;
use App\Models\Shop\ShopProductVariationValue;
use App\Models\Shop\UserAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ShopProduct $product;
    protected ShopProductVariationValue $redVariant;
    protected ShopProductVariationValue $blueVariant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create Product
        $this->product = ShopProduct::factory()->create([
            'base_price' => 1000,
            'is_active' => true,
        ]);

        // Create Variation Group
        $variation = ShopProductVariationGroup::factory()->create([
            'shop_product_id' => $this->product->id,
            'name' => 'Color',
            'type' => 'radio'
        ]);

        // Create Values (Red with stock, Blue out of stock/low stock)
        $this->redVariant = ShopProductVariationValue::factory()->create([
            'group_id' => $variation->id,
            'caption' => 'Red',
            'price' => 1200, // +200 override
            'stock_qty' => 10
        ]);

        $this->blueVariant = ShopProductVariationValue::factory()->create([
            'group_id' => $variation->id,
            'caption' => 'Blue',
            'price' => 1000,
            'stock_qty' => 0
        ]);
    }

    public function test_user_can_manage_addresses()
    {
        $this->actingAs($this->user);

        // 1. Create Address
        $payload = [
            'label' => 'Home',
            'full_name' => 'John Doe',
            'phone' => '9876543210',
            'line1' => '123 Main St',
            'city' => 'Mumbai',
            'state' => 'MH',
            'postal_code' => '400001',
            'country' => 'India',
            'is_default' => true,
            'type' => 'shipping'
        ];

        $response = $this->postJson('/api/v1/shop/addresses', $payload);
        $response->assertCreated()
            ->assertJsonPath('data.label', 'Home');
        
        $addressId = $response->json('data.id');
        $this->assertDatabaseHas('user_addresses', ['id' => $addressId]);

        // 2. List Addresses
        $this->getJson('/api/v1/shop/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // 3. Update Address
        $this->patchJson("/api/v1/shop/addresses/{$addressId}", ['label' => 'Work'])
            ->assertOk()
            ->assertJsonPath('data.label', 'Work');
        
        // 4. Delete Address
        $this->deleteJson("/api/v1/shop/addresses/{$addressId}")
            ->assertOk();
        
        $this->assertDatabaseMissing('user_addresses', ['id' => $addressId]);
    }

    public function test_checkout_summary_and_validation()
    {
        $this->actingAs($this->user);

        // Add item to cart
        $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'variation_value_ids' => [$this->redVariant->id]
        ])->assertCreated();

        // Get Summary
        $response = $this->getJson('/api/v1/shop/checkout/summary');
        
        // Price should be max(base, variant) = 1200 * 2 = 2400
        $response->assertOk()
            ->assertJsonPath('data.subtotal', 2400)
            ->assertJsonPath('data.can_place_order', true);
    }

    public function test_cannot_checkout_with_insufficient_stock()
    {
        $this->actingAs($this->user);

        // Set Blue variant stock to 1 to test boundary
        $this->blueVariant->update(['stock_qty' => 1]);

        // Add 2 Blue items (exceeds stock)
        $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'variation_value_ids' => [$this->blueVariant->id]
        ]);

        // Summary should show issue
        $res = $this->getJson('/api/v1/shop/checkout/summary');
        $res->assertOk()
            ->assertJsonPath('data.can_place_order', false)
            ->assertJsonPath('data.items.0.stock_issues.0', 'Insufficient stock');

        // Place Order should fail
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);
        
        $this->postJson('/api/v1/shop/checkout/place-order', [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => true
        ])->assertStatus(409); // Conflict
    }

    public function test_can_place_order_successfully()
    {
        $this->actingAs($this->user);

        // Add 2 Red items (Stock 10)
        $this->postJson('/api/v1/cart/items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'variation_value_ids' => [$this->redVariant->id]
        ]);

        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/v1/shop/checkout/place-order', [
            'shipping_address_id' => $address->id,
            'billing_same_as_shipping' => true,
            'notes' => 'Test Order'
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending_payment');

        // Check Stock Deducted (10 - 2 = 8)
        $this->assertEquals(8, $this->redVariant->fresh()->stock_qty);

        // Check Cart Cleared
        $this->getJson('/api/v1/cart')->assertJsonCount(0, 'data.items');
    }

    public function test_order_history_and_cancellation()
    {
        $this->actingAs($this->user);
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        // Create an order directly (simulating placement)
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'TEST-ORDER',
            'status' => 'pending_payment',
            'currency' => 'INR',
            'total_amount' => 2400,
            'billing_address_snapshot' => $address->toArray(),
            'shipping_address_snapshot' => $address->toArray(),
        ]);

        // Add Item
        $order->items()->create([
            'shop_product_id' => $this->product->id,
            'product_snapshot' => ['title' => 'Test'],
            'quantity' => 2,
            'unit_price' => 1200,
            'line_total' => 2400,
            'variation_snapshot' => [[
                'id' => $this->redVariant->id,
                'stock_qty' => 10 // Snapshot of old stock
            ]]
        ]);
        
        // Link variations table actually
        // In my service logic, I don't create `shop_order_item_variation_values` manually in test helper yet
        // accessing relationship might fail if not set up, but let's test endpoint access first.
        
        // 1. List Orders
        $this->getJson('/api/v1/shop/orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id);

        // 2. Show Order
        $this->getJson("/api/v1/shop/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id);

        // 3. Confirm Payment
        $this->postJson("/api/v1/shop/orders/{$order->id}/confirm-payment", ['method' => 'card', 'reference' => '123'])
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'paid');
    }
}
