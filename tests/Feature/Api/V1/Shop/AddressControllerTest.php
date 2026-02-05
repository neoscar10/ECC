<?php

namespace Tests\Feature\Api\V1\Shop;

use App\Models\User;
use App\Models\Shop\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    // We already have migration setup in normal test runs, usually RefreshDatabase or equivalent is used.
    // Assuming project uses TestCase which handles this or we rely on existing DB state (but RefreshDatabase is safer).
    // Given the constraints "Do NOT delete any data", implies we should be careful with main DB.
    // Feature tests usually use a separate test DB or transaction rollbacks.
    // Standard Laravel tests use RefreshDatabase trait.
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // specific setup if needed, but we just need a user
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    public function test_user_can_list_addresses()
    {
        UserAddress::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/v1/shop/addresses');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'full_name',
                             'line1',
                             'city',
                             'is_default'
                         ]
                     ]
                 ]);
    }

    public function test_user_can_create_address()
    {
        $payload = [
            'full_name' => 'John Doe',
            'line1' => '123 Main St',
            'city' => 'Metropolis',
            'country' => 'USA',
            'is_default' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/addresses', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Address created successfully.',
                     'data' => [
                         'full_name' => 'John Doe',
                         'is_default' => true,
                     ]
                 ]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $this->user->id,
            'line1' => '123 Main St',
        ]);
    }

    public function test_setting_default_address_updates_others()
    {
        // Create an existing default address
        $address1 = UserAddress::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true
        ]);

        // Create a new one and set as default
        $payload = [
            'full_name' => 'Jane Doe',
            'line1' => '456 Another St',
            'city' => 'Gotham',
            'country' => 'USA',
            'is_default' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/v1/shop/addresses', $payload);
        
        $response->assertStatus(200);

        // Verify address1 is no longer default
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address1->id,
            'is_default' => false,
        ]);

        // Verify new address is default
        $this->assertDatabaseHas('user_addresses', [
            'line1' => '456 Another St',
            'is_default' => true,
        ]);
    }

    public function test_user_can_show_address()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/shop/addresses/{$address->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $address->id,
                     ]
                 ]);
    }

    public function test_user_can_update_address()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $payload = [
            'full_name' => 'Updated Name',
            'city' => 'New City'
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->putJson("/api/v1/shop/addresses/{$address->id}", $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Address updated successfully.',
                 ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'full_name' => 'Updated Name',
        ]);
    }

    public function test_user_can_delete_address()
    {
        $address = UserAddress::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->deleteJson("/api/v1/shop/addresses/{$address->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Address deleted successfully.',
                 ]);

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_user_cannot_access_others_address()
    {
        $otherUser = User::factory()->create();
        $address = UserAddress::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/shop/addresses/{$address->id}");

        $response->assertStatus(404); // Or 403 depending on implementation, usually findOrFail gives 404 for model not found in scope
    }
}
