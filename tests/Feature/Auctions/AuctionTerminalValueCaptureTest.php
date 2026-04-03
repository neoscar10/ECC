<?php

namespace Tests\Feature\Auctions;

use App\Models\Auctions\AuctionAutoBid;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuctionTerminalValueCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected $tier;
    protected $u1;
    protected $u2;
    protected $lot;

    protected function setUp(): void
    {
        parent::setUp();
        
        Bus::fake();
        Event::fake();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);

        $this->tier = MembershipTier::create([
            'name' => 'Sovereign', 
            'level' => 10, 
            'code' => 'sovereign',
            'is_auto_bidding_enabled' => true
        ]);
        
        $this->u1 = User::factory()->create(['name' => 'Super Admin']); // System Pressure actor
        $this->u2 = User::factory()->create(['name' => 'Real Bidder']);

        $this->assignTier($this->u1, $this->tier);
        $this->assignTier($this->u2, $this->tier);

        $this->lot = AuctionLot::create([
             'lot_no' => 'LOT-VAL-01',
             'title' => 'Value Capture Test Lot',
             'starting_price' => 1000,
             'min_increment' => 100,
             'restriction_mode' => 'public',
             'status' => 'live',
             'ends_at' => now()->addSeconds(30),
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
    public function auction_ends_at_authorized_max_with_single_auto_bidder()
    {
        // 1. Setup Auto-Bid for U2 (max 100,000)
        AuctionAutoBid::create([
            'auction_lot_id' => $this->lot->id,
            'user_id' => $this->u2->id,
            'max_bid' => 100000,
            'increment_amount' => 1000,
            'is_enabled' => true
        ]);

        // Place initial bid so it's not starting price (simulate activity)
        $this->actingAs($this->u2)->postJson("/api/v1/auctions/{$this->lot->id}/bid", ['amount' => 2000]);

        // 2. Simulate Auction Closing
        // This should trigger the terminal value capture
        app(\App\Services\Auctions\AuctionTerminalValueCaptureService::class)->capture($this->lot);
        $this->lot->status = 'ended';
        $this->lot->save();

        // 3. Verify Results
        $this->lot->refresh();
        $this->assertEquals('ended', $this->lot->status);
        $this->assertEquals($this->u2->id, $this->lot->winner_user_id);
        
        // REQUIREMENT: Must be 100,000
        $this->assertEquals(100000, (float)$this->lot->current_highest_bid);

        // Verify history shows the system pressure
        $this->assertDatabaseHas('auction_bids', [
            'auction_lot_id' => $this->lot->id,
            'amount' => 99000, // 100,000 - 1000
            'source' => 'system_terminal',
            'user_id' => 1 // Super Admin
        ]);

        $this->assertDatabaseHas('auction_bids', [
            'auction_lot_id' => $this->lot->id,
            'amount' => 100000,
            'user_id' => $this->u2->id
        ]);
    }
}
