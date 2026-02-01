<?php

namespace Tests\Feature;

use App\Jobs\Notifications\SendFcmToTopicJob;
use App\Jobs\Notifications\SendFcmToUserJob;
use App\Models\Auctions\AuctionBid;
use App\Models\Auctions\AuctionLot;
use App\Models\User;
use App\Services\Auctions\AuctionBiddingService;
use App\Services\Auctions\AuctionLifecycleService;
use App\Services\Notifications\NotificationDedupe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuctionNotificationTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bid_placed_trigger()
    {
        Bus::fake();
        
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create(['status' => 'live', 'starting_price' => 100]);
        
        $service = app(AuctionBiddingService::class);
        $service->placeBid($lot, $user, 110);
        
        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) use ($user, $lot) {
            return $job->topic === 'ecc_auction_' . $lot->id 
                && $job->data['type'] === 'bid_placed'
                && $job->data['actor_user_id'] === (string)$user->id // Casted to string by builder
                && !isset($job->data['is_autobid'])
                // Base & New fields
                && isset($job->data['lot_no'])
                && isset($job->data['sent_at'])
                && str_starts_with($job->data['event_id'], 'bid_placed:')
                && isset($job->data['currency'])
                && isset($job->data['ends_at']); // auto-added
        });
    }

    public function test_auto_bid_triggers()
    {
        Bus::fake();
        
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create(['status' => 'live', 'starting_price' => 100]);
        
        $service = app(AuctionBiddingService::class);
        $service->placeBid($lot, $user, 110, 'system', true); // isAuto = true
        
        // 1. Public Topic (Masked)
        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) use ($user) {
            return $job->data['type'] === 'bid_placed'
                && $job->data['actor_user_id'] === (string)$user->id
                && isset($job->data['lot_no']);
        });
        
        // 2. Private User
        Bus::assertDispatched(SendFcmToUserJob::class, function ($job) use ($user) {
            return $job->userId === $user->id
                && $job->data['type'] === 'auto_bid_executed'
                && isset($job->data['lot_no'])
                && isset($job->data['currency'])
                && isset($job->data['status'])
                && str_starts_with($job->data['event_id'], 'auto_bid_executed:');
        });
    }

    public function test_go_live_trigger()
    {
        Bus::fake();
        
        $lot = AuctionLot::factory()->create([
            'status' => 'upcoming', 
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour()
        ]);
        
        $service = app(AuctionLifecycleService::class);
        $service->checkLifecycle();
        
        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) {
            return $job->data['type'] === 'auction_go_live'
                && isset($job->data['lot_no'])
                && isset($job->data['sent_at'])
                && str_starts_with($job->data['event_id'], 'auction_go_live:');
        });
        
        // Dedupe check
        $this->assertDatabaseHas('notification_delivery_logs', [
            'type' => 'auction_go_live'
        ]);
    }

    public function test_ended_results_winner_triggers()
    {
        Bus::fake();
        
        $winner = User::factory()->create();
        $lot = AuctionLot::factory()->create([
            'status' => 'live', 
            'starts_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
            'current_highest_bid' => 500,
            'min_selling_price' => 100,
            // winner_user_id will be set by logic, but we can seed it or let logic do it.
            // Logic relies on actual BIDS.
        ]);
        
        // Create the winning bid
        AuctionBid::create([
            'auction_lot_id' => $lot->id,
            'user_id' => $winner->id,
            'amount' => 500,
            'placed_at' => now()->subMinutes(2)
        ]);
        
        $service = app(AuctionLifecycleService::class);
        $service->checkLifecycle();
        
        // 1. Ended
        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) {
            return $job->data['type'] === 'auction_ended'
                && isset($job->data['lot_no'])
                && str_starts_with($job->data['event_id'], 'auction_ended:')
                && isset($job->data['ends_at']);
        });

        // 2. Results
        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) use ($winner) {
            return $job->data['type'] === 'auction_results'
                && $job->data['status'] === 'ended'
                && isset($job->data['currency'])
                && isset($job->data['winning_bid_id'])
                && $job->data['winner_user_id'] === (string)$winner->id; 
        });
        
        // 3. Winner
        Bus::assertDispatched(SendFcmToUserJob::class, function ($job) use ($winner) {
            return $job->userId === $winner->id
                && $job->data['type'] === 'auction_winner'
                && isset($job->data['currency'])
                && isset($job->data['winning_bid_id']);
        });
    }

    public function test_reminder_trigger_deduped()
    {
        Bus::fake();
        Config::set('auction_notifications.reminder_minutes', [30]);
        
        // Freeze time
        $now = now();
        $this->travelTo($now);
        
        // Lot ends in EXACTLY 30 minutes
        $lot = AuctionLot::factory()->create([
            'status' => 'live',
            'starts_at' => $now->copy()->subHour(),
            'ends_at' => $now->copy()->addMinutes(30)
        ]);
        
        $service = app(AuctionLifecycleService::class);
        $service->checkLifecycle();
        
        Bus::assertDispatched(SendFcmToTopicJob::class, function ($job) {
            return $job->data['type'] === 'auction_reminder'
                && (int)$job->data['minutes_remaining'] === 30
                && str_starts_with($job->data['event_id'], 'auction_reminder:')
                && isset($job->data['lot_no']);
        });
        
        // Run again -> Should NOT dispatch
        Bus::fake(); // Clear dispatched
        $service->checkLifecycle();
        Bus::assertNotDispatched(SendFcmToTopicJob::class);
    }
}
