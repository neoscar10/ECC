<?php

namespace Tests\Feature;

use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuctionAutoBidValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $tier;
    protected $user;
    protected $lot;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);

        $this->tier = MembershipTier::create([
            'name' => 'Gold', 
            'level' => 3, 
            'price' => 300, 
            'code' => 'gold',
            'is_auto_bidding_enabled' => true
        ]);
        
        $this->user = User::factory()->create();
        $this->assignTier($this->user, $this->tier);

        // Standard Clear Lot
        $this->lot = AuctionLot::create([
             'lot_no' => 'LOT-VAL-01',
             'title' => 'Validation Lot',
             'description' => 'Desc',
             'starting_price' => 1000,
             'min_increment' => 100, // Explicit
             'restriction_mode' => 'public', // Open for access checks
             'status' => 'live',
             'ends_at' => now()->addDay(),
             'currency' => 'INR'
        ]);
    }

    private function assignTier(User $user, MembershipTier $tier)
    {
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
            'approved_at' => now(),
            'approved_by' => $user->id
        ]);
        $user->load('currentMembership.membershipTier');
    }

    /** @test */
    public function increment_amount_must_meet_min_increment()
    {
        // Lot min_inc = 100
        // Request = 50
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/auto-bid", [
            'max_bid' => 5000, 
            'increment_amount' => 50
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['increment_amount']);
        $response->assertJsonFragment(['Increment Amount must be at least INR 100.']);
    }

    /** @test */
    public function max_bid_must_meet_starting_price_if_no_bids()
    {
        // No bids. Starting Price = 1000.
        // Request Max = 900.
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/auto-bid", [
            'max_bid' => 900, 
            'increment_amount' => 100
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['max_bid']);
        // Threshold = Starting Price (1000)
        $response->assertJsonFragment(['Max Bid must be at least INR 1000.']);
    }

    /** @test */
    public function max_bid_must_exceed_current_bid_plus_increment()
    {
        // Place a bid first
        $this->lot->update(['current_highest_bid' => 2000]);
        // Min Increment 100.
        // Required Max = 2000 + 100 = 2100.
        
        // Request 2050
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/auto-bid", [
            'max_bid' => 2050, 
            'increment_amount' => 100
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['max_bid']);
        $response->assertJsonFragment(['Max Bid must be at least INR 2100.']);
    }
    
    /** @test */
    public function access_denied_if_view_mode_not_clear()
    {
        // Make lot blurred for user
        $this->lot->update([
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $this->tier->id, // User has access
            'blur_enabled' => true,
            'blur_strategy' => 'hierarchical',
            'min_clear_view_tier_id' => $this->tier->id + 1 // Blurry
        ]);
        
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/auto-bid", [
            'max_bid' => 5000, 
            'increment_amount' => 100
        ]);
        
        $response->assertStatus(403);
        $response->assertJsonFragment(['Access Denied']);
    }

    /** @test */
    public function access_denied_if_tier_disabled()
    {
        $this->tier->update(['is_auto_bidding_enabled' => false]);
        $this->user->refresh();
        $this->user->load('currentMembership.membershipTier');
        
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/auto-bid", [
            'max_bid' => 5000, 
            'increment_amount' => 100
        ]);
        
        $response->assertStatus(403);
        $response->assertJsonFragment(['Auto-bidding is not enabled for your Membership Tier']);
    }

    /** @test */
    public function successful_configuration()
    {
        $response = $this->actingAs($this->user, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/auto-bid", [
            'max_bid' => 5000, 
            'increment_amount' => 100
        ]);
        
        $response->assertOk();
        $response->assertJson(['message' => 'Auto-bid configured successfully.']);
        
        $this->assertDatabaseHas('auction_auto_bids', [
            'auction_lot_id' => $this->lot->id,
            'user_id' => $this->user->id,
            'max_bid' => 5000,
            'increment_amount' => 100
        ]);
    }
}
