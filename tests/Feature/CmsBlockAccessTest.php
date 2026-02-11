<?php

namespace Tests\Feature;

use App\Models\Cms\CmsBlock;
use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsBlockAccessTest extends TestCase
{
    // use RefreshDatabase; // Using transaction rollback trait if available or careful cleanup?
    // Project likely uses RefreshDatabase or DatabaseTransactions. 
    // I'll assume standard setup. If not, I'll clean up.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        
        // Seed Tiers
        $this->bronze = MembershipTier::create(['code' => 'bronze', 'name' => 'Bronze', 'level' => 10, 'price' => 100, 'currency' => 'INR']);
        $this->silver = MembershipTier::create(['code' => 'silver', 'name' => 'Silver', 'level' => 20, 'price' => 200, 'currency' => 'INR']);
        $this->gold = MembershipTier::create(['code' => 'gold', 'name' => 'Gold', 'level' => 30, 'price' => 300, 'currency' => 'INR']);
    }

    private function authenticateUser($tier)
    {
        $user = User::factory()->create();
        // Create membership
        // app/Models/UserMembership (check if factory exists or create manually)
        $user->memberships()->create([
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear()
        ]);
        
        $this->actingAs($user, 'api');
        return $user;
    }

    /** @test */
    public function public_blocks_are_visible_to_all_tiers()
    {
        $block = CmsBlock::create([
            'title' => 'Public Block',
            'type' => 'card',
            'restriction_mode' => 'public',
            'is_active' => true,
            'content' => ['title' => 'Visible', 'body' => 'Content']
        ]);

        $this->authenticateUser($this->bronze);

        $response = $this->getJson('/api/v1/cms/blocks');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $block->id);
    }

    /** @test */
    public function restricted_blocks_are_hidden_from_lower_tiers()
    {
        $block = CmsBlock::create([
            'title' => 'Silver Only',
            'type' => 'card',
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $this->silver->id,
            'is_active' => true,
        ]);
        
        // Add to visibility pivot implicitly via scope? 
        // No, Hierarchical checks min tier.
        // Wait, CmsBlock::scopeVisibleTo logic:
        // if restricted -> check (visibilityTiers OR minTier OR private)
        // So hierarchical logic should work even without pivot entry if scope handles it.
        // Let's check scope logic in Model:
        // ->orWhereHas('restrictedMinTier', function ($t) use ($userTier) { $t->where('level', '<=', $userTier->level); })
        // Yes, scope handles it.

        $this->authenticateUser($this->bronze); // Level 10 < 20
        $response = $this->getJson('/api/v1/cms/blocks');
        $response->assertStatus(200)->assertJsonCount(0, 'data');

        $this->authenticateUser($this->silver); // Level 20 >= 20
        $response = $this->getJson('/api/v1/cms/blocks');
        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    /** @test */
    public function blur_logic_redacts_content_for_lower_tiers()
    {
        // Setup:
        // Block is visible to Bronze (via Visibility Pivot for example, or Low Min Tier)
        // But Clear View requires Gold.
        
        $block = CmsBlock::create([
            'title' => 'Blurred Block',
            'type' => 'card',
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $this->bronze->id, // Visible to Bronze
            'blur_enabled' => true,
            'blur_strategy' => 'hierarchical',
            'min_clear_view_tier_id' => $this->gold->id, // Clear only for Gold
            'content' => [
                'title' => 'Teaser Title',
                'body' => 'Secret Body',
                'cta_text' => 'Secret Link'
            ]
        ]);

        // 1. Bronze User (Visible but Blurred)
        $this->authenticateUser($this->bronze);
        $response = $this->getJson('/api/v1/cms/blocks');
        
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
            
        $data = $response->json('data.0');
        $this->assertEquals('blur', $data['access']['view_mode']);
        $this->assertEquals('Teaser Title', $data['content']['title']);
        $this->assertNull($data['content']['body']); // Redacted
        $this->assertNull($data['content']['cta_text']); // Redacted

        // 2. Gold User (Visible and Clear)
        $this->authenticateUser($this->gold);
        $response = $this->getJson('/api/v1/cms/blocks');
        
        $data = $response->json('data.0');
        $this->assertEquals('clear', $data['access']['view_mode']);
        $this->assertEquals('Secret Body', $data['content']['body']); // Visible
    }
}
