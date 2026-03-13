<?php

namespace Tests\Feature\Shop;

use App\Livewire\Shop\CheckoutPage;
use App\Livewire\Shop\OrderSuccessPage;
use App\Models\Shop\Cart;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\UserAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ShopProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->product = ShopProduct::factory()->create([
            'base_price' => 1000,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * Test that order/stock/cart remain untouched before the final payment success signal.
     */
    public function test_checkout_does_not_create_paid_order_before_payment_success()
    {
        $this->actingAs($this->user);
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        // Setup Cart
        $cart = Cart::create(['user_id' => $this->user->id, 'last_activity_at' => now()]);
        $cart->items()->create([
            'shop_product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'currency' => 'INR',
            'selection_signature' => 'none',
        ]);

        $this->assertEquals(0, \App\Models\Shop\ShopOrder::count());

        // View checkout page - should not trigger order creation
        Livewire::actingAs($this->user)
            ->test(CheckoutPage::class)
            ->assertSet('selectedAddressId', $address->id);

        $this->assertEquals(0, \App\Models\Shop\ShopOrder::count());
        $this->assertEquals(10, $this->product->fresh()->stock_qty); // Stock intact
    }

    /**
     * Test that successful dummy payment creates a paid order, deducts stock, and clears cart.
     */
    public function test_successful_dummy_payment_creates_or_finalizes_paid_order()
    {
        $this->actingAs($this->user);
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $cart = Cart::create(['user_id' => $this->user->id, 'last_activity_at' => now()]);
        $cart->items()->create([
            'shop_product_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'currency' => 'INR',
            'selection_signature' => 'none',
        ]);

        Livewire::actingAs($this->user)
            ->test(CheckoutPage::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder');

        // Verify order creation
        $this->assertEquals(1, \App\Models\Shop\ShopOrder::count());
        $order = \App\Models\Shop\ShopOrder::first();

        $this->assertEquals('paid', $order->status);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);

        // Verify stock deduction
        $this->assertEquals(8, $this->product->fresh()->stock_qty);

        // Verify cart cleared
        $this->assertEquals(0, $cart->items()->count());
    }

    /**
     * Test that entering the success page with an unpaid order results in a redirect.
     */
    public function test_order_success_page_requires_paid_order()
    {
        $this->actingAs($this->user);
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        // Create an UNPAID order manually
        $order = \App\Models\Shop\ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'FAKE-PENDING',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 1000,
            'total_amount' => 1000,
            'shipping_address_snapshot' => $address->toArray(),
            'billing_address_snapshot' => $address->toArray(),
            'placed_at' => now(),
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderSuccessPage::class, ['orderId' => $order->id])
            ->assertRedirect(route('shop.cart'));
    }

    /**
     * Test that even if payment is "initiated", if finalization fails (e.g. stock collision), no order is created.
     */
    public function test_failed_finalization_does_not_create_order_or_clear_cart()
    {
        $this->actingAs($this->user);
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $cart = Cart::create(['user_id' => $this->user->id, 'last_activity_at' => now()]);
        $cart->items()->create([
            'shop_product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 1000,
            'currency' => 'INR',
            'selection_signature' => 'none',
        ]);

        // Tank the stock right before placing
        $this->product->update(['stock_qty' => 2]);

        Livewire::actingAs($this->user)
            ->test(CheckoutPage::class)
            ->set('selectedAddressId', $address->id)
            ->call('placeOrder')
            ->assertSee('Checkout failed');

        $this->assertEquals(0, \App\Models\Shop\ShopOrder::count());
        $this->assertEquals(1, $cart->items()->count()); // Cart still intact
    }
}
