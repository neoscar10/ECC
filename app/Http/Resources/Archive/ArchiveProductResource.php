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
        
        // Check View Mode
        $isClear = ($access['view_mode'] === 'clear');

        // Images (Standard) - Hide if blurred? User requirements say "sensitive fields". Images usually restricted.
        // Assuming images should be hidden or blurred. Frontend handles blur overlap usually, but API should be safe.
        // Let's hide images if not clear, unless we have a specific 'blurred' image variant (not implemented).
        // Safest: Return empty if blurred.
        $images = $isClear ? $this->images->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        }) : [];

        // 360 Images
        $images360 = $isClear ? $this->images360->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        }) : [];

        // Attachments
        // Logic: Return all attachments but with their own access objects. 
        // If Product is blurred, attachments are definitely not explicitly open unless public override?
        // But Resolver handles product dependency.
        // Here we just ensure CONTENT is striped if not open OR if product is blurred.
        
        $resource = $this->resource; // Capture for closure
        
        $attachments = $this->attachments->map(function ($att) use ($resolver, $resource, $user, $userTier, $isClear) {
             $attAccess = $resolver->resolveAttachmentAccess($att, $resource, $user, $userTier);
             
             // Strict Content Control: Open AND Product is Clear (unless attachment overrides standard logic?)
             // Actually, resolveAttachmentAccess checks resolveProductAccess['open'].
             // If product is BLUR, resolveProductAccess['open'] is FALSE.
             // So attAccess['open'] will be FALSE.
             // So content is already hidden by $isOpen check below.
             // BUT: We want to ensure we don't leak anything if logic drifts.
             
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
        // Filtering attachments list itself? No, list is usually visible, content is locked.
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category_id' => $this->archive_category_id,
            'category_title' => $this->category->title ?? null,
            'category_summary' => [ 
                 'id' => $this->archive_category_id,
                 'title' => $this->category->title ?? null,
                 'slug' => $this->category->slug ?? null,
            ],
            
            // Description: Hide unlocked description if blurred? 
            // Usually valid to show teaser. "description_unlocked" implies public/teaser.
            'description_unlocked' => $this->description_unlocked,
            
            'price' => [
                'min' => $this->price_min_amount,
                'max' => $this->price_max_amount,
                'currency' => $this->currency,
            ],
            
            'quantity' => $this->quantity ?? 1, 
            
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
