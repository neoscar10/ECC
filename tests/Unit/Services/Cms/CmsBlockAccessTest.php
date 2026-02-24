<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Cms\CmsBlockAccessResolverService;
use App\Models\Cms\CmsBlock;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CmsBlockAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the resolver handles a block with null restriction data gracefully.
     * This simulates a production scenario with "bad" or incomplete data.
     */
    public function test_resolve_handles_null_restriction_data_without_crashing()
    {
        $resolver = new CmsBlockAccessResolverService();

        // Create a block using factory, then nullify restriction fields
        $block = CmsBlock::factory()->create([
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'blur_enabled' => true,
            'restricted_min_tier_id' => null,
            'min_clear_view_tier_id' => null,
        ]);

        $user = User::factory()->create();

        // This should NOT throw "Trying to access array offset on value of type null"
        $access = $resolver->resolve($block, $user);

        // Assertions
        $this->assertIsArray($access);
        $this->assertEquals('blur', $access['view_mode']);
        $this->assertEquals('blurred', $access['reason']);
        $this->assertEquals('Higher Tier Tier Required', $access['message']['body']);
        $this->assertNull($access['actions'][0]['target_tier']);
    }

    /**
     * Test that the resolver handles null user (guest) correctly.
     */
    public function test_resolve_handles_guest_user()
    {
        $resolver = new CmsBlockAccessResolverService();

        $block = CmsBlock::create([
            'title' => 'Restricted Block',
            'placement' => 'home',
            'type' => 'card',
            'restriction_mode' => 'restricted',
        ]);

        $access = $resolver->resolve($block, null);

        $this->assertEquals('blocked', $access['view_mode']);
        $this->assertEquals('block_restricted', $access['reason']);
        $this->assertEquals('Join Now', $access['actions'][0]['label']);
    }
}
