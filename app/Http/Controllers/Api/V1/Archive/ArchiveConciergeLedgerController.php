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

    protected $conciergeService;

    public function __construct(\App\Services\Archive\ArchiveConciergeService $conciergeService)
    {
        $this->conciergeService = $conciergeService;
    }

    /**
     * Get paginated list of unique archive items the user has enquired about.
     * Ordered by most recent enquiry.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $paginator = $this->conciergeService->getUserRequests($request->user(), $perPage);

        return $this->success(
            $paginator->items(),
            'Concierge ledger fetched successfully.',
            200,
            [
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
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
