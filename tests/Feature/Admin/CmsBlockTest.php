<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\Blocks\Index;
use App\Models\Cms\CmsBlock;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Create the admin role needed for the middleware
        Role::findOrCreate('ecc_admin', 'web');
    }

    protected function createAdmin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('ecc_admin');
        return $admin;
    }

    public function test_can_create_basic_block()
    {
        $admin = $this->createAdmin();
        
        MembershipTier::forceCreate(['name' => 'T1', 'code' => 't1', 'price' => 0, 'is_active' => true, 'sort_order' => 1, 'level' => 1]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('createStep', 1)
            ->set('title', 'Test Block')
            ->set('placement', 'home')
            ->set('type', 'card')
            ->set('isActive', true)
            ->call('nextStep')
            ->set('createStep', 2)
            ->set('type', 'card')
            ->call('nextStep')
            ->set('createStep', 3)
            ->set('contentTitle', 'Card Title')
            ->set('contentBody', 'Card Body')
            ->call('nextStep')
            ->set('createStep', 4)
            ->set('restrictionMode', 'public')
            ->call('nextStep')
            ->call('store');

        $this->assertDatabaseHas('cms_blocks', [
            'title' => 'Test Block',
            'placement' => 'home',
            'type' => 'card',
        ]);
    }

    public function test_sort_order_is_auto_assigned()
    {
        $admin = $this->createAdmin();
        MembershipTier::forceCreate(['name' => 'T1', 'code' => 't1', 'price' => 0, 'is_active' => true, 'sort_order' => 1, 'level' => 1]);
        
        CmsBlock::create(['title' => 'B1', 'placement' => 'home', 'type' => 'card', 'sort_order' => 1]);
        CmsBlock::create(['title' => 'B2', 'placement' => 'home', 'type' => 'card', 'sort_order' => 2]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('title', 'B3')
            ->set('placement', 'home')
            ->set('type', 'card')
            ->set('createStep', 1)
            ->call('nextStep')
            ->set('createStep', 2)
            ->call('nextStep')
            ->set('createStep', 3)
            ->set('contentTitle', 'T')
            ->call('nextStep')
            ->set('createStep', 4)
            ->set('restrictionMode', 'public')
            ->call('nextStep')
            ->call('store');

        $this->assertDatabaseHas('cms_blocks', [
            'title' => 'B3',
            'sort_order' => 3,
        ]);
    }

    public function test_can_create_slider_category_mode()
    {
        $admin = $this->createAdmin();
        MembershipTier::forceCreate(['name' => 'T1', 'code' => 't1', 'price' => 0, 'is_active' => true, 'sort_order' => 1, 'level' => 1]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('title', 'Slider Cat')
            ->set('placement', 'home')
            ->set('type', 'slider')
            ->set('sliderMode', 'category')
            ->set('createStep', 2)
            ->call('nextStep')
            ->set('createStep', 3)
            ->set('contentTitle', 'Slider Title')
            ->set('sliderSource', 'shop')
            ->set('sliderCategoryId', 1)
            ->call('nextStep')
            ->set('createStep', 4)
            ->set('restrictionMode', 'public')
            ->call('nextStep')
            ->call('store');

        $block = CmsBlock::where('title', 'Slider Cat')->first();
        $this->assertNotNull($block);
        $this->assertEquals('slider', $block->type);
        $this->assertEquals('category', $block->type_config['mode']);
        $this->assertEquals(1, $block->type_config['category_id']);
    }

    public function test_can_create_slider_manual_mode()
    {
        $admin = $this->createAdmin();
        MembershipTier::forceCreate(['name' => 'T1', 'code' => 't1', 'price' => 0, 'is_active' => true, 'sort_order' => 1, 'level' => 1]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('title', 'Slider Manual')
            ->set('placement', 'home')
            ->set('createStep', 1)
            ->call('nextStep')
            ->set('createStep', 2)
            ->set('type', 'slider')
            ->set('sliderMode', 'manual')
            ->call('nextStep')
            ->set('createStep', 3)
            ->set('contentTitle', 'Slider Title')
            ->set('sliderSource', 'shop')
            ->set('selectedSliderItems', [['id' => 101, 'name' => 'Item 1']])
            ->call('nextStep') 
            ->set('createStep', 4)
            ->set('restrictionMode', 'public')
            ->call('nextStep')
            ->call('store');

        $block = CmsBlock::where('title', 'Slider Manual')->first();
        $this->assertEquals('manual', $block->type_config['mode']);
        $this->assertEquals(101, $block->type_config['items'][0]['id']);
    }
    
    public function test_can_reorder_blocks()
    {
         $admin = $this->createAdmin();
         MembershipTier::forceCreate(['name' => 'T1', 'code' => 't1', 'price' => 0, 'is_active' => true, 'sort_order' => 1, 'level' => 1]);
         
         $b1 = CmsBlock::create(['title' => 'B1', 'placement' => 'home', 'type' => 'card', 'sort_order' => 1]);
         $b2 = CmsBlock::create(['title' => 'B2', 'placement' => 'home', 'type' => 'card', 'sort_order' => 2]);
         
         Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('updateOrder', [$b2->id, $b1->id]);
            
         $this->assertEquals(1, $b2->fresh()->sort_order);
         $this->assertEquals(2, $b1->fresh()->sort_order);
    }
}
