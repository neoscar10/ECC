<?php

namespace App\Services\Archive;

use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class ArchiveConciergeService
{
    /**
     * Get user's concierge ledger items.
     */
    public function getUserRequests(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $userId = $user->id;

        // 1. Subquery: Find the MAX(id) (latest enquiry) for each archive_product_id for this user
        $latestEnquiryIdsQuery = ArchiveProductEnquiry::select(DB::raw('MAX(id)'))
            ->where('user_id', $userId)
            ->groupBy('archive_product_id');

        // 2. Fetch the full Enquiry models that match these MAX IDs
        $ledgerEntries = ArchiveProductEnquiry::whereIn('id', $latestEnquiryIdsQuery)
            ->with(['product.images']) 
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // 3. Transform the pagination result (for service consumption)
        $ledgerEntries->getCollection()->transform(function ($enquiry) use ($userId) {
            $product = $enquiry->product;
            
            if (!$product) {
                return null;
            }

            $count = ArchiveProductEnquiry::where('user_id', $userId)
                ->where('archive_product_id', $product->id)
                ->count();

            $img = $product->images->sortBy('sort_order')->first();
            $thumbnailUrl = $img ? url(Storage::url($img->image_path)) : null;

            return [
                'id' => $product->id,
                'title' => $product->title,
                'thumbnail_url' => $thumbnailUrl,
                'status' => $enquiry->status,
                'status_label' => ucfirst($enquiry->status),
                'meta' => "Request #{$enquiry->id} • " . $enquiry->created_at->format('M d, Y'),
                'url' => route('pavilion.detail', ['type' => 'artifact', 'slugOrId' => $product->id]), // Fallback to detail
                'count' => $count,
                'created_at' => $enquiry->created_at,
            ];
        });

        return $ledgerEntries;
    }
}
