<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;
        
        // Prepare Variations
        $variations = $this->selectedVariations->map(function ($value) {
            return [
                'variation_group_id' => $value->group_id,
                'group_name' => $value->group->name ?? 'Unknown',
                'value_id' => $value->id,
                'value_label' => $value->caption,
                'value_price' => (float)$value->price,
                'stock_qty' => $value->stock_qty,
            ];
        });

        // Determine limits based on combination or simple product stock
        $maxAvailable = 0;
        if ($this->shop_product_variant_id && $this->variant) {
            $maxAvailable = $this->variant->stock_qty;
        } else {
            // Simple product stock or fallback to basic check
            $maxAvailable = $product->stock_qty;
        }
        
        return [
            'cart_item_id' => $this->id,
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'primary_image_url' => $product->images->first() ? Storage::url($product->images->first()->image_path) : null,
                'base_price' => (float)$product->base_price,
                'currency' => $product->currency,
            ],
            'quantity' => $this->quantity,
            'unit_price' => (float)$this->unit_price,
            'line_total' => (float)$this->line_total,
            'currency' => $this->currency,
            'selected_variations' => $variations,
            'availability' => [
                'is_available' => $maxAvailable >= $this->quantity,
                'max_available_qty' => $maxAvailable,
            ],
        ];
    }
}
