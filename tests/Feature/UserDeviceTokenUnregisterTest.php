<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Models\Auctions\AuctionLot;
use App\Services\Notifications\FcmTopicManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserDeviceTokenUnregisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_unregister_token_unsubscribes_from_all_topics()
    {
        // 1. Arrange
        $mockFCM = Mockery::mock(FcmTopicManager::class);
        $this->instance(FcmTopicManager::class, $mockFCM);

        $tier = MembershipTier::create([
            'name' => 'Platinum', 'level' => 3, 'price' => 500, 'code' => 'plat_tier'
        ]);
        
        $user = User::factory()->create();
        $app = \App\Domain\Membership\MembershipApplication::create([
            'user_id' => $user->id, 
            'status' => 'approved'
        ]);

        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'source_application_id' => $app->id,
            'status' => 'active',
            'started_at' => now(),
        ]);
        
        // Add Auction Subscription
        $lot = AuctionLot::factory()->create();
        $user->auctionNotificationSubscriptions()->create([
            'auction_lot_id' => $lot->id,
            'is_enabled' => true
        ]);

        // Add Token
        $token = 'token-to-delete';
        $user->deviceTokens()->create(['token' => $token, 'platform' => 'android']);

        $user->refresh();

        // 2. Expectations
        // A) Global
        $mockFCM->shouldReceive('unsubscribeTokensFromTopic')
            ->once()
            ->with([$token], 'ecc_all_users');

        // B) User
        $mockFCM->shouldReceive('unsubscribeTokensFromTopic')
            ->once()
            ->with([$token], 'ecc_user_' . $user->id);

        // C) Tier
        $mockFCM->shouldReceive('unsubscribeTokensFromTopic')
            ->once()
            ->with([$token], 'ecc_membership_' . $tier->id);

        // D) Auction
        $mockFCM->shouldReceive('unsubscribeTokensFromTopic')
            ->once()
            ->with([$token], 'ecc_auction_' . $lot->id);
            
        // 3. Act
        $response = $this->actingAs($user, 'api')
            ->deleteJson('/api/v1/user/device-tokens', [
                'token' => $token
            ]);

        // 4. Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_device_tokens', ['token' => $token]);
    }
}
