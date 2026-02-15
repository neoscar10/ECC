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
