<?php

namespace App\Http\Resources\Vault;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VaultItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Absolute Image URLs
        $thumbnailUrl = $this->display_image_url; // Accessor handles fallback
        // For now, cover/gallery/360 are placeholders or mapped from source if available
        // The spec asks for Thumbnail + Cover. We'll use the same URL for both if only one exists, 
        // or check if source has dedicated covers.
        
        $sourceType = $this->source_type;
        $sourceId = $this->source_id;
        
        $sourceData = [
            'type' => $sourceType,
            'id' => $sourceId,
            'title' => $this->item_title, // Fallback title
            'api_detail_path' => null,
            'extra' => [],
        ];

        // Enrich Source Data
        if ($sourceType === 'archive_product') {
            $sourceData['api_detail_path'] = "/api/v1/archive/products/{$sourceId}";
        } elseif ($sourceType === 'auction_lot' || $sourceType === 'auction') {
             $sourceData['type'] = 'auction'; // Normalize 'auction_lot' to 'auction' for mobile
             $sourceData['api_detail_path'] = "/api/v1/auctions/{$sourceId}";
             // Could add 'ends_at' if we loaded the relation
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'removed_at' => $this->removed_at?->toIso8601String(),

            'display' => [
                'title' => $this->item_title,
                'subtitle' => $this->currency . ' ' . number_format($this->price, 2),
                'ref' => $this->item_ref,
                'currency' => $this->currency,
                'price' => (string) $this->price,
                // Description might need to come from source if not snapshotted
                'description' => $this->notes, 
            ],

            'media' => [
                'thumbnail' => [
                    'url' => $thumbnailUrl,
                    'path' => $this->item_image_url, // Raw path for debugging
                ],
                'cover' => [
                    'url' => $thumbnailUrl, // Use same for now unless we look up source images
                    'path' => $this->item_image_url,
                ],
                // Placeholder for gallery/360
                'gallery' => [],
                'image_360' => null,
            ],

            'source' => $sourceData,

            'acquisition' => [
                'channel' => $sourceType === 'auction_lot' ? 'auction' : $sourceType,
                'sale_id' => $this->sale_context_id,
                'sale_price' => (string) $this->price,
                'currency' => $this->currency,
                'sold_at' => $this->locked_at?->toIso8601String(), // Approx
            ],

            // Only relevant for detail view, but harmless in list
            'release' => [
                'can_user_release' => false,
                'status' => $this->status === 'removed' ? 'completed' : 'not_requested',
                'removed_note' => $this->status === 'removed' ? $this->notes : null,
            ],
        ];
    }
}
