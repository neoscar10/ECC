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

    public $expandedRows = [];
    public $loadingRows = [];

    // State for inline editing
    // simpleStockValues[productId] = qty
    public $simpleStockValues = []; 
    
    // variationStockValues[variationValueId] = qty
    public $variationStockValues = [];

    public $lowStockThreshold = 5;

    public function mount()
    {
        $this->lowStockThreshold = config('shop.low_stock_threshold', 5);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }

    public function toggleExpand($productId)
    {
        if (in_array($productId, $this->expandedRows)) {
            $this->expandedRows = array_diff($this->expandedRows, [$productId]);
            unset($this->simpleStockValues[$productId]);
            // We don't easily unset variation values without iterating, but it's fine to keep them in memory briefly or clear them.
        } else {
            $this->expandedRows[] = $productId;
            $this->loadRowData($productId);
        }
    }

    public function loadRowData($productId)
    {
        $product = ShopProduct::with(['variationGroups.values'])->find($productId);
        if (!$product) return;

        if ($product->variationGroups->isEmpty()) {
            $this->simpleStockValues[$productId] = $product->stock_qty;
        } else {
            foreach ($product->variationGroups as $group) {
                foreach ($group->values as $val) {
                    $this->variationStockValues[$val->id] = $val->stock_qty;
                }
            }
        }
    }

    public function saveSimpleStock($productId)
    {
        $this->validate([
            "simpleStockValues.{$productId}" => 'required|integer|min:0',
        ]);

        $qty = $this->simpleStockValues[$productId];

        DB::transaction(function () use ($productId, $qty) {
            $product = ShopProduct::where('id', $productId)->lockForUpdate()->firstOrFail();
            $product->update(['stock_qty' => $qty]);
        });
        
        $this->dispatch('alert', type: 'success', message: 'Stock updated.');
    }

    public function saveVariationStock($variationId)
    {
        $this->validate([
            "variationStockValues.{$variationId}" => 'required|integer|min:0',
        ]);

        $qty = $this->variationStockValues[$variationId];

        DB::transaction(function () use ($variationId, $qty) {
            $val = ShopProductVariationValue::where('id', $variationId)->lockForUpdate()->firstOrFail();
            $val->update(['stock_qty' => $qty]);
        }); // Parent product touch() happens automatically if relation set up, or we might need to touch parent.
        
        // Touch parent to update 'Last Updated'
        $val = ShopProductVariationValue::find($variationId);
        if($val && $val->group && $val->group->product) {
            $val->group->product->touch();
        }

        $this->dispatch('alert', type: 'success', message: 'Variation stock updated.');
    }

    public function render()
    {
        $query = ShopProduct::query();

        // 1. Eager load sum of variation stock for performance
        // We use withSum to get 'variation_values_sum_stock_qty'.
        // Note: variationValues is a HasManyThrough in ShopProduct model.
        $query->withSum('variationValues', 'stock_qty');

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

        // 4. Sort (Preliminary)
        // We need to retrieve results to filter by status efficiently if we calculate status in PHP,
        // OR we try to filter by status in SQL.
        // SQL is better for pagination.
        
        // Logic for Total Stock in SQL:
        // COALESCE(variation_values_sum_stock_qty, stock_qty, 0)
        // Note: withSum creates `variation_values_sum_stock_qty`.
        // ShopProduct logic: if has variants, use sum. If not, use stock_qty.
        // But stock_qty is null for variants usually? 
        // Let's assume: if variation_values_sum_stock_qty IS NOT NULL, use it. Else use stock_qty.
        
        // Add select raw for total stock to sort/filter
        $query->addSelect(['total_computed_stock' => function ($sub) {
            $sub->selectRaw('COALESCE(
                (SELECT SUM(stock_qty) FROM shop_product_variation_values 
                 INNER JOIN shop_product_variation_groups ON shop_product_variation_groups.id = shop_product_variation_values.group_id
                 WHERE shop_product_variation_groups.shop_product_id = shop_products.id),
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
