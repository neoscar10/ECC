<?php

namespace Tests\Feature\Cms;

use App\Models\Cms\CmsBlock;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsApiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_public_block_details()
    {
        $block = CmsBlock::factory()->create([
            'restriction_mode' => 'public',
            'blur_enabled' => false,
            'placement' => 'home-hero'
        ]);

        $response = $this->getJson('/api/v1/content/blocks?placement=home-hero');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $block->id)
            ->assertJsonPath('data.0.access.view_mode', 'clear')
            ->assertJsonPath('data.0.access.is_allowed', true);
    }

    public function test_api_handles_random_blur_access()
    {
        // Tiers
        $tier1 = MembershipTier::factory()->create(['level' => 10, 'name' => 'Bronze']);
        $tier2 = MembershipTier::factory()->create(['level' => 20, 'name' => 'Silver']);
        $tier3 = MembershipTier::factory()->create(['level' => 30, 'name' => 'Gold']);

        // Block: Visible to all 3, but Clear only for Tier 3 (Gold)
        $block = CmsBlock::factory()->create([
            'restriction_mode' => 'restricted',
            'restriction_type' => 'random',
            'blur_enabled' => true,
            'blur_strategy' => 'random',
            'placement' => 'explore'
        ]);
        
        $block->visibilityTiers()->attach([$tier1->id, $tier2->id, $tier3->id]);
        $block->clearViewTiers()->attach([$tier3->id]);

        // User 1: Bronze (Should see Blurred)
        $userBronze = User::factory()->create();
        $userBronze->memberships()->create(['membership_tier_id' => $tier1->id, 'status' => 'active', 'started_at' => now()]);

        $response = $this->actingAs($userBronze, 'api')->getJson('/api/v1/content/blocks?placement=explore');

        $response->assertOk();
        $data = $response->json('data.0');
        
        $this->assertEquals($block->id, $data['id']);
        $this->assertEquals('blur', $data['access']['view_mode']);
        $this->assertFalse($data['access']['is_allowed']);
        $this->assertTrue($data['access']['show_teaser']);
        $this->assertEquals('Restricted View', $data['access']['message']['title']);
        
        // User 2: Gold (Should see Clear)
        $userGold = User::factory()->create();
        $userGold->memberships()->create(['membership_tier_id' => $tier3->id, 'status' => 'active', 'started_at' => now()]);

        $response = $this->actingAs($userGold, 'api')->getJson('/api/v1/content/blocks?placement=explore');
        
        $response->assertOk();
        $data = $response->json('data.0');
        
        $this->assertEquals('clear', $data['access']['view_mode']);
        $this->assertTrue($data['access']['is_allowed']);
        $this->assertEquals('Open', $data['access']['message']['title']);
    }

    public function test_api_hides_block_if_not_visible()
    {
        $tier1 = MembershipTier::factory()->create(['level' => 10]);
        $tier2 = MembershipTier::factory()->create(['level' => 20]);

        // Block: Visible only to Tier 2
        $block = CmsBlock::factory()->create([
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $tier2->id,
            'placement' => 'explore'
        ]);

        // User: Tier 1
        $user = User::factory()->create();
        $user->memberships()->create(['membership_tier_id' => $tier1->id, 'status' => 'active', 'started_at' => now()]);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/content/blocks?placement=explore');

        $response->assertOk();
        $response->assertJsonCount(0, 'data'); // Should not see the block at all
    }
}
