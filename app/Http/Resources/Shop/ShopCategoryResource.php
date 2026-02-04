<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'is_active' => (bool) $this->is_active,
            
            // Hierarchy Meta
            'has_children' => (bool) $this->has_children,
            'children_count' => (int) $this->children_count,
            
            // Contextual
            'breadcrumb' => $this->when($request->routeIs('*.show'), function() {
                // Generate breadcrumb path: root -> ... -> self
                $crumbs = [];
                $curr = $this->resource; // using resource model
                // If we loaded ancestors upstream, great. If not, we climb up.
                // Assuming not too deep.
                while($curr->parent) {
                    $curr = $curr->parent;
                    array_unshift($crumbs, [
                        'id' => $curr->id,
                        'name' => $curr->name,
                        'slug' => $curr->slug,
                    ]);
                }
                return $crumbs;
            }),

            'created_at' => $this->created_at->toIso8601String(),
            
            // Tree support - Recursive children if loaded
            'children' => ShopCategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
