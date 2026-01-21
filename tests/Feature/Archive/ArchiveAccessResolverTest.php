<?php

namespace Tests\Feature\Archive;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveAccessResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_null_upgrade_path_gracefully()
    {
        // 1. Setup Data
        $category = ArchiveCategory::create([
            'title' => 'Test Cat',
            'slug' => 'test-cat',
            'visibility' => 'public',
            'is_active' => true,
        ]);

        $product = ArchiveProduct::create([
            'title' => 'Test Product',
            'slug' => 'test-product',
            'archive_category_id' => $category->id,
            'restriction_mode' => 'public', // Product open, so we reach attachment check
            'is_active' => true,
            'go_live_now' => true,
        ]);

        // Create attachment with BROKEN restriction config (hierarchical but NO min tier)
        // This causes findBaseRestrictionUpgrade to return null
        $attachment = $product->attachments()->create([
            'type' => 'line',
            'restriction_mode' => 'restricted',
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => null, // INVALID CONFIG
            'sort_order' => 1
        ]);

        // Create user without tier
        $user = User::factory()->create();

        // 2. Act
        $resolver = new \App\Services\Archive\ArchiveAccessResolver();
        
        // We pass the user, and null for userTier (since user has no tier)
        // This exactly replicates the conditions where $userTier is null/derived
        $result = $resolver->resolveAttachmentAccess($attachment, $product, $user, null);

        // 3. Assert
        // If we reach here, no exception was thrown (500 fixed).
        $this->assertIsArray($result);
        $this->assertArrayHasKey('view_mode', $result);
        // Expect blocked/restricted because of the broken/restricted config
        $this->assertEquals('blocked', $result['view_mode']);
        // Verify fallback logic
        $this->assertStringContainsString('Membership', $result['message']['body'] ?? $result['message']['title'] ?? '');
    }
}
