<?php

namespace Tests\Feature\Cms;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Tests\TestCase;

class CmsPlacementAndReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_canonical_placements()
    {
        $response = $this->getJson('/api/v1/content/placements');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    ['key' => 'home-hero', 'label' => 'Home Hero'],
                    ['key' => 'explore', 'label' => 'Explore'],
                ]
            ]);
    }

    public function test_admin_can_create_block_with_canonical_placement()
    {
        // Spatie setup
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        
        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            ->set('title', 'Test Block')
            ->set('placement', 'home-hero')
            ->set('type', 'card')
            ->set('contentTitle', 'Card Title') // Required
            ->set('contentBody', 'Card Body')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cms_blocks', [
            'title' => 'Test Block',
            'placement' => 'home-hero',
        ]);
    }

    public function test_admin_cannot_create_block_with_invalid_placement()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        
        $admin = User::factory()->create();
        $admin->assignRole($role);
        
        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            ->set('title', 'Test Block')
            ->set('placement', 'invalid-placement')
            ->set('type', 'card')
            ->call('store')
            ->assertHasErrors(['placement']);
    }

    public function test_reordering_blocks_updates_sort_order()
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        
        $block1 = CmsBlock::create([
            'title' => 'B1', 'placement' => 'home-hero', 'sort_order' => 1, 'type' => 'card', 'content' => [], 'is_active' => true, 'restriction_mode' => 'public', 'blur_enabled' => false
        ]);
        $block2 = CmsBlock::create([
            'title' => 'B2', 'placement' => 'home-hero', 'sort_order' => 2, 'type' => 'card', 'content' => [], 'is_active' => true, 'restriction_mode' => 'public', 'blur_enabled' => false
        ]);
        $block3 = CmsBlock::create([
            'title' => 'B3', 'placement' => 'home-hero', 'sort_order' => 3, 'type' => 'card', 'content' => [], 'is_active' => true, 'restriction_mode' => 'public', 'blur_enabled' => false
        ]);

        // Reorder: 3, 1, 2
        $orderedIds = [$block3->id, $block1->id, $block2->id];

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Cms\Blocks\Index::class)
            ->call('updateOrder', $orderedIds);

        $this->assertEquals(1, $block3->fresh()->sort_order);
        $this->assertEquals(2, $block1->fresh()->sort_order);
        $this->assertEquals(3, $block2->fresh()->sort_order);
    }
}
