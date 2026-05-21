<?php

namespace Tests\Feature\Api\Vault;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\Archive\ArchiveProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaultEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->withoutExceptionHandling();
        // Seed some tiers
        MembershipTier::factory()->create(['name' => 'Silver', 'level' => 1, 'has_vault_access' => false]);
        MembershipTier::factory()->create(['name' => 'Gold', 'level' => 2, 'has_vault_access' => true]);
    }

    public function test_vault_summary_returns_restricted_access_for_no_access_tier()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Silver');

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me/vault/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_access_vault', false)
            ->assertJsonPath('meta.code', 'VAULT_ACCESS_RESTRICTED')
            ->assertJsonStructure(['success', 'message', 'data' => ['access', 'can_access_vault', 'counts']]);
    }

    public function test_vault_summary_allowed_for_access_tier()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Item',
            'status' => 'locked',
            'locked_at' => now(),
            'currency' => 'INR',
            'price' => 1000,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me/vault/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.can_access_vault', true)
            ->assertJsonPath('data.counts.locked', 1);
    }

    public function test_vault_index_pagination_and_filters()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        // Create 3 items: 2 locked, 1 removed
        UserVaultItem::create(['user_id' => $user->id, 'source_type' => 'archive_product', 'source_id' => 1, 'item_title' => 'Item A', 'status' => 'locked', 'locked_at' => now(), 'currency' => 'INR', 'price' => 100]);
        UserVaultItem::create(['user_id' => $user->id, 'source_type' => 'auction', 'source_id' => 1, 'item_title' => 'Item B', 'status' => 'locked', 'locked_at' => now()->subDay(), 'currency' => 'INR', 'price' => 200]);
        UserVaultItem::create(['user_id' => $user->id, 'source_type' => 'archive_product', 'source_id' => 2, 'item_title' => 'Item C', 'status' => 'removed', 'locked_at' => now()->subDays(2), 'removed_at' => now(), 'currency' => 'INR', 'price' => 300]);

        // Default: locked only
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me/vault');
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Status: all
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me/vault?status=all');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Source: auction
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me/vault?status=all&source_type=auction');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.display.title', 'Item B');
            
        // Search
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/me/vault?q=Item A');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.display.title', 'Item A');
    }

    public function test_vault_show_returns_details()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        $item = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 999,
            'item_title' => 'Detail Item',
            'status' => 'locked',
            'locked_at' => now(),
            'currency' => 'INR',
            'price' => 5000,
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/me/vault/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.display.title', 'Detail Item')
            ->assertJsonStructure(['data' => ['media' => ['thumbnail', 'cover']]]);
    }

    public function test_profile_membership_includes_vault()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        $response = $this->actingAs($user, 'api')->getJson('api/v1/profile/membership');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['vault' => ['can_access', 'counts']]]);
    }

    public function test_vault_delivery_quote_successful_with_address_id()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        $product = ArchiveProduct::factory()->create([
            'weight_kg' => 1.5,
            'length_cm' => 12,
            'breadth_cm' => 12,
            'height_cm' => 12,
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        $address = \App\Models\Shop\UserAddress::create([
            'user_id' => $user->id,
            'label' => 'Home',
            'full_name' => 'John Doe',
            'phone' => '9999999999',
            'line1' => 'Test Street',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'postal_code' => '110055',
            'country' => 'India',
        ]);

        \Illuminate\Support\Facades\Config::set('shiprocket.pickup_pincode', '110001');

        $mock = $this->createMock(\App\Services\Shipping\Shiprocket\ShiprocketClient::class);
        $mockResponse = [
            'success' => true,
            'data' => [
                'available_courier_companies' => [
                    [
                        'courier_company_id' => '12',
                        'courier_name' => 'Blue Dart Air',
                        'rating' => '4.8',
                        'freight_charge' => '72.00',
                        'cod_charges' => '0.00',
                        'total_charge' => '72.00',
                        'etd' => '2026-05-20',
                        'estimated_delivery_days' => 3,
                        'cod' => 0,
                        'prepaid' => 1,
                    ]
                ]
            ]
        ];
        $mock->expects($this->exactly(2))
            ->method('get')
            ->willReturn($mockResponse);
        $this->app->instance(\App\Services\Shipping\Shiprocket\ShiprocketClient::class, $mock);

        // Test GET request
        $response = $this->actingAs($user, 'api')->getJson("/api/v1/me/vault/{$vaultItem->id}/delivery-quote?address_id={$address->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.delivery_fee', 72)
            ->assertJsonPath('data.selected_courier.courier_name', 'Blue Dart Air');

        // Test POST request
        $responsePost = $this->actingAs($user, 'api')->postJson("/api/v1/me/vault/{$vaultItem->id}/delivery-quote", [
            'address_id' => $address->id
        ]);

        $responsePost->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.delivery_fee', 72);
    }

    public function test_vault_delivery_quote_successful_with_postal_code()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        $product = ArchiveProduct::factory()->create([
            'weight_kg' => 1.5,
            'length_cm' => 12,
            'breadth_cm' => 12,
            'height_cm' => 12,
        ]);

        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        \Illuminate\Support\Facades\Config::set('shiprocket.pickup_pincode', '110001');

        $mock = $this->createMock(\App\Services\Shipping\Shiprocket\ShiprocketClient::class);
        $mockResponse = [
            'success' => true,
            'data' => [
                'available_courier_companies' => [
                    [
                        'courier_company_id' => '12',
                        'courier_name' => 'Blue Dart Air',
                        'rating' => '4.8',
                        'freight_charge' => '72.00',
                        'cod_charges' => '0.00',
                        'total_charge' => '72.00',
                        'etd' => '2026-05-20',
                        'estimated_delivery_days' => 3,
                        'cod' => 0,
                        'prepaid' => 1,
                    ]
                ]
            ]
        ];
        $mock->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);
        $this->app->instance(\App\Services\Shipping\Shiprocket\ShiprocketClient::class, $mock);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/me/vault/{$vaultItem->id}/delivery-quote?postal_code=110055");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.delivery_fee', 72)
            ->assertJsonPath('data.selected_courier.courier_name', 'Blue Dart Air');
    }

    public function test_vault_delivery_quote_validation_errors()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Gold');

        $product = ArchiveProduct::factory()->create();
        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        // Empty parameters
        $response = $this->actingAs($user, 'api')->getJson("/api/v1/me/vault/{$vaultItem->id}/delivery-quote");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address_id', 'postal_code']);
    }

    public function test_vault_delivery_quote_access_restricted()
    {
        $user = User::factory()->create();
        $this->assignTier($user, 'Silver'); // No vault access

        $product = ArchiveProduct::factory()->create();
        $vaultItem = UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => $product->id,
            'quantity' => 1,
            'status' => 'locked',
            'item_title' => 'Vault Item',
        ]);

        $response = $this->actingAs($user, 'api')->getJson("/api/v1/me/vault/{$vaultItem->id}/delivery-quote?postal_code=110055");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_access_vault', false);
    }

    private function assignTier($user, $tierName)
    {
        $tier = MembershipTier::where('name', $tierName)->first();
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }
}

