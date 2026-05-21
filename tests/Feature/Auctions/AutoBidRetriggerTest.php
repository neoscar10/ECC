<?php

namespace Tests\Feature\Auctions;

use App\Jobs\Auctions\ProcessAutoBidStepJob;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutoBidRetriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_autobid_should_retrigger_after_manual_outbid()
    {
        Queue::fake();

        // 1. Setup Data
        $tier = MembershipTier::create(['name' => 'Gold', 'level' => 2, 'price' => 100, 'is_auto_bidding_enabled' => true, 'code' => 'gold']);
        
        $userA = User::factory()->create(); // Auto-Bidder
        $this->setupMembership($userA, $tier);

        $userB = User::factory()->create(); // Manual Bidder
        $this->setupMembership($userB, $tier);

        $lot = AuctionLot::factory()->create([
            'starting_price' => 100,
            'min_increment' => 10,
            'current_highest_bid' => null,
            'status' => 'live',
            'ends_at' => now()->addHour(),
        ]);

        // 2. User A sets Auto-Bid (Initial Bid -> 100)
        $this->actingAs($userA, 'api')->postJson("/api/v1/auctions/{$lot->id}/auto-bid", [
            'max_bid' => 500,
            'increment_amount' => 10,
        ])->assertStatus(200);

        // Assert Immediate Initial Bid
        $this->assertDatabaseHas('auction_bids', [
            'auction_lot_id' => $lot->id,
            'user_id' => $userA->id,
            'amount' => 100,
        ]);
        $lot->refresh();
        $this->assertEquals($userA->id, $lot->winner_user_id);

        // 3. Simulating Job Completion (Lock Removal)
        // Since Step 2 called setAutoBid -> processAutoBids -> lock set (future)
        // We simulate the job finishing by clearing the lock, allowing User B's bid to trigger new job.
        \Illuminate\Support\Facades\Cache::forget("auctions:auto_bid:pending:{$lot->id}");

        // User B places Manual Bid (150) -> Outbids A
        $this->actingAs($userB, 'api')->postJson("/api/v1/auctions/{$lot->id}/bid", [
            'amount' => 150,
        ])->assertStatus(200);

        $lot->refresh();
        $this->assertEquals(150, $lot->current_highest_bid);
        $this->assertEquals($userB->id, $lot->winner_user_id);

        // 4. Verify Job Dispatched
        // We expect ProcessAutoBidStepJob to be pushed
        Queue::assertPushed(ProcessAutoBidStepJob::class, function ($job) use ($lot) {
            // Check if job is for our lot
            $prop = new \ReflectionProperty($job, 'lotId');
            $prop->setAccessible(true);
            return $prop->getValue($job) === $lot->id;
        });

        // 5. MANUALLY Run the Job to Verify Logic
        // IF the system is working, this job should find User A and place bid 160.
        $job = new ProcessAutoBidStepJob($lot->id);
        $biddingService = app(\App\Services\Auctions\AuctionBiddingService::class);
        $autoBidService = app(\App\Services\Auctions\AuctionAutoBidService::class);
        
        $job->handle($biddingService, $autoBidService);

        // 6. Assert Result
        $lot->refresh();
        // User A should have bid 150 + 10 = 160
        $this->assertEquals(160, $lot->current_highest_bid, "Auto-bid did not trigger correctly.");
        $this->assertEquals($userA->id, $lot->winner_user_id);
    }

    protected function setupMembership($user, $tier)
    {
        $app = \App\Models\MembershipApplication::create(['user_id' => $user->id, 'status' => 'approved']);
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'source_application_id' => $app->id,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }
}
