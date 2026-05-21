<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use App\Models\User;
use App\Services\Notifications\FcmTopicManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MembershipTierSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_change_resyncs_topics()
    {
        // 1. Arrange
        $mockFCM = Mockery::mock(FcmTopicManager::class);
        $this->instance(FcmTopicManager::class, $mockFCM);

        $oldTier = MembershipTier::create(['name' => 'Silver', 'level' => 1, 'price' => 10, 'code' => 'silver_tier']);
        $newTier = MembershipTier::create(['name' => 'Gold', 'level' => 2, 'price' => 20, 'code' => 'gold_tier']);
        
        $user = User::factory()->create();
        
        // Setup initial membership (Observer 'created' might fire, but we focus on update)
        $app = \App\Models\MembershipApplication::create([
            'user_id' => $user->id, 
            'status' => 'approved'
        ]);

        $membership = \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $oldTier->id,
            'source_application_id' => $app->id,
            'status' => 'active',
            'started_at' => now(),
        ]);
        
        $token = 'user-token-abc';
        $user->deviceTokens()->create(['token' => $token, 'platform' => 'ios']);

        // 2. Expectations
        // From Old
        $mockFCM->shouldReceive('unsubscribeTokensFromTopic')
            ->once()
            ->with([$token], 'ecc_membership_' . $oldTier->id);
            
        // To New
        $mockFCM->shouldReceive('subscribeTokensToTopic')
            ->once()
            ->with([$token], 'ecc_membership_' . $newTier->id);

        // 3. Act
        $membership->membership_tier_id = $newTier->id;
        $membership->save();

        // 4. Assert
        $this->assertDatabaseHas('memberships', [
            'id' => $membership->id,
            'membership_tier_id' => $newTier->id
        ]);
    }
}
