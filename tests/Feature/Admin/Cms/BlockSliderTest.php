<?php

namespace Tests\Feature\Admin\Cms;

use App\Models\User;
use App\Models\Cms\CmsBlock;
use App\Models\Shop\ShopCategory;
use App\Models\Archive\ArchiveCategory;
use App\Models\Auctions\AuctionLot;
use App\Livewire\Admin\Cms\Blocks\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlockSliderTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    /** @test */
    public function it_loads_shop_categories_when_source_is_shop()
    {
        $parent = ShopCategory::create(['name' => 'Bats', 'slug' => 'bats', 'sort_order' => 1]);
        $child = ShopCategory::create(['name' => 'English Willow', 'slug' => 'english-willow', 'parent_id' => $parent->id, 'sort_order' => 1]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('createStep', 3)
            ->set('type', 'slider')
            ->set('sliderMode', 'category')
            ->set('sliderSource', 'shop')
            ->assertSet('sliderSource', 'shop')
            ->assertCount('sourceCategories', 2)
            ->assertSeeHtml('Bats')
            ->assertSeeHtml('— English Willow');
    }

    /** @test */
    public function it_loads_archive_categories_when_source_is_archive()
    {
        ArchiveCategory::create(['title' => '1990s', 'slug' => '1990s', 'is_active' => true]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('createStep', 3)
            ->set('type', 'slider')
            ->set('sliderMode', 'category')
            ->set('sliderSource', 'archive')
            ->assertCount('sourceCategories', 1)
            ->assertSeeHtml('1990s');
    }

    /** @test */
    public function it_hides_categories_and_requires_lots_when_source_is_auctions()
    {
        $lot = AuctionLot::factory()->create(['title' => 'Signed Bat', 'lot_no' => '101']);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            // Navigate to Step 3
            ->set('createStep', 3)
            ->set('type', 'slider')
            ->set('sliderMode', 'category')
            ->set('sliderSource', 'auctions')
            // Assert Categories are cleared/empty (optional check mainly for UI state)
            ->assertSet('sourceCategories', [])
            // Try validation without lots
            ->call('validateStep', 3)
            ->assertHasErrors(['selectedSliderItems'])
            // Search and Add Lot
            ->set('itemSearchQuery', '101')
            ->call('updatedItemSearchQuery') // Trigger search
            ->call('addSliderItem', $lot->id)
            ->assertCount('selectedSliderItems', 1)
            // Validate success
            ->call('validateStep', 3)
            ->assertHasNoErrors(['selectedSliderItems']);
    }
}
