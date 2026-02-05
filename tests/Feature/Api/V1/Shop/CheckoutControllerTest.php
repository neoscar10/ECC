<?php

namespace Tests\Feature\Api\V1\Shop;

use App\Models\User;
use App\Models\Shop\UserAddress;
use App\Models\Shop\ShopOrder;
use App\Services\Shop\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery\MockInterface;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    public function test_checkout_summary_success()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);
        
        // Mock Service
        $this->mock(CheckoutService::class, function (MockInterface $mock) use ($address) {
            $mock->shouldReceive('getCheckoutSummary')
                 ->once()
                 ->andReturn([
                     'currency' => 'INR',
                     'subtotal' => 100,
                     'shipping_fee' => 0,
                     'tax_amount' => 0,
                     'discount_amount' => 0,
                     'total_amount' => 100,
                     'items' => [],
                     'can_place_order' => true,
                 ]);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/shop/checkout/summary?shipping_address_id={$address->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Checkout summary generated.',
                     'data' => [
                         'total_amount' => 100
                     ]
                 ]);
    }

    public function test_checkout_summary_error_handling()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $this->mock(CheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCheckoutSummary')
                 ->once()
                 ->andThrow(new \Exception('Something went wrong', 500));
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/shop/checkout/summary?shipping_address_id={$address->id}");

        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Something went wrong',
                 ]);
    }

    public function test_place_order_success()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);
        
        // Mock Order Object (partial)
        $mockOrder = new ShopOrder([
            'id' => 1,
            'order_number' => 'ORD-123',
            'total_amount' => 150.00,
            'status' => 'pending_payment'
        ]);
        // Since it's a new instance not saved in this context if mapped via Resource it might fail if resource needs relations.
        // But ShopOrderResource typically just reads fields.
        // Let's rely on Mock return. 
        // We might need to mock the Resource or ensure the Service returns something the Resource accepts.
        // Ideally Service returns a real model or object.
        // If ShopOrderResource looks up relations, this might be flaky.
        // Let's return a simple mock object that duplicates the model structure.
        
        $this->mock(CheckoutService::class, function (MockInterface $mock) use ($address, $mockOrder) {
            $mock->shouldReceive('placeOrder')
                 ->once()
                 ->andReturn($mockOrder);
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $address->id,
                             'billing_same_as_shipping' => true
                         ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Order placed successfully.',
                     'data' => [
                         'order_number' => 'ORD-123'
                     ]
                 ]);
    }

    public function test_place_order_conflict_error()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $this->mock(CheckoutService::class, function (MockInterface $mock) {
            $mock->shouldReceive('placeOrder')
                 ->once()
                 ->andThrow(new \Exception('Product out of stock', 409));
        });

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $address->id
                         ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Product out of stock',
                 ]);
    }

    public function test_place_order_validation_invalid_address()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => 99999 // Non-existent
                         ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['shipping_address_id']]);
    }

    public function test_place_order_address_ownership()
    {
        $otherUser = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/checkout/place-order', [
                             'shipping_address_id' => $address->id
                         ]);

        $response->assertStatus(404); // Using findOrFail in controller
    }
}
