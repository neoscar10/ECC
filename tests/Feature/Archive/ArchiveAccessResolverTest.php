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

    public function test_it_returns_secondary_action_for_early_access()
    {
        // 1. Setup Tiers
        // Gold (Level 3) -> Will be Primary (Active, Lowest Level)
        $gold = MembershipTier::create(['name' => 'Gold', 'code' => 'gold-'.uniqid(), 'level' => 3, 'has_early_access' => true, 'price_amount' => 200, 'currency' => 'INR']);
        // Platinum (Level 4) -> Will be Secondary (Active, Alternative)
        $platinum = MembershipTier::create(['name' => 'Platinum', 'code' => 'plat-'.uniqid(), 'level' => 4, 'has_early_access' => true, 'price_amount' => 300, 'currency' => 'INR']);

        // 2. Setup Product (Not Live, EA Enabled)
        $category = ArchiveCategory::create(['title' => 'Cat', 'slug' => 'cat-'.uniqid(), 'visibility' => 'public']);
        $product = ArchiveProduct::create([
            'title' => 'EA Product', 'slug' => 'ea-prod-'.uniqid(),
            'archive_category_id' => $category->id,
            'is_active' => true,
            'go_live_now' => false,
            'go_live_at' => now()->addDays(10), // Not live yet
            'early_access_enabled' => true,
        ]);

        // 3. Setup Windows (All Active)
        $product->earlyAccessWindows()->create(['membership_tier_id' => $platinum->id, 'access_at' => now()->subDay()]);
        $product->earlyAccessWindows()->create(['membership_tier_id' => $gold->id, 'access_at' => now()->subDay()]);

        // 4. Act
        $resolver = new \App\Services\Archive\ArchiveAccessResolver();
        $user = User::factory()->create(); // No tier
        
        $result = $resolver->resolveProductAccess($product, $user, null);

        // 5. Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('actions', $result);
        // Expect 1 action (Primary - Best Active Tier)
        // New logic prioritizes single clear path when all windows are open
        $this->assertCount(1, $result['actions']);

        // Check Primary (Gold, Level 3 - Sorted First)
        $this->assertEquals('primary', $result['actions'][0]['priority']);
        $this->assertEquals('Gold', $result['actions'][0]['target_tier']['name']);
    }

    public function test_it_handles_enabled_early_access_with_no_tiers_configured()
    {
        // 1. Create product with early access enabled but NO windows
        $product = ArchiveProduct::factory()->create([
            'is_active' => true,
            'restriction_mode' => 'public',
            'early_access_enabled' => true, // ENABLED
            'go_live_now' => false,
            'go_live_at' => now()->addDays(5),
            'archive_category_id' => ArchiveCategory::factory()->create()->id
        ]);

        // 2. Access as standard user (no specialized early access tiers exists)
        $user = User::factory()->create();
        
        $resolver = new \App\Services\Archive\ArchiveAccessResolver();
        $access = $resolver->resolveProductAccess($product, $user, null);

        // 3. Assertions
        // Should NOT show "Early Access: VIP" or early_access_locked
        $this->assertEquals('not_live_yet', $access['reason']);
        $this->assertEquals('Coming Soon', $access['message']['title']);
        $this->assertEquals('wait', $access['actions'][0]['type']);
        
        // Ensure VIP string string is gone
        $this->assertStringNotContainsString('VIP', json_encode($access));
    }

    public function test_it_returns_empty_actions_if_user_already_has_early_access_tier()
    {
        // 1. Setup Tier
        $gold = MembershipTier::create(['name' => 'Gold', 'code' => 'gold-'.uniqid(), 'level' => 3, 'has_early_access' => true, 'price_amount' => 200, 'currency' => 'INR']);

        // 2. Setup Product (Not Live, EA Enabled, Future Window for Gold)
        $category = ArchiveCategory::create(['title' => 'Cat', 'slug' => 'cat-'.uniqid(), 'visibility' => 'public']);
        $product = ArchiveProduct::create([
            'title' => 'EA Product', 'slug' => 'ea-prod-'.uniqid(),
            'archive_category_id' => $category->id,
            'is_active' => true,
            'go_live_now' => false,
            'go_live_at' => now()->addDays(10),
            'early_access_enabled' => true,
        ]);

        // Window starts tomorrow (so user is locked today)
        $product->earlyAccessWindows()->create(['membership_tier_id' => $gold->id, 'access_at' => now()->addDay()]);

        // 3. Setup User WITH Gold Tier
        $user = User::factory()->create();
        // Manually attach tier or use a helper if available, assumes standard relation logic
        // For testing "currentMembership", usually requires more setup, but resolveProductAccess accepts $userTier directly.
        // We will pass $gold as $userTier.

        $resolver = new \App\Services\Archive\ArchiveAccessResolver();
        $access = $resolver->resolveProductAccess($product, $user, $gold);

        // 4. Assert
        // Reason should still be early_access_locked
        $this->assertEquals('early_access_locked', $access['reason']);
        // Message should mention Gold
        $this->assertStringContainsString('Gold', $access['message']['body']);
        
        // Actions MUST be empty because user already has Gold
        $this->assertEmpty($access['actions']);
    }
    public function test_it_handles_active_past_and_future_early_access_windows()
    {
        // 1. Setup Tiers
        // Sovereign (Active/Past)
        $sovereign = MembershipTier::create(['name' => 'Sovereign', 'code' => 'sov-'.uniqid(), 'level' => 5, 'has_early_access' => true, 'price_amount' => 500, 'currency' => 'INR']);
        // Platinum (Future)
        $platinum = MembershipTier::create(['name' => 'Platinum', 'code' => 'plat-'.uniqid(), 'level' => 4, 'has_early_access' => true, 'price_amount' => 300, 'currency' => 'INR']);
        // Gold (Viewer - Current)
        $gold = MembershipTier::create(['name' => 'Gold', 'code' => 'gold-'.uniqid(), 'level' => 3, 'has_early_access' => true, 'price_amount' => 200, 'currency' => 'INR']);

        // 2. Product
        $category = ArchiveCategory::create(['title' => 'Cat', 'slug' => 'cat-'.uniqid(), 'visibility' => 'public']);
        $product = ArchiveProduct::create([
            'title' => 'Timeline Product', 'slug' => 'timeline-'.uniqid(),
            'archive_category_id' => $category->id,
            'is_active' => true,
            'go_live_now' => false,
            'go_live_at' => now()->addDays(20), // Live in 20 days
            'early_access_enabled' => true,
        ]);

        // 3. Windows
        // Sovereign started yesterday
        $product->earlyAccessWindows()->create(['membership_tier_id' => $sovereign->id, 'access_at' => now()->subDay()]);
        // Platinum starts in 5 days
        $product->earlyAccessWindows()->create(['membership_tier_id' => $platinum->id, 'access_at' => now()->addDays(5)]);

        // 4. Viewer (Gold)
        $user = User::factory()->create();
        
        $resolver = new \App\Services\Archive\ArchiveAccessResolver();
        $access = $resolver->resolveProductAccess($product, $user, $gold);

        // 5. Assertions
        // Current Bug would show Sovereign (past) as next unlock
        // Desired: Show Platinum (future) as next unlock
        
        // Timing
        $this->assertEquals(now()->addDays(5)->toIso8601String(), $access['timing']['next_access_at']);
        
        // Message
        // "Unlocks in 5 days" (approx)
        $this->assertStringContainsString('5 days', $access['message']['title']);
        // "Early Access: Platinum" (Next tier)
        $this->assertStringContainsString('Platinum', $access['message']['body']);
        
        // Actions
        // Primary: Platinum (Wait/Upgrade for next window)
        $this->assertEquals('Platinum', $access['actions'][0]['target_tier']['name']);
        
        // Secondary: Sovereign (Join to view NOW)
        $this->assertCount(2, $access['actions']);
        $this->assertEquals('Sovereign', $access['actions'][1]['target_tier']['name']);
        $this->assertEquals('secondary', $access['actions'][1]['priority']);
        $this->assertStringContainsString('view now', $access['actions'][1]['label']);
    }
    public function test_it_offers_upgrade_to_active_higher_tier_while_viewer_waits_for_own_future_window()
    {
        // 1. Setup Tiers
        // Sovereign (Active/Past, Level 5)
        $sovereign = MembershipTier::create(['name' => 'Sovereign', 'code' => 'sov-'.uniqid(), 'level' => 5, 'has_early_access' => true, 'price_amount' => 500, 'currency' => 'INR']);
        // Gold (Future, Level 3)
        $gold = MembershipTier::create(['name' => 'Gold', 'code' => 'gold-'.uniqid(), 'level' => 3, 'has_early_access' => true, 'price_amount' => 200, 'currency' => 'INR']);
        
        // 2. Product
        $category = ArchiveCategory::create(['title' => 'Cat', 'slug' => 'cat-'.uniqid(), 'visibility' => 'public']);
        $product = ArchiveProduct::create([
            'title' => 'Timeline Product', 'slug' => 'timeline-'.uniqid(),
            'archive_category_id' => $category->id,
            'is_active' => true,
            'go_live_now' => false,
            'go_live_at' => now()->addDays(20),
            'early_access_enabled' => true,
        ]);

        // 3. Windows
        // Sovereign active now
        $product->earlyAccessWindows()->create(['membership_tier_id' => $sovereign->id, 'access_at' => now()->subDay()]);
        // Gold future (User's tier)
        $product->earlyAccessWindows()->create(['membership_tier_id' => $gold->id, 'access_at' => now()->addDays(5)]);

        // 4. Viewer (Gold)
        $user = User::factory()->create();
        // Pass Gold as user tier
        $resolver = new \App\Services\Archive\ArchiveAccessResolver();
        $access = $resolver->resolveProductAccess($product, $user, $gold);

        // 5. Assertions
        // Current: User is Gold. Next Window is Gold (in 5 days).
        // Message should be about Gold unlock.
        $this->assertStringContainsString('5 days', $access['message']['title']);
        $this->assertStringContainsString('Gold', $access['message']['body']);
        
        // Actions: Should NOT be empty. Should offer Sovereign upgrade.
        $this->assertNotEmpty($access['actions']);
        $this->assertCount(1, $access['actions']);
        $this->assertEquals('Sovereign', $access['actions'][0]['target_tier']['name']);
        $this->assertEquals('primary', $access['actions'][0]['priority']);
        $this->assertStringContainsString('Upgrade to Sovereign', $access['actions'][0]['label']);
    }
}

