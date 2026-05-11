<?php

namespace App\Livewire\Admin\Shop\Inventory;

use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopProductVariationValue;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filterStatus = 'all'; // all, in_stock, low_stock, out_of_stock
    public $filterType = 'all';   // all, simple, variant
    public $sortField = 'updated_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
    ];

    public $lowStockThreshold = 5;

    // Modal State
    public $showAdjustModal = false;
    public $editingProduct = null;
    public $editingStockQty = 0; // For Simple
    public $editingVariationStock = []; // [variation_value_id => qty]

    public function mount()
    {
        $this->lowStockThreshold = config('shop.low_stock_threshold', 5);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }

    public function openAdjustStockModal($productId)
    {
        $this->editingProduct = ShopProduct::with(['variants.optionValues'])->findOrFail($productId);
        
        // Reset state
        $this->editingStockQty = 0;
        $this->editingVariationStock = [];

        if ($this->editingProduct->variants->isEmpty()) {
            // Simple
            $this->editingStockQty = $this->editingProduct->stock_qty ?? 0;
        } else {
            // Variant
            foreach ($this->editingProduct->variants as $variant) {
                $this->editingVariationStock[$variant->id] = $variant->stock_qty ?? 0;
            }
        }

        $this->showAdjustModal = true;
        $this->dispatch('show-adjust-stock-modal');
    }

    public function closeAdjustStockModal()
    {
        $this->showAdjustModal = false;
        $this->editingProduct = null;
        $this->editingVariationStock = [];
        $this->dispatch('hide-adjust-stock-modal');
    }

    public function saveStockAdjustment()
    {
        if (!$this->editingProduct) return;

        $isVariant = $this->editingProduct->variants->isNotEmpty();

        if ($isVariant) {
            $this->validate([
                'editingVariationStock.*' => 'required|integer|min:0',
            ]);

            DB::transaction(function () {
                foreach ($this->editingVariationStock as $id => $qty) {
                    \App\Models\Shop\ShopProductVariant::where('id', $id)->update(['stock_qty' => $qty]);
                }
                $this->editingProduct->touch();
            });

        } else {
            $this->validate([
                'editingStockQty' => 'required|integer|min:0',
            ]);

            $this->editingProduct->update(['stock_qty' => $this->editingStockQty]);
        }

        $this->closeAdjustStockModal();
        $this->dispatch('alert', type: 'success', message: 'Stock updated successfully.');
    }

    public function render()
    {
        $query = ShopProduct::query();

        // 1. Eager load sum of variation stock for performance AND count for type detection
        $query->withSum('variants', 'stock_qty')
              ->withCount('variationGroups');

        // 2. Search
        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                  ->orWhere('slug', 'like', '%'.$this->search.'%');
            });
        }

        // 3. Filter Type
        if ($this->filterType === 'simple') {
            $query->doesntHave('variationGroups');
        } elseif ($this->filterType === 'variant') {
            $query->has('variationGroups');
        }

        // 4. Total Stock Calculation for Sorting/Listing
        $query->addSelect(['total_computed_stock' => function ($sub) {
            $sub->selectRaw('COALESCE(
                (SELECT SUM(stock_qty) FROM shop_product_variants 
                 WHERE shop_product_variants.shop_product_id = shop_products.id),
                stock_qty, 
                0
            )');
        }]);

        // 5. Filter Status
        if ($this->filterStatus !== 'all') {
            if ($this->filterStatus === 'out_of_stock') {
                $query->having('total_computed_stock', '=', 0);
            } elseif ($this->filterStatus === 'low_stock') {
                $query->having('total_computed_stock', '>', 0)
                      ->having('total_computed_stock', '<=', $this->lowStockThreshold);
            } elseif ($this->filterStatus === 'in_stock') {
                $query->having('total_computed_stock', '>', $this->lowStockThreshold);
            }
        }

        // 6. Sorting
        if ($this->sortField === 'stock') {
            $query->orderBy('total_computed_stock', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $products = $query->paginate(15);

        return view('livewire.admin.shop.inventory.index', [
            'products' => $products
        ])->layout('layouts.admin');
    }
}
