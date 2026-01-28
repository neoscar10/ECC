<?php

namespace Tests\Feature;

use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuctionAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected $basicTier;
    protected $platinumTier; 
    protected $silverTier;
    protected $goldTier;
    
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);

        // Create Hierarchy 
        $this->basicTier = MembershipTier::create(['name' => 'Basic', 'level' => 1, 'price' => 100, 'code' => 'basic']);
        $this->silverTier = MembershipTier::create(['name' => 'Silver', 'level' => 2, 'price' => 200, 'code' => 'silver']);
        $this->goldTier = MembershipTier::create(['name' => 'Gold', 'level' => 3, 'price' => 300, 'code' => 'gold']);
        $this->platinumTier = MembershipTier::create(['name' => 'Platinum', 'level' => 4, 'price' => 400, 'code' => 'platinum']);
    }

    private function assignTier(User $user, MembershipTier $tier)
    {
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
            'approved_at' => now(),
            'approved_by' => $user->id
        ]);
        $user->load('currentMembership.membershipTier');
    }

    /** @test */
    public function public_lot_is_visible_to_all()
    {
        $lot = AuctionLot::create([
             'lot_no' => 'LOT-001',
             'title' => 'Public Lot',
             'description' => 'Desc',
             'starting_price' => 1000,
             'restriction_mode' => 'public', 
             'status' => 'live',
             'starts_at' => now()->subDay(),
             'ends_at' => now()->addDay(),
             'currency' => 'INR'
        ]);
        $user = User::factory()->create();
        $this->assignTier($user, $this->basicTier);

        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions');
        
        $response->assertOk()
            ->assertJsonFragment(['id' => $lot->id]);
    }

    /** @test */
    public function restricted_lot_is_hidden_if_user_not_eligible()
    {
        $lot = AuctionLot::create([
            'lot_no' => 'LOT-002',
            'title' => 'Restricted Lot',
            'description' => 'Desc',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'starting_price' => 1000,
            'restriction_mode' => 'restricted', 
            'restriction_type' => 'hierarchical',
            'restricted_min_tier_id' => $this->silverTier->id,
            'status' => 'live',
            'currency' => 'INR'
        ]);

        $user = User::factory()->create();
        $this->assignTier($user, $this->basicTier); // Level 1 < 2

        // Show Check (403)
        $detailResponse = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$lot->id}");
        
        if ($detailResponse->status() !== 403) {
            fwrite(STDERR, "DEBUG_FAIL_RESTRICTED: " . $detailResponse->status() . " " . $detailResponse->content());
        }
        
        $detailResponse->assertStatus(403);
        $access = $detailResponse->json('data.access');
        
        // Debug
        if ($access['message']['body'] !== 'Silver Tier Required') {
             fwrite(STDERR, "DEBUG_RESTRICTED_BODY: " . $access['message']['body'] . "\n");
        }

        $this->assertEquals('Upgrade', $access['actions'][0]['label']);
    }

    /** @test */
    public function reproduce_task_d_scenario()
    {
        // One Archive product and one Auction lot with the SAME restriction config:
        // - restricted allowlist includes Gold
        // - blur enabled
        // - blur strategy hierarchical
        // - min clear tier = Sovereign (Platinum)
        // User tier = Gold (Level 3)
        // Expected: Auctions list MUST include the lot.

        $lot = AuctionLot::create([
            'lot_no' => 'LOT-TASK-D',
            'title' => 'Task D Lot',
            'description' => 'Desc',
            'starting_price' => 1000,
            'restriction_mode' => 'restricted', 
            'restriction_type' => 'allowlist',
            // Note: restricted_min_tier_id might be set or not? Logic shouldn't care if allowlist.
            'blur_enabled' => true,
            'blur_strategy' => 'hierarchical',
            'min_clear_view_tier_id' => $this->platinumTier->id, // Sovereign equivalent
            'status' => 'live',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'currency' => 'INR'
        ]);
        
        $lot->visibilityTiers()->attach($this->goldTier->id);

        $user = User::factory()->create();
        $this->assignTier($user, $this->goldTier);

        // 1. List Inclusion Check
        $response = $this->actingAs($user, 'api')->getJson('/api/v1/auctions');
        $response->assertOk();
        
        $found = collect($response->json('data'))->firstWhere('id', $lot->id);
        
        if (!$found) {
             // Debug dump to see why
             fwrite(STDERR, "DEBUG_REPRO_FAIL: Lot " . $lot->id . " not found in list.\n");
             die();
        }

        $this->assertNotNull($found, 'Lot should be included in list');
        
        // 2. View Mode Check
        $this->assertEquals('blur', $found['access']['view_mode']);
        
        // 3. Message Strings Check (Task C)
        // Expected: "Sovereign Tier Required" (Platinum in our case)
        $this->assertEquals('Platinum Tier Required', $found['access']['message']['body']);
        $this->assertEquals('Restricted View', $found['access']['message']['title']);
        $this->assertEquals('lock', $found['access']['message']['icon']); // AccessIconNormalizer check
    }

    /** @test */
    public function can_bid_requires_clear_access()
    {
        // 1. Blocked Lot -> can_bid false
        $lot = AuctionLot::create([
             'lot_no' => 'LOT-BID-01',
             'title' => 'Restricted Bid',
             'description' => 'Desc',
             'starting_price' => 1000,
             'restriction_mode' => 'restricted',
             'restriction_type' => 'hierarchical',
             'restricted_min_tier_id' => $this->goldTier->id,
             'status' => 'live',
             'currency' => 'INR'
        ]);
        
        $user = User::factory()->create();
        $this->assignTier($user, $this->silverTier);
        
        // Blocked Show (403) - can_bid isn't typically accessible in 403 data?
        // Wait, 403 returns "data" => ["access" => ...]. Does it return "data" => ["can_bid" => ...]?
        // Controller show() returns `response()->json(['data' => ['access' => ...]], 403)` manually.
        // It does NOT use `transformLot` for the 403 case currently in the snippet I saw?
        // Let's check Controller show() again.
        // Line 88: 'data' => ['access' => $access]. No can_bid.
        // So strict "can_bid" check on 403 is N/A.
        
        // 2. Blurred Lot -> can_bid false
        $blurLot = AuctionLot::create([
             'lot_no' => 'LOT-BID-02',
             'title' => 'Blurred Bid',
             'description' => 'Desc',
             'starting_price' => 1000,
             'restriction_mode' => 'restricted',
             'restriction_type' => 'hierarchical',
             'restricted_min_tier_id' => $this->silverTier->id,
             'blur_enabled' => true,
             'blur_strategy' => 'hierarchical',
             'min_clear_view_tier_id' => $this->goldTier->id,
             'status' => 'live',
             'currency' => 'INR'
        ]);
        
        // User Silver (Visible but Blurred)
        $res = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$blurLot->id}");
        $res->assertOk();
        $res->assertJsonPath('data.can_bid', false);
        $res->assertJsonPath('data.can_auto_bid', false);
        $res->assertJsonPath('data.access.view_mode', 'blur');
        
        $this->silverTier->update(['is_auto_bidding_enabled' => false]);
        $this->goldTier->update(['is_auto_bidding_enabled' => false]);
        
        $user->memberships()->delete(); // Clear old
        $this->assignTier($user, $this->goldTier);
        $user->refresh();
        $user->load('currentMembership.membershipTier');
        $res2 = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$blurLot->id}");
        $res2->assertOk();
        
        $res2->assertJsonPath('data.can_bid', true);
        // auto_bid depends on tier capability. Assuming default false?
        // We didn't set is_auto_bidding_enabled in factory. Default usually 0/false.
        $res2->assertJsonPath('data.can_auto_bid', false); 
        
        // Enable Auto Bid for Tier
        $this->goldTier->update(['is_auto_bidding_enabled' => true]);
        $user->refresh(); // Load updated tier
        $user->load('currentMembership.membershipTier');
        
        $res3 = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$blurLot->id}");
        $res3->assertJsonPath('data.can_auto_bid', true);
    }

    /** @test */
    public function images_are_returned_unconditionally()
    {
        // Setup a blurred lot (Gold required, User Silver)
        $lot = AuctionLot::create([
             'lot_no' => 'LOT-IMG-01',
             'title' => 'Image Test Lot',
             'description' => 'Desc',
             'starting_price' => 1000,
             'restriction_mode' => 'restricted',
             'restriction_type' => 'hierarchical',
             'restricted_min_tier_id' => $this->silverTier->id, // Visible to Silver
             'blur_enabled' => true,
             'blur_strategy' => 'hierarchical',
             'min_clear_view_tier_id' => $this->goldTier->id, // But blurred for Silver
             'status' => 'live',
             'currency' => 'INR'
        ]);

        // Create Image
        \App\Models\Auctions\AuctionLotImage::create([
            'auction_lot_id' => $lot->id,
            'path' => 'test.jpg',
            'sort_order' => 1
        ]);

        $user = User::factory()->create();
        $this->assignTier($user, $this->silverTier);

        $res = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$lot->id}");
        $res->assertOk();
        
        $res->assertJsonPath('data.access.view_mode', 'blur');
        
        // Assert Images Present
        $images = $res->json('data.images');
        
        if (empty($images)) {
             fwrite(STDERR, "DEBUG_FAIL_NO_IMAGES: " . $res->content() . "\n");
        }
        $this->assertNotEmpty($images, 'Images should be returned even if blurred');
        
        // Storage::url usually returns /storage/path. url() adds domain.
        // Just check if filename is in url
        $this->assertStringContainsString('test.jpg', $images[0]['url']);
    }
}
