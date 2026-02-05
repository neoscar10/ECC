<?php

namespace Tests\Feature\Api\V1\Shop;

use App\Models\User;
use App\Models\Shop\ShopOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShopOrderControllerTest extends TestCase
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

    public function test_user_can_list_orders()
    {
        ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-001',
            'status' => 'pending_payment',
            'payment_status' => 'unpaid', 
            'currency' => 'INR',
            'subtotal' => 100,
            'total_amount' => 100,
            'shipping_address_snapshot' => [],
            'placed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/v1/shop/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id', 
                             'order_number', 
                             'totals' => ['total_amount']
                         ]
                     ],
                     // Assuming standard pagination wrapped in resource collection usually preserves links/meta if using resource collection properly
                     // Or ApiResponse trait might wrap it. 
                     // If ShopOrderResource::collection($paginator) is passed to success($data), 
                     // and success() puts $data into 'data', then 'data' will be the resource collection.
                     // Standard Laravel Resource Collection on paginator results in: { data: [...], links: ..., meta: ... }
                     // If success() wraps that in { data: <resource_collection> }, we get { data: { data: [...], meta: ... } }
                     // Let's inspect the actual response structure in test or verify ApiResponse implementation.
                 ]);
    }

    public function test_user_can_show_order()
    {
        $order = ShopOrder::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-002',
            'status' => 'pending_payment',
             'payment_status' => 'unpaid',
            'currency' => 'INR',
            'subtotal' => 200,
            'total_amount' => 200,
            'shipping_address_snapshot' => [],
            'placed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/shop/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $order->id,
                         'order_number' => 'ORD-002'
                     ]
                 ]);
    }

    public function test_user_cannot_access_others_order()
    {
        $otherUser = User::factory()->create();
        $order = ShopOrder::create([
            'user_id' => $otherUser->id,
            'order_number' => 'ORD-OTHER',
            'status' => 'paid',
             'payment_status' => 'paid',
            'currency' => 'INR',
            'subtotal' => 50,
            'total_amount' => 50,
             'shipping_address_snapshot' => [],
             'placed_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/shop/orders/{$order->id}");

        $response->assertStatus(404);
    }
}
