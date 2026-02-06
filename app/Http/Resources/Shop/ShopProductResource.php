<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ShopProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryImage = $this->images->first();

        // Calculate max price for UI helper
        // Use computed column if reliable, fallback to relation scan (eager loaded in controller?)
        // We'll rely on the model attributes or load them.
        $maxVarPrice = $this->computed_max_price ?? $this->base_price; 

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'short_description' => Str::limit(strip_tags($this->description), 100),
            'currency' => $this->currency,
            'base_price' => number_format($this->base_price, 2, '.', ''),
            'stock_qty' => $this->relationLoaded('variationGroups') && $this->variationGroups->isNotEmpty() ? null : $this->stock_qty,
            'is_active' => (bool)$this->is_active,

            'primary_image' => $primaryImage ? [
                'url' => url('storage/' . $primaryImage->image_path),
                'thumb_url' => url('storage/' . $primaryImage->image_path), // Add thumb logic if exists
            ] : null,

            'price_helpers' => [
                'rule' => 'MAX_SELECTED_VARIATION_PRICE',
                'max_variation_price' => number_format($maxVarPrice, 2, '.', ''),
            ],

            'categories' => $this->categories->map(fn($c) => [
                'id' => $c->id, 
                'name' => $c->name,
                // Simple path Logic: Parent > Child
                // To do this efficiently, we normally need the parent loaded.
                // For now, returning name as path if parent not loaded, or implement simple check
                'path' => $c->parent ? $c->parent->name . ' > ' . $c->name : $c->name
            ]),

            'selected_tags' => $this->tags->map(fn($t) => [
                'group' => [
                    // We need group info. Pivot has shop_tag_group_id.
                    // Ideally we eager loaded tags.group
                    'id' => $t->group->id,
                    'slug' => Str::slug($t->group->name),
                    'name' => $t->group->name,
                ],
                'tag' => [
                    'id' => $t->id,
                    'name' => $t->name,
                ]
            ]),

            'has_variations' => $this->variationGroups->isNotEmpty(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
