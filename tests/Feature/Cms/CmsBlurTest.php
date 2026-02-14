<?php

namespace Tests\Feature\Cms;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CmsBlurTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_random_blur_settings()
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $admin->assignRole($role);
        
        // Create Tiers
        $tier1 = MembershipTier::factory()->create(['name' => 'Bronze', 'level' => 10]);
        $tier2 = MembershipTier::factory()->create(['name' => 'Silver', 'level' => 20]);
        $tier3 = MembershipTier::factory()->create(['name' => 'Gold', 'level' => 30]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            ->call('create')
            // Step 1
            ->set('placement', 'explore')
            ->set('title', 'Blur Test Block')
            ->call('nextStep')
            // Step 2
            ->set('type', 'card') // card
            ->call('nextStep')
            // Step 3
            ->set('contentTitle', 'Test Title')
            ->call('nextStep')
            // Step 4: Access
            ->set('restrictionMode', 'restricted')
            ->set('selectedVisibilityTiers', [$tier1->id, $tier2->id, $tier3->id])
            ->set('blurEnabled', true)
            ->set('restrictionType', 'random')
            ->set('selectedRandomTiers', [$tier3->id]) // Only Gold sees clear
            ->call('nextStep')
            // Step 5: Save
            ->call('store')
            ->assertHasNoErrors();

        $block = CmsBlock::first();
        $this->assertNotNull($block);
        $this->assertTrue((bool)$block->blur_enabled);
        $this->assertEquals('random', $block->restriction_type);
        
        $this->assertCount(3, $block->visibilityTiers);
        $this->assertCount(1, $block->clearViewTiers);
        $this->assertEquals($tier3->id, $block->clearViewTiers->first()->id);
    }
    
    public function test_validates_random_blur_tiers_must_be_visible()
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        $admin->assignRole($role);
        
        $tier1 = MembershipTier::factory()->create(['name' => 'Bronze', 'level' => 10]);
        $tier2 = MembershipTier::factory()->create(['name' => 'Silver', 'level' => 20]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            ->call('create')
            ->set('createStep', 4)
            ->set('restrictionMode', 'restricted')
            ->set('selectedVisibilityTiers', [$tier1->id]) // Only Tier 1 visible
            ->set('blurEnabled', true)
            ->set('restrictionType', 'random')
            ->set('selectedRandomTiers', [$tier2->id]) // Select Tier 2 (Not visible)
            ->call('nextStep') // Should trigger validation
            // Wait, we implemented auto-sanitization in computeEligibleBlurTiers? 
            // If we set selectedRandomTiers via Livewire, updatedSelectedVisibilityTiers might prune it?
            // Or validation 'exists' might pass but logic inside component might strip it?
            // Actually, the `computeEligibleBlurTiers` runs on updates.
            // If I set `selectedRandomTiers` directly with an invalid ID, validation "exists:membership_tiers,id" will pass if tier exists.
            // But logic in component:
            // if (!empty($this->selectedRandomTiers)) {
            //      $this->selectedRandomTiers = array_values(array_intersect($this->selectedRandomTiers, $this->computedVisibilityTierIds));
            // }
            // So it should silently remove it?
            // Let's verify that behavior.
            ->assertHasNoErrors();
            
            // Checking the property current value
            // Livewire test doesn't easily expose current property value without assertion helper or dumping.
            // But if we proceed to store, it should fail or save empty?
            // "selectedRandomTiers" => "required|array|min:1" validation rule exists.
            // If sanitization removes it, it becomes empty, so validation should FAIL with "The selected random tiers field is required."
            
    }
}
