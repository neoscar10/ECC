<?php

namespace Tests\Feature;

use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuctionAccessControlRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected $silverTier;
    protected $goldTier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);

        // Silver = Level 1, Gold = Level 2 (as per user description implies Gold > Silver)
        // User said: "Min Clear View Tier = Silver (level 1). User: Gold (level 2)"
        $this->silverTier = MembershipTier::create(['name' => 'Silver', 'level' => 1, 'price' => 200, 'code' => 'silver']);
        $this->goldTier = MembershipTier::create(['name' => 'Gold', 'level' => 2, 'price' => 300, 'code' => 'gold']);
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
    public function regression_gold_user_should_see_clear_when_min_tier_is_silver()
    {
        // "FAILING CASE (MUST PASS AFTER FIX)"
        // Lot id=3 admin config:
        // - visibility restricted
        // - allowlist includes Gold
        // - blur_enabled true
        // - blur_strategy hierarchical
        // - min_clear_view_tier = Silver (level 1)
        
        $lot = AuctionLot::create([
            'lot_no' => 'LOT-REG-01',
            'title' => 'Regression Lot',
            'description' => 'Desc',
            'starting_price' => 1000,
            
            // Visibility Config
            'restriction_mode' => 'restricted',   // "Restricted"
            'restriction_type' => 'allowlist',    // Implied by "allowlist includes Gold"
            
            // Blur Config
            'blur_enabled' => true,
            'blur_strategy' => 'hierarchical',
            'min_clear_view_tier_id' => $this->silverTier->id,
            
            'status' => 'live',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'currency' => 'INR'
        ]);

        // "Allowlist includes Gold"
        $lot->visibilityTiers()->attach($this->goldTier);

        // User: Gold
        $user = User::factory()->create();
        $this->assignTier($user, $this->goldTier);

        // Act
        $response = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$lot->id}");

        // Assert
        $response->assertOk();
        $access = $response->json('data.access');
        
        $this->assertEquals('clear', $access['view_mode'], 'Gold user should see clear view when Min Clear Tier is Silver');
        $this->assertEquals('Open', $access['message']['title']);
        $this->assertEquals('Access Granted', $access['message']['body']);
    }

    /** @test */
    public function verify_message_has_no_double_tier()
    {
        // Scenario: Silver User (Level 1) looking at lot requiring Gold (Level 2)
        $lot = AuctionLot::create([
             'lot_no' => 'LOT-REG-02',
             'title' => 'Gold Only',
             'description' => 'Desc',
             'starting_price' => 1000,
             'restriction_mode' => 'restricted',
             'restriction_type' => 'tiered', // defaults to hierarchical usually if type matches? NO wait.
             // Let's use hierarchical
             'restriction_type' => 'hierarchical',
             'restricted_min_tier_id' => $this->silverTier->id,
             // Blur
             'blur_enabled' => true,
             'blur_strategy' => 'hierarchical',
             'min_clear_view_tier_id' => $this->goldTier->id,
             
             'status' => 'live',
             'starts_at' => now()->subDay(),
             'ends_at' => now()->addDay(),
             'currency' => 'INR'
        ]);

        $user = User::factory()->create();
        $this->assignTier($user, $this->silverTier); 

        // User is Level 1. Min Clear is Level 2. So BLUR is expected.
        $response = $this->actingAs($user, 'api')->getJson("/api/v1/auctions/{$lot->id}");
        
        if ($response->status() !== 200) {
             fwrite(STDERR, "DEBUG_FAIL_STATUS: " . $response->status() . " " . $response->content() . "\n");
        }
        $response->assertOk();
        $access = $response->json('data.access');
        
        $this->assertEquals('blur', $access['view_mode']);
        
        if ($access['message']['body'] !== 'Gold Tier Required') {
            fwrite(STDERR, "DEBUG_REGRESSION_BODY: " . $access['message']['body'] . "\n");
        }
        $this->assertEquals('Gold Tier Required', $access['message']['body']);
    }
}
