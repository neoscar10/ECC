<?php

namespace Tests\Feature\Auctions;

use App\Models\Auctions\AuctionAutoBid;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuctionTerminalProxyResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected $tier;
    protected $u1;
    protected $u2;
    protected $u3;
    protected $lot;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);

        $this->tier = MembershipTier::create([
            'name' => 'Sovereign', 
            'level' => 10, 
            'code' => 'sovereign',
            'is_auto_bidding_enabled' => true
        ]);
        
        $this->u1 = User::factory()->create();
        $this->u2 = User::factory()->create();
        $this->u3 = User::factory()->create();

        $this->assignTier($this->u1, $this->tier);
        $this->assignTier($this->u2, $this->tier);
        $this->assignTier($this->u3, $this->tier);

        $this->lot = AuctionLot::create([
             'lot_no' => 'LOT-TERM-01',
             'title' => 'Terminal Test Lot',
             'starting_price' => 1000,
             'min_increment' => 100,
             'restriction_mode' => 'public',
             'status' => 'live',
             'ends_at' => now()->addSeconds(30), // Within 120s window
             'anti_sniping_enabled' => true,
             'max_extensions' => 3,
             'extensions_used' => 3, // Exhausted
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
    }

    /** @test */
    public function manual_bid_triggers_immediate_auto_bid_escalation_in_terminal_state()
    {
        // 1. Setup Auto-Bids
        // U1 has max 5000
        AuctionAutoBid::create([
            'auction_lot_id' => $this->lot->id,
            'user_id' => $this->u1->id,
            'max_bid' => 5000,
            'increment_amount' => 100,
            'is_enabled' => true
        ]);

        // 2. U2 places a manual bid of 1100
        $response = $this->actingAs($this->u2, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/bid", [
            'amount' => 1100
        ]);

        $response->assertStatus(200);

        // 3. Verify U1 immediately counter-bid
        // U1 should win at 1100 + 100 = 1200
        $this->lot->refresh();
        $this->assertEquals($this->u1->id, $this->lot->winner_user_id);
        $this->assertEquals(1200, (float)$this->lot->current_highest_bid);

        // Check bid history
        $this->assertDatabaseHas('auction_bids', [
            'user_id' => $this->u2->id,
            'amount' => 1100,
            'is_auto' => false
        ]);
        $this->assertDatabaseHas('auction_bids', [
            'user_id' => $this->u1->id,
            'amount' => 1200,
            'is_auto' => true
        ]);
    }

    /** @test */
    public function multiple_auto_bidders_resolve_synchronously_in_terminal_state()
    {
        // 1. Setup Auto-Bids
        // U1 has max 5000
        AuctionAutoBid::create([
             'auction_lot_id' => $this->lot->id,
             'user_id' => $this->u1->id,
             'max_bid' => 5000,
             'increment_amount' => 100,
             'is_enabled' => true
        ]);
        // U2 has max 7000
        AuctionAutoBid::create([
             'auction_lot_id' => $this->lot->id,
             'user_id' => $this->u2->id,
             'max_bid' => 7000,
             'increment_amount' => 100,
             'is_enabled' => true
        ]);

        // 2. U3 places manual bid of 1500
        $this->actingAs($this->u3, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/bid", [
            'amount' => 1500
        ]);

        // 3. Terminal resolution should happen:
        // Highest is U2 (7000), Second is U1 (5000).
        // Winner is U2 at 5000 + 100 = 5100.
        
        $this->lot->refresh();
        $this->assertEquals($this->u2->id, $this->lot->winner_user_id);
        $this->assertEquals(5100, (float)$this->lot->current_highest_bid);

        // Verify escalation sequence in DB
        // 1500 (U3 manual)
        // 5000 (U1 auto jump)
        // 5100 (U2 winning auto jump)
        $this->assertDatabaseHas('auction_bids', ['user_id' => $this->u3->id, 'amount' => 1500]);
        $this->assertDatabaseHas('auction_bids', ['user_id' => $this->u1->id, 'amount' => 5000]);
        $this->assertDatabaseHas('auction_bids', ['user_id' => $this->u2->id, 'amount' => 5100]);
    }

    /** @test */
    public function manual_bidder_wins_if_exceeds_all_auto_bid_ceilings()
    {
        // U1 max 2000
        AuctionAutoBid::create([
             'auction_lot_id' => $this->lot->id,
             'user_id' => $this->u1->id,
             'max_bid' => 2000,
             'increment_amount' => 100,
             'is_enabled' => true
        ]);

        // U2 bids 2500
        $this->actingAs($this->u2, 'api')->postJson("/api/v1/auctions/{$this->lot->id}/bid", [
            'amount' => 2500
        ]);

        $this->lot->refresh();
        $this->assertEquals($this->u2->id, $this->lot->winner_user_id);
        $this->assertEquals(2500, (float)$this->lot->current_highest_bid);
        
        // U1 should have 0 bids because 2500 jumped over their 2000 limit immediately
        $this->assertEquals(0, $this->lot->bids()->where('user_id', $this->u1->id)->count());
    }
}
