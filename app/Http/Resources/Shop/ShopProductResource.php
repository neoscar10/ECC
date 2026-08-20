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

        $showcaseGroup = $this->relationLoaded('variationGroups') ? $this->variationGroups->firstWhere('show_on_list', true) : null;
        // Fallback to first group if none are marked for show_on_list
        if (!$showcaseGroup && $this->relationLoaded('variationGroups')) {
            $showcaseGroup = $this->variationGroups->first();
        }

        $formattedShowcaseGroup = null;
        if ($showcaseGroup) {
            $formattedShowcaseGroup = [
                'id' => $showcaseGroup->id,
                'name' => $showcaseGroup->name,
                'presentation' => $showcaseGroup->presentation_type,
                'has_gallery_images' => (bool)$showcaseGroup->has_images,
                'values' => $showcaseGroup->values->map(function ($val) use ($showcaseGroup) {
                    return [
                        'id' => $val->id,
                        'label' => $val->caption,
                        'price' => number_format($val->price, 2, '.', ''),
                        'stock' => $val->stock_qty,
                        'is_default' => (bool)$val->is_default,
                        'presentation_image_url' => $val->presentation_image_path ? url('storage/' . $val->presentation_image_path) : null,
                        'display' => [
                            'image_url' => $showcaseGroup->presentation_type == 'image' && $val->presentation_image_path 
                                ? url('storage/' . $val->presentation_image_path) : null,
                            'color_hex' => $showcaseGroup->presentation_type == 'color' ? $val->color_hex : null,
                        ],
                    ];
                }),
            ];
        }

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

            'has_variations' => $this->relationLoaded('variationGroups') && $this->variationGroups->isNotEmpty(),
            'showcase_variation_group' => $formattedShowcaseGroup,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
