<?php

namespace Tests\Feature;

use App\Models\Auctions\AuctionLot;
use App\Models\User;
use App\Services\Notifications\FcmTopicManager;
use App\Support\Notifications\FcmTopicNamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuctionNotificationSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthHeaders(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_toggle_enables_subscription_and_subscribes_topic()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create();
        
        // User has devices
        $user->deviceTokens()->create(['token' => 't1', 'platform' => 'android']);

        $this->mock(FcmTopicManager::class, function (MockInterface $mock) use ($lot) {
            $topic = FcmTopicNamer::auctionTopic($lot);
            $mock->shouldReceive('subscribeTokensToTopic')
                ->once()
                ->with(['t1'], $topic);
        });

        $response = $this->putJson("/api/v1/auctions/{$lot->id}/notification-subscription", [
            'enabled' => true
        ], $this->getAuthHeaders($user));

        $response->assertStatus(200)
            ->assertJson(['data' => ['is_enabled' => true]]);

        $this->assertDatabaseHas('auction_notification_subscriptions', [
            'user_id' => $user->id,
            'auction_lot_id' => $lot->id,
            'is_enabled' => true
        ]);
    }

    public function test_toggle_disables_subscription_and_unsubscribes_topic()
    {
        $user = User::factory()->create();
        $lot = AuctionLot::factory()->create();
        
        // Pre-existing subscription
        $user->auctionNotificationSubscriptions()->create([
            'auction_lot_id' => $lot->id,
            'is_enabled' => true
        ]);
        
        $user->deviceTokens()->create(['token' => 't1', 'platform' => 'android']);

        $this->mock(FcmTopicManager::class, function (MockInterface $mock) use ($lot) {
            $topic = FcmTopicNamer::auctionTopic($lot);
            $mock->shouldReceive('unsubscribeTokensFromTopic')
                ->once()
                ->with(['t1'], $topic);
        });

        $response = $this->putJson("/api/v1/auctions/{$lot->id}/notification-subscription", [
            'enabled' => false
        ], $this->getAuthHeaders($user));

        $response->assertStatus(200)
            ->assertJson(['data' => ['is_enabled' => false]]);

        $this->assertDatabaseHas('auction_notification_subscriptions', [
            'user_id' => $user->id,
            'auction_lot_id' => $lot->id,
            'is_enabled' => false
        ]);
    }

    public function test_token_registration_syncs_subscriptions()
    {
        $user = User::factory()->create();
        $lot1 = AuctionLot::factory()->create();
        $lot2 = AuctionLot::factory()->create(); // Disabled sub

        $user->auctionNotificationSubscriptions()->create(['auction_lot_id' => $lot1->id, 'is_enabled' => true]);
        $user->auctionNotificationSubscriptions()->create(['auction_lot_id' => $lot2->id, 'is_enabled' => false]);

        $this->mock(FcmTopicManager::class, function (MockInterface $mock) use ($lot1, $lot2) {
            $topic1 = FcmTopicNamer::auctionTopic($lot1);
            
            // Should subscribe to Lot 1
            $mock->shouldReceive('subscribeTokensToTopic')
                ->once()
                ->with(['new_token'], $topic1);
            
            // Should NOT subscribe to Lot 2
            $topic2 = FcmTopicNamer::auctionTopic($lot2);
            $mock->shouldNotReceive('subscribeTokensToTopic')
                ->with(['new_token'], $topic2);
        });

        $response = $this->postJson('/api/v1/me/device-tokens', [
            'token' => 'new_token',
            'platform' => 'ios'
        ], $this->getAuthHeaders($user));

        $response->assertStatus(200);
    }
}
