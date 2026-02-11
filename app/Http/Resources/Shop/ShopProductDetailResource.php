<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ShopProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // 1. Calculate Defaults & Pricing
        $defaultValues = collect();
        foreach ($this->variationGroups as $group) {
            $def = $group->values->where('is_default', true)->first();
            if (!$def && $group->values->isNotEmpty()) {
                $def = $group->values->first();
            }
            if ($def) {
                $defaultValues->put($group->id, $def);
            }
        }
        
        // Compute default price (Max of defaults vs Base)
        $defaultPrice = $this->base_price;
        if ($defaultValues->isNotEmpty()) {
            $maxOfDefaults = $defaultValues->max('price');
            $defaultPrice = max($this->base_price, $maxOfDefaults);
        }

        // 2. Format Tags by Group
        $tagsByGroup = $this->tags->groupBy('shop_tag_group_id')->map(function ($tags) {
            $firstTag = $tags->first();
            $group = $firstTag->group;
            
            // Per contract: 1 selected tag per group (usually)
            // But if multiple, we just list them? Contract says "selected_tag" (singular).
            // We'll take the first one.
            return [
                'group' => [
                    'id' => $group->id,
                    'slug' => Str::slug($group->name),
                    'name' => $group->name,
                ],
                'selected_tag' => [
                    'id' => $firstTag->id,
                    'name' => $firstTag->name,
                ]
            ];
        })->values();

        // 3. Find Gallery Control Group
        $galleryControlGroup = $this->variationGroups->where('has_images', true)->first();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'currency' => $this->currency,
            'base_price' => number_format($this->base_price, 2, '.', ''),

            'media' => $this->images->map(function ($img, $index) {
                return [
                    'id' => $img->id,
                    'url' => url('storage/' . $img->image_path),
                    'thumb_url' => url('storage/' . $img->image_path),
                    'position' => $img->sort_order, // or index + 1
                    'is_primary' => $index === 0,
                ];
            }),

            'categories' => $this->categories->map(fn($c) => [
                'id' => $c->id, 
                'name' => $c->name,
                'path' => $c->parent ? $c->parent->name . ' > ' . $c->name : $c->name
            ]),

            'tags_by_group' => $tagsByGroup,

            'variations' => [
                'gallery_control_group_id' => $galleryControlGroup ? $galleryControlGroup->id : null,
                'groups' => $this->variationGroups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'slug' => Str::slug($group->name),
                        'presentation' => $group->presentation_type,
                        'has_gallery_images' => (bool)$group->has_images,
                        'values' => $group->values->map(function ($val) use ($group) {
                            
                            $gallery = [];
                            // If this group controls gallery, load images for this value
                            if ($group->has_images) {
                                // Assuming 'images' relation exists on Value model (via `shop_variation_value_images`)
                                // Index::edit() used DB::table query. 
                                // We need to check if the relation exists in the Model.
                                // If not, we might need to rely on the eager loaded `images` relation we added in Controller show()
                                if ($val->relationLoaded('images')) {
                                    $gallery = $val->images->map(fn($i) => [
                                        'id' => $i->id ?? 0, // Pivot/Table usually has ID?
                                        // If it's the custom table, it might not have ID model?
                                        // Let's assume standard object structure
                                        'url' => url('storage/' . $i->image_path),
                                        'thumb_url' => url('storage/' . $i->image_path)
                                    ]);
                                }
                            }

                            return [
                                'id' => $val->id,
                                'label' => $val->caption,
                                'price' => number_format($val->price, 2, '.', ''),
                                'stock' => $val->stock_qty,
                                'is_default' => (bool)$val->is_default,
                                'presentation_image_url' => $val->presentation_image_path ? url('storage/' . $val->presentation_image_path) : null,
                                'display' => [
                                    'image_url' => $group->presentation_type == 'image' && $val->presentation_image_path 
                                        ? url('storage/' . $val->presentation_image_path) : null,
                                    'color_hex' => $group->presentation_type == 'color' ? $val->color_hex : null,
                                ],
                                'gallery' => $gallery
                            ];
                        }),
                    ];
                }),
            ],

            'defaults' => [
                'selected_values' => $defaultValues->mapWithKeys(function($val, $groupId) {
                    return [$groupId => $val->id];
                }),
                'default_computed_price' => number_format($defaultPrice, 2, '.', ''),
                'rule' => 'MAX_SELECTED_VARIATION_PRICE',
            ],

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
