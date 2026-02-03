<?php

namespace Tests\Feature\Notifications;

use App\Jobs\Notifications\SendFcmToTopicJob;
use App\Jobs\Notifications\SendFcmToUserJob;
use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionNotificationSubscription;
use App\Models\User;
use App\Services\Auctions\AuctionBiddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuctionNotificationTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_bid_sends_topic_fcm_only()
    {
        Queue::fake();

        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create([
            'status' => 'live',
            'starting_price' => 100,
            'current_highest_bid' => null,
        ]);

        $service = app(AuctionBiddingService::class);
        $service->placeBid($lot, $user, 110, 'web', false);

        // 1. Assert Topic Job
        Queue::assertPushed(SendFcmToTopicJob::class, function ($job) use ($lot) {
            return str_contains($job->topic, "ecc_auction_{$lot->id}")
                && $job->title === 'New Bid Placed';
        });

        // 2. Assert NO User Job
        Queue::assertNotPushed(SendFcmToUserJob::class);
    }

    public function test_auto_bid_fans_out_to_subscribers_excluding_owner_and_sends_private_confirmation()
    {
        Queue::fake();

        $autoBidder = User::factory()->create();
        $otherSubscriber1 = User::factory()->create();
        $otherSubscriber2 = User::factory()->create();
        
        $lot = AuctionLot::factory()->create([
            'status' => 'live',
            'starting_price' => 100,
            'current_highest_bid' => null,
        ]);

        // Setup Subscribers
        // Auto-bidder is subscribed (usually)
        AuctionNotificationSubscription::create(['user_id' => $autoBidder->id, 'auction_lot_id' => $lot->id, 'is_enabled' => true]);
        // Others
        AuctionNotificationSubscription::create(['user_id' => $otherSubscriber1->id, 'auction_lot_id' => $lot->id, 'is_enabled' => true]);
        AuctionNotificationSubscription::create(['user_id' => $otherSubscriber2->id, 'auction_lot_id' => $lot->id, 'is_enabled' => false]); // Disabled, should skip

        $service = app(AuctionBiddingService::class);
        // Simulate auto-bid
        $service->placeBid($lot, $autoBidder, 110, 'system', true);

        // 1. Assert TOPIC Job NOT Pushed
        Queue::assertNotPushed(SendFcmToTopicJob::class);

        // 2. Assert User Job Pushed for Other Subscriber 1 (Bid Placed)
        Queue::assertPushed(SendFcmToUserJob::class, function ($job) use ($otherSubscriber1) {
            return $job->userId === $otherSubscriber1->id
                && $job->title === 'New Bid Placed';
        });

        // 3. Assert User Job Pushed for Auto-Bidder (Auto-Bid Executed)
        Queue::assertPushed(SendFcmToUserJob::class, function ($job) use ($autoBidder) {
             return $job->userId === $autoBidder->id
                 && $job->title === 'Auto-Bid Executed';
        });

        // 4. Assert User Job NOT Pushed for Auto-Bidder with 'Bid Placed'
        // We can't strictly inspect all pushed jobs easily with simple assertPushed closure if distinct types exist,
        // but checking the *absence* of a specific combination works.
        // Or we inspect recorded jobs.
        
        $jobs = Queue::pushed(SendFcmToUserJob::class);
        $autoBidderBidPlacedJobs = $jobs->filter(function($job) use ($autoBidder) {
             return $job->userId === $autoBidder->id && $job->title === 'New Bid Placed';
        });

        $this->assertCount(0, $autoBidderBidPlacedJobs, "Auto-bidder should NOT receive 'Bid Placed' notification.");
        
        // 5. Assert User Job NOT Pushed for Disabled Subscriber
        $disabledJobs = $jobs->filter(function($job) use ($otherSubscriber2) {
             return $job->userId === $otherSubscriber2->id;
        });
        $this->assertCount(0, $disabledJobs, "Disabled subscriber should not receive notification.");
    }
}
