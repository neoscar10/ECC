<?php

namespace App\Http\Resources\Archive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConciergeLedgerItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->resource is the flattened array from ArchiveConciergeService
        return [
            'item' => [
                'id' => $this->resource['id'],
                'title' => $this->resource['title'],
                'primary_image_url' => $this->resource['thumbnail_url'],
            ],
            'enquiry_summary' => [
                'last_enquiry_status' => $this->resource['status'],
                'last_enquiry_created_at' => is_scalar($this->resource['created_at']) 
                    ? $this->resource['created_at'] 
                    : $this->resource['created_at']->toIso8601String(),
                'enquiries_count_for_item' => $this->resource['count'],
            ],
            'created_at' => is_scalar($this->resource['created_at']) 
                ? $this->resource['created_at'] 
                : $this->resource['created_at']->toIso8601String(),
        ];
    }
}
