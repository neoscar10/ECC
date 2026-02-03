<?php

namespace Tests\Feature\Auctions;

use App\Jobs\Auctions\ProcessAutoBidStepJob;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InitialAutoBidTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake(); // Prevent actual dispatch, but allow inspection
    }

    public function test_initial_autobid_places_immediate_bid_if_no_bids_yet()
    {
        // 1. Arrange
        $tier = MembershipTier::create(['name' => 'Gold', 'level' => 2, 'price' => 100, 'code' => 'gold_tier', 'is_auto_bidding_enabled' => true]);
        $user = User::factory()->create();
        // Setup membership manually
        $app = \App\Domain\Membership\MembershipApplication::create(['user_id' => $user->id, 'status' => 'approved']);
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'source_application_id' => $app->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $lot = AuctionLot::factory()->create([
            'starting_price' => 100,
            'min_increment' => 10,
            'current_highest_bid' => null,
            'winner_user_id' => null,
            'status' => 'live',
            'ends_at' => now()->addHour(),
        ]);

        // 2. Act
        $response = $this->actingAs($user)->postJson("/api/v1/auctions/{$lot->id}/auto-bid", [
            'max_bid' => 500,
            'increment_amount' => 15, // > min 10
        ]);

        // 3. Assert
        $response->assertStatus(200);

        // Check if bid was placed IMMEDIATELY
        $this->assertDatabaseHas('auction_bids', [
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'amount' => 100, // starting price
            'is_auto' => true,
        ]);

        $lot->refresh();
        $this->assertEquals(100, $lot->current_highest_bid);
        $this->assertEquals($user->id, $lot->winner_user_id);
    }

    public function test_initial_autobid_schedules_job_if_existing_bid()
    {
        // 1. Arrange
        // User A (Winner)
        $userA = User::factory()->create();
        $lot = AuctionLot::factory()->create([
            'starting_price' => 100,
            'min_increment' => 10,
            'current_highest_bid' => 100,
            'winner_user_id' => $userA->id,
            'status' => 'live',
            'ends_at' => now()->addHour(),
        ]);

        // User B (Auto Bidder)
        $tier = MembershipTier::create(['name' => 'Plat', 'level' => 3, 'price' => 200, 'code' => 'plat_tier', 'is_auto_bidding_enabled' => true]);
        $userB = User::factory()->create();
        $app = \App\Domain\Membership\MembershipApplication::create(['user_id' => $userB->id, 'status' => 'approved']);
        \App\Models\Membership::create([
            'user_id' => $userB->id,
            'membership_tier_id' => $tier->id,
            'source_application_id' => $app->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Pre-set Cache Lock to verify if it gets cleared?
        // Actually, if we clear it, we expect ProcessAutoBidStepJob to be pushed.
        // Queue::fake() captures the push.

        // 2. Act
        // Current: 100. Next req: 100 + 10 = 110. Max: 200.
        $response = $this->actingAs($userB)->postJson("/api/v1/auctions/{$lot->id}/auto-bid", [
            'max_bid' => 200,
            'increment_amount' => 10,
        ]);

        // 3. Assert
        $response->assertStatus(200);

        // Immediate bid should NOT be placed in DB (it relies on job)
        // Wait, current logic for Case B says: force schedule.
        // So DB check verifies setAutoBid didn't place it synchronously?
        // But if code places it synchronously (if I changed my mind), this test would fail.
        // My implementation: Case B -> Schedule.
        $this->assertDatabaseMissing('auction_bids', [
            'user_id' => $userB->id,
            'amount' => 110
        ]);

        // Verify Job Pushed
        Queue::assertPushed(ProcessAutoBidStepJob::class, function ($job) use ($lot) {
            // Can inspect job properties if public property exists
            return true;
        });
        
        // Also verify cache lock is gone? 
        // Can't easily test cache forget in feature test unless we spy on Cache.
    }

    public function test_initial_autobid_skips_if_winning()
    {
        // 1. Arrange
        $user = User::factory()->create();
        $tier = MembershipTier::create(['name' => 'Plat', 'level' => 3, 'price' => 200, 'code' => 'plat_tier_2', 'is_auto_bidding_enabled' => true]);
        $app = \App\Domain\Membership\MembershipApplication::create(['user_id' => $user->id, 'status' => 'approved']);
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'source_application_id' => $app->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $lot = AuctionLot::factory()->create([
            'starting_price' => 100,
            'min_increment' => 10,
            'current_highest_bid' => 100,
            'winner_user_id' => $user->id, // Already winning
            'status' => 'live',
        ]);

        // 2. Act
        $response = $this->actingAs($user)->postJson("/api/v1/auctions/{$lot->id}/auto-bid", [
            'max_bid' => 500,
            'increment_amount' => 10,
        ]);

        // 3. Assert
        // Should NOT place new bid
        $this->assertEquals(100, $lot->fresh()->current_highest_bid);
        
        // Should standard schedule (Queue pushed)
        Queue::assertPushed(ProcessAutoBidStepJob::class);
    }
}
