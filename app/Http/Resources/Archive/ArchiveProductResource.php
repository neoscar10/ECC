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
        $service = app(ArchiveAccessService::class);
        $user = $request->user();
        $userTier = $user ? $service->resolveUserTier($user) : null;
        
        $isOpen = $service->isProductAccessible($this->resource, $userTier);
        $upgrade = $isOpen ? null : $service->getRecommendedUpgrade($this->resource);
        
        // Images
        $images = $this->images->map(function ($img) {
             return [
                 'id' => $img->id,
                 'url' => url(Storage::url($img->image_path)),
                 'sort_order' => $img->sort_order,
             ];
        });

        // Attachments
        $includeLocked = filter_var($request->query('include_locked_attachments', false), FILTER_VALIDATE_BOOLEAN);

        // Attachments
        $attachments = $this->attachments->map(function ($att) use ($service, $userTier, $includeLocked) {
             $attOpen = $service->isAttachmentAccessible($att, $this->resource, $userTier);
             
             if (!$attOpen && !$includeLocked) {
                 return null;
             }

             $upgrade = $attOpen ? null : $service->getAttachmentUpgrade($att);

             return [
                 'id' => $att->id,
                 'type' => $att->type,
                 'heading' => $att->heading,
                 // Access Status
                 'is_accessible' => $attOpen,
                 'lock_message' => $upgrade['message'] ?? null,
                 'recommended_upgrade' => $upgrade,
                 
                 // Content (Hidden if locked)
                 'line_text' => $attOpen ? $att->line_text : null,
                 'kv_key' => $attOpen ? $att->kv_key : null,
                 'kv_value' => $attOpen ? $att->kv_value : null,
                 'body' => $attOpen ? $att->body : null,
             ];
        })->filter()->values();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category_id' => $this->archive_category_id,
            'category_title' => $this->category->title ?? null,
            
            'description_unlocked' => $this->description_unlocked,
            'description_locked' => $isOpen ? $this->description_locked : null,
            
            'price' => [
                'min' => $this->price_min_amount,
                'max' => $this->price_max_amount,
                'currency' => $this->currency,
            ],
            
            'is_live' => (bool) ($this->go_live_now || ($this->go_live_at && now()->gte($this->go_live_at))),
            'go_live_at' => $this->go_live_at,
            'go_live_formatted' => $this->go_live_at ? $this->go_live_at->format('d M Y, h:i A') : null,
            
            'is_open' => $isOpen,
            'recommended_upgrade' => $upgrade,
            
            'images' => $images,
            'attachments' => $attachments,
        ];
    }
}
