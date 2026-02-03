<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use App\Models\User;
use App\Services\Notifications\FcmTopicManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_active_subscriptions()
    {
        // 1. Arrange
        $tier = MembershipTier::create(['name' => 'Gold', 'level' => 2, 'price' => 0, 'code' => 'gold_tier']);
        
        $password = 'password123';
        $user = User::factory()->create([
            'password' => bcrypt($password),
            'email' => 'test@example.com'
        ]);

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
        
        // Add auction subscription
        // We need a lot ID. Mock DB record.
        $lotId = 99;
        $user->auctionNotificationSubscriptions()->create([
            'auction_lot_id' => $lotId,
            'is_enabled' => true
        ]);

        // 2. Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password
        ]);

        // 3. Assert
        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'data' => [
                'active_subscriptions' => [
                    'baseline_topics',
                    'enabled_auction_lot_ids'
                ]
            ]
        ]);
        
        $data = $response->json('data.active_subscriptions');
        
        $this->assertContains('ecc_all_users', $data['baseline_topics']);
        $this->assertContains('ecc_user_' . $user->id, $data['baseline_topics']);
        $this->assertContains('ecc_membership_' . $tier->id, $data['baseline_topics']);
        
        $this->assertContains((string)$lotId, $data['enabled_auction_lot_ids']);
    }
}
