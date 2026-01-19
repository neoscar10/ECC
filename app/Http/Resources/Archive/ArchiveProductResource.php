<?php

namespace App\Http\Resources\Archive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Archive\ArchiveAccessService;
use Illuminate\Support\Facades\Storage;

use App\Support\Archive\AccessIconNormalizer;

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
        
        // Strict Icon Normalization (Last Mile)
        $access['message']['icon'] = AccessIconNormalizer::normalize(
            $access['reason'] ?? null, 
            $access['view_mode'] ?? 'blocked'
        );
        
        // View Mode Checks
        $isClear = ($access['view_mode'] === 'clear');
        $isBlur = ($access['view_mode'] === 'blur');
        // If blocked, controller handles 403, or listing excludes it. 
        // But if we are here (e.g. show endpoint before controller update), be safe.

        // Images: Always return images for Clear OR Blur. (Frontend applies blur style)
        // Only hide if strictly blocked (which shouldn't happen here usually)
        $showImages = ($isClear || $isBlur);

        $images = $showImages ? $this->images->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        }) : [];

        $images360 = $showImages ? $this->images360->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        }) : [];

        // Attachments
        $resource = $this->resource; 
        
        $attachments = $this->attachments->map(function ($att) use ($resolver, $resource, $user, $userTier, $isClear) {
             $attAccess = $resolver->resolveAttachmentAccess($att, $resource, $user, $userTier);
             
             // Strict Icon Normalization (Last Mile for Attachments)
             $attAccess['message']['icon'] = AccessIconNormalizer::normalize(
                 $attAccess['reason'] ?? null,
                 $attAccess['view_mode'] ?? 'blocked'
             );
             
             // Content requires strict Clear access on attachment
             $attIsClear = ($attAccess['view_mode'] === 'clear');

             return [
                 'id' => $att->id,
                 'type' => $att->type,
                 'heading' => $att->heading,
                 
                 'access' => $attAccess,
                 
                 // Content (Conditionally Hidden)
                 'line_text' => $attIsClear ? $att->line_text : null,
                 'kv_key' => $attIsClear ? $att->kv_key : null,
                 'kv_value' => $attIsClear ? $att->kv_value : null,
                 'body' => $attIsClear ? $att->body : null,
                 
                 'sort_order' => $att->sort_order
             ];
        });
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category_id' => $this->archive_category_id,
            'category_title' => $this->category->title ?? null,
            // 'category_summary' removed
            
            // Description: Hide unlocked description if blurred?
            // User requested: "description_unlocked should become: If blur: return null OR a short preview"
            // We return null for blur to be safe/minimal as requested.
            'description_unlocked' => $isClear ? $this->description_unlocked : null,
            
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
            
            // 'is_open' removed
            
            'images' => $images,
            'images360' => $images360,
            'attachments' => $attachments,
        ];
    }
}
