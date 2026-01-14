<?php

namespace App\Http\Resources\Archive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArchiveCategoryResource extends JsonResource
{
    protected ?bool $isAccessible = null;

    /**
     * Set accessibility status explicitly if needed.
     */
    public function setAccessible(bool $status)
    {
        $this->isAccessible = $status;
        return $this;
    }

    public function toArray(Request $request): array
    {
        // Normalize image path logic
        $imagePath = $this->image_path;
        $imageUrl = null;

        if ($imagePath) {
            $normalized = preg_replace('#^public/#', '', str_replace('\\', '/', $imagePath));
            $imageUrl = url(Storage::url($normalized));
        }

        // Determine accessible status if not manually set
        $accessible = $this->isAccessible;
        if (is_null($accessible)) {
            // Default assumption if not passed: true (usually filtered out if false)
            // But if include_locked passed, we might need context. 
            // Ideally, controller sets this, or we trust the passed model has an attribute.
            $accessible = $this->resource->is_accessible ?? true;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $imageUrl,
            'visibility' => $this->visibility,
            'is_accessible' => (bool) $accessible,
            'created_at' => $this->created_at->toIso8601String(),
            // 'tiers' => $this->when($request->include_locked, ...) // Optional optimization
        ];
    }
}
