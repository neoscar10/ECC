<?php

namespace Tests\Feature\Api\Content;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ContentBlockApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure tiers exist
        MembershipTier::factory()->create(['name' => 'Free', 'level' => 1]);
    }

    /** @test */
    public function guest_can_fetch_public_placements()
    {
        CmsBlock::create([
            'title' => 'Home Block',
            'type' => 'banner',
            'placement' => 'home',
            'is_active' => true,
            'restriction_mode' => 'public',
        ]);

        $response = $this->getJson('/api/v1/content/placements');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['home']);
    }

    /** @test */
    public function guest_can_fetch_public_blocks()
    {
        $block = CmsBlock::create([
            'title' => 'Public Banner',
            'type' => 'banner',
            'placement' => 'home',
            'sort_order' => 1,
            'is_active' => true,
            'restriction_mode' => 'public',
            'content' => [
                'title' => 'Welcome',
                'image_url' => 'https://example.com/image.jpg',
            ]
        ]);

        $response = $this->getJson('/api/v1/content/blocks?placement=home');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $block->id)
            ->assertJsonPath('data.0.title', 'Welcome')
            ->assertJsonPath('data.0.access.state', 'public');
    }

    /** @test */
    public function guest_sees_locked_state_for_restricted_blocks()
    {
        $tier = MembershipTier::first();
        
        // For guest to see a "locked" block in the list, it must be visible (public) 
        // but have clear view restricted (teaser logic).
        $block = CmsBlock::create([
            'title' => 'Restricted Banner',
            'type' => 'banner',
            'placement' => 'home',
            'is_active' => true,
            'restriction_mode' => 'public', 
            'blur_enabled' => true,
            'min_clear_view_tier_id' => $tier->id,
            'content' => ['title' => 'Members Only']
        ]);

        $response = $this->getJson('/api/v1/content/blocks?placement=home');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $block->id)
            ->assertJsonPath('data.0.access.state', 'teaser') // Teaser = Blurred/Locked but visible
            ->assertJsonPath('data.0.access.show_teaser', true);
    }
    
    /** @test */
    public function authenticated_user_with_tier_sees_public_state()
    {
        $tier = MembershipTier::first();
        $user = User::factory()->create();
        
        // Create active membership for user
        $user->memberships()->create([
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $block = CmsBlock::create([
            'title' => 'Restricted Banner',
            'type' => 'banner',
            'placement' => 'home',
            'is_active' => true,
            'restriction_mode' => 'restricted',
            'restricted_min_tier_id' => $tier->id,
            'content' => ['title' => 'Members Only']
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/content/blocks?placement=home');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.access.state', 'public')
            ->assertJsonPath('data.0.access.is_allowed', true);
    }

    /** @test */
    public function it_returns_slider_structure_correctly()
    {
        $block = CmsBlock::create([
            'title' => 'Slider Block',
            'type' => 'slider',
            'placement' => 'home',
            'is_active' => true,
            'restriction_mode' => 'public',
            'type_config' => [
                'mode' => 'images',
                'slides' => [
                    ['title' => 'Slide 1', 'image_url' => 'img1.jpg']
                ]
            ]
        ]);

        $response = $this->getJson('/api/v1/content/blocks?placement=home');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.type', 'slider')
            ->assertJsonPath('data.0.slider.mode', 'images')
            ->assertJsonPath('data.0.slider.items.0.title', 'Slide 1');
    }
}
