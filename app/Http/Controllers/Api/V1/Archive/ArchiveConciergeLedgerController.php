<?php

namespace App\Http\Controllers\Api\V1\Archive;

use App\Http\Controllers\Controller;
use App\Http\Resources\Archive\ArchiveProductResource;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArchiveConciergeLedgerController extends Controller
{
    use ApiResponse;

    /**
     * Get paginated list of unique archive items the user has enquired about.
     * Ordered by most recent enquiry.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 20);

        // 1. Subquery: Find the MAX(id) (latest enquiry) for each archive_product_id for this user
        $latestEnquiryIdsQuery = ArchiveProductEnquiry::select(DB::raw('MAX(id)'))
            ->where('user_id', $userId)
            ->groupBy('archive_product_id');

        // 2. Fetch the full Enquiry models that match these MAX IDs
        //    Eager load the product and its images (for thumbnail)
        $ledgerEntries = ArchiveProductEnquiry::whereIn('id', $latestEnquiryIdsQuery)
            ->with(['product.images']) 
            ->orderBy('id', 'desc') // Latest enquiry first
            ->paginate($perPage);

        // 3. Transform the pagination result
        $data = $ledgerEntries->map(function ($enquiry) use ($userId) {
            $product = $enquiry->product;
            
            // Should not happen due to foreign key, but safety check
            if (!$product) {
                return null;
            }

            // Get total count of enquiries for this specific product by this user
            // We can do a quick count query here. For 20 items per page, 20 fast queries is acceptable.
            // A more complex join with GROUP BY could get counts in one go, but this is cleaner to read.
            $count = ArchiveProductEnquiry::where('user_id', $userId)
                ->where('archive_product_id', $product->id)
                ->count();

            // Resolve thumbnail
            $img = $product->images->sortBy('sort_order')->first();
            $thumbnailUrl = $img ? url(Storage::url($img->image_path)) : null;

            return [
                'item' => [
                    'id' => $product->id,
                    'title' => $product->title,
                    'primary_image_url' => $thumbnailUrl,
                    // 'sku' or 'lot_code' if available in schema. 
                    // ArchiveProduct schema inspection didn't show sku/lot_code explicitly in fillable/casts, 
                    // but we will omit if unsure to strictly follow "no invention".
                ],
                'enquiry_summary' => [
                    'last_enquiry_id' => $enquiry->id,
                    'last_enquiry_status' => $enquiry->status,
                    'last_enquiry_created_at' => $enquiry->created_at->toIso8601String(),
                    'enquiries_count_for_item' => $count,
                ],
                // Ledger row timestamps derived from the latest enquiry interaction
                'created_at' => $enquiry->created_at->toIso8601String(),
                // 'updated_at' => $enquiry->updated_at->toIso8601String(),
            ];
        })->filter(); // Remove nulls if any product invalid

        return $this->success(
            $data->values(), // Reset keys after filter
            'Concierge ledger fetched successfully.',
            200,
            [
                'pagination' => [
                    'page' => $ledgerEntries->currentPage(),
                    'per_page' => $ledgerEntries->perPage(),
                    'total' => $ledgerEntries->total(),
                    'last_page' => $ledgerEntries->lastPage(),
                ]
            ]
        );
    }

    /**
     * Show enquiry history for a single archive item.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $userId = $request->user()->id;

        // 1. Verify user has enquired about this product (Security/Access Check)
        $hasEnquired = ArchiveProductEnquiry::where('user_id', $userId)
            ->where('archive_product_id', $id)
            ->exists();

        if (!$hasEnquired) {
            return $this->error('Item not found in your ledger.', 404);
        }

        // 2. Fetch Product 
        // Reuse ArchiveProductResource for consistent "item detail" shape
        $product = ArchiveProduct::with(['images', 'images360', 'attachments', 'category'])
            ->findOrFail($id);
            
        // 3. Fetch Enquiries History
        $enquiries = ArchiveProductEnquiry::where('user_id', $userId)
            ->where('archive_product_id', $id)
            ->orderBy('id', 'desc') // Latest first
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'status' => $e->status,
                    'message' => $e->message,
                    // Check if 'admin_reply' exists in schema. The view_file was truncated.
                    // Assuming for now standard interaction, if not present it will be null nicely.
                    'admin_reply' => $e->admin_reply ?? null, 
                    'created_at' => $e->created_at->toIso8601String(),
                ];
            });

        return $this->success([
            'item' => new ArchiveProductResource($product),
            'enquiries' => $enquiries
        ], 'Ledger item details fetched successfully.');
    }
}
