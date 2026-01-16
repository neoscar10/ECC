<?php

namespace App\Http\Resources\Archive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Archive\ArchiveAccessService;
use Illuminate\Support\Facades\Storage;

class ArchiveProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resolver = app(\App\Services\Archive\ArchiveAccessResolver::class);
        $user = $request->user();
        $userTier = $user ? $user->currentMembership?->membershipTier : null;
        
        $access = $resolver->resolveProductAccess($this->resource, $user, $userTier);
        
        // Images (Standard)
        $images = $this->images->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        });

        // 360 Images
        $images360 = $this->images360->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        });

        // Attachments
        // Logic: Return all attachments but with their own access objects. 
        $resource = $this->resource; // Capture for closure
        
        $attachments = $this->attachments->map(function ($att) use ($resolver, $resource, $user, $userTier) {
             $attAccess = $resolver->resolveAttachmentAccess($att, $resource, $user, $userTier);
             $isOpen = $attAccess['open'];

             return [
                 'id' => $att->id,
                 'type' => $att->type,
                 'heading' => $att->heading,
                 
                 'access' => $attAccess,
                 
                 // Content (Conditionally Hidden)
                 'line_text' => $isOpen ? $att->line_text : null,
                 'kv_key' => $isOpen ? $att->kv_key : null,
                 'kv_value' => $isOpen ? $att->kv_value : null,
                 'body' => $isOpen ? $att->body : null,
                 
                 'sort_order' => $att->sort_order
             ];
        });

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category_id' => $this->archive_category_id,
            'category_title' => $this->category->title ?? null,
            'category_summary' => [ // New Requirement
                 'id' => $this->archive_category_id,
                 'title' => $this->category->title ?? null,
                 'slug' => $this->category->slug ?? null,
            ],
            
            'description_unlocked' => $this->description_unlocked,
            'description_locked' => $access['open'] ? $this->description_locked : null,
            
            'price' => [
                'min' => $this->price_min_amount,
                'max' => $this->price_max_amount,
                'currency' => $this->currency,
            ],
            
            'quantity' => $this->quantity ?? 1, // New Field
            
            // Timing / Early Access Info
            'early_access_enabled' => (bool) $this->early_access_enabled,
            'is_live' => (bool) ($this->go_live_now || ($this->go_live_at && now()->gte($this->go_live_at))),
            'go_live_at' => $this->go_live_at,
            'go_live_formatted' => $this->go_live_at ? $this->go_live_at->format('d M Y, h:i A') : null,
            
            // Unified Access Object
            'access' => $access,
            
            // Legacy Backwards Compat (Optional but safe)
            'is_open' => $access['open'],
            
            'images' => $images,
            'images360' => $images360,
            'attachments' => $attachments,
        ];
    }
}
