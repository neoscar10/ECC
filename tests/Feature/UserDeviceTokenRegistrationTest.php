<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\Notifications\FcmTopicManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class UserDeviceTokenRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_token_subscribes_to_baseline_topics()
    {
        // 1. Arrange
        // Create Mock Manager
        $mockFCM = Mockery::mock(FcmTopicManager::class);
        $this->instance(FcmTopicManager::class, $mockFCM);

        // Create User with Tier
        $tier = MembershipTier::create([
            'name' => 'Gold', 'level' => 2, 'price' => 100, 'code' => 'gold_tier'
        ]);
        
        $user = User::factory()->create();
        
        $user = User::factory()->create();
        
        // Assign membership manually if needed or via factory state if available
        // For simplicity, create membership record locally
        $app = \App\Models\MembershipApplication::create([
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
        
        // refresh user to pick up relation
        $user->refresh();

        $token = 'test-device-token-123';
        
        // 2. Expectations
        // A) Global Topic
        $mockFCM->shouldReceive('subscribeTokensToTopic')
            ->once()
            ->with([$token], 'ecc_all_users');

        // B) User Topic
        $mockFCM->shouldReceive('subscribeTokensToTopic')
            ->once()
            ->with([$token], 'ecc_user_' . $user->id);

        // C) Tier Topic
        $mockFCM->shouldReceive('subscribeTokensToTopic')
            ->once()
            ->with([$token], 'ecc_membership_' . $tier->id);

        // 3. Act
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/user/device-tokens', [
                'token' => $token,
                'platform' => 'android'
            ]);

        // 4. Assert
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
            
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'token' => $token
        ]);
    }

    public function test_register_token_without_tier_skips_tier_topic()
    {
        $mockFCM = Mockery::mock(FcmTopicManager::class);
        $this->instance(FcmTopicManager::class, $mockFCM);
        
        $user = User::factory()->create(); // No membership
        $token = 'no-tier-token';

        // Expect Global & User, but NOT Tier
        $mockFCM->shouldReceive('subscribeTokensToTopic')
            ->once()
            ->with([$token], 'ecc_all_users');
            
        $mockFCM->shouldReceive('subscribeTokensToTopic')
            ->once()
            ->with([$token], 'ecc_user_' . $user->id);
            
        $mockFCM->shouldNotReceive('subscribeTokensToTopic')
            ->with([$token], Mockery::pattern('/ecc_membership_/'));

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/user/device-tokens', [
                'token' => $token,
                'platform' => 'ios'
            ])
            ->assertStatus(200);
    }
}
