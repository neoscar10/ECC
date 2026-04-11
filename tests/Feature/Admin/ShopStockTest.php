<?php

namespace Tests\Feature\Admin;

use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariant;
use App\Models\Shop\ShopProductVariationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Shop\Products\Index as ProductsIndex;
use App\Livewire\Admin\Shop\Inventory\Index as InventoryIndex;
use App\Models\User;

class ShopStockTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure roles exist for EnsureAdminRole middleware
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'ecc_admin', 'guard_name' => 'web']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('ecc_admin');
    }

    /** @test */
    public function it_calculates_stock_consistently_between_products_and_inventory_pages()
    {
        // 1. Create a variable product
        $product = ShopProduct::factory()->create([
            'title' => 'Test var product',
            'stock_qty' => 120, // This should be ignored when variants exist
        ]);

        // Add a variation group to mark it as variable
        ShopProductVariationGroup::create([
            'shop_product_id' => $product->id,
            'name' => 'Size',
            'presentation_type' => 'text'
        ]);

        // 2. Add active variants (total 85)
        ShopProductVariant::create([
            'shop_product_id' => $product->id,
            'sku' => 'SKU-85-1',
            'price' => 10,
            'stock_qty' => 50,
            'is_active' => true,
        ]);
        ShopProductVariant::create([
            'shop_product_id' => $product->id,
            'sku' => 'SKU-85-2',
            'price' => 10,
            'stock_qty' => 35,
            'is_active' => true,
        ]);

        // 3. Add a soft-deleted variant (120 units)
        $deletedVariant = ShopProductVariant::create([
            'shop_product_id' => $product->id,
            'sku' => 'SKU-DELETED',
            'price' => 10,
            'stock_qty' => 120,
            'is_active' => true,
        ]);
        $deletedVariant->delete();

        // 4. Test Products Index (Expected: 85)
        Livewire::actingAs($this->admin)
            ->test(ProductsIndex::class)
            ->assertViewHas('products', function ($products) use ($product) {
                $p = $products->where('id', $product->id)->first();
                return (int)$p->computed_stock === 85;
            });

        // 5. Test Inventory Index (Expected: 85, BUG currently shows 205)
        Livewire::actingAs($this->admin)
            ->test(InventoryIndex::class)
            ->assertViewHas('products', function ($products) use ($product) {
                $p = $products->where('id', $product->id)->first();
                // Ensure we check the correct property used in the view
                return (int)$p->total_computed_stock === 85;
            });
    }

    /** @test */
    public function it_excludes_inactive_variants_from_totals()
    {
        $product = ShopProduct::factory()->create(['title' => 'Product with inactive variants']);
        
        ShopProductVariationGroup::create([
            'shop_product_id' => $product->id,
            'name' => 'Color',
            'presentation_type' => 'color'
        ]);

        ShopProductVariant::create([
            'shop_product_id' => $product->id,
            'sku' => 'SKU-ACTIVE',
            'price' => 10,
            'stock_qty' => 10,
            'is_active' => true,
        ]);
        ShopProductVariant::create([
            'shop_product_id' => $product->id,
            'sku' => 'SKU-INACTIVE',
            'price' => 10,
            'stock_qty' => 20,
            'is_active' => false,
        ]);

        // Total should be 10 (excluding 20 from inactive variant)
        $this->assertEquals(10, (int)$product->fresh()->computed_stock);
    }
}
