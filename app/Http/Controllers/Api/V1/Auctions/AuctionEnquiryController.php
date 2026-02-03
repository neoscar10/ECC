<?php

namespace App\Http\Controllers\Api\V1\Auctions;

use App\Http\Controllers\Controller;
use App\Models\Auctions\AuctionEnquiry;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuctionEnquiryController extends Controller
{
    use ApiResponse;

    /**
     * List enquiries (Admin/Mobile Admin).
     */
    public function index(Request $request)
    {
        $query = AuctionEnquiry::with(['lot.images', 'user'])
            ->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contact_name', 'like', '%' . $search . '%')
                  ->orWhere('contact_email', 'like', '%' . $search . '%')
                  ->orWhereHas('lot', function($subQ) use ($search) {
                      $subQ->where('lot_no', 'like', '%' . $search . '%')
                           ->orWhere('title', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enquiries = $query->paginate($request->input('per_page', 10));

        return $this->success(
            $enquiries->items(),
            'List of Enquiries',
            200,
            [
                'current_page' => $enquiries->currentPage(),
                'last_page' => $enquiries->lastPage(),
                'total' => $enquiries->total(),
                'per_page' => $enquiries->perPage(),
            ]
        );
    }

    /**
     * Submit an enquiry for an auction lot.
     */
    public function store(Request $request, $auctionId)
    {
        // Replicating ArchiveEnquiryController logic but adapted for URL parameter
        $request->merge(['auction_lot_id' => $auctionId]);

        $request->validate([
            'auction_lot_id' => 'required|exists:auction_lots,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        // Check if user is authenticated (Route is middleware auth:api)
        // If route allows guest, we need to handle $user being null.
        // The prompt says "If archive requires auth -> require JWT". Core Archive routes require auth.
        
        $enquiry = AuctionEnquiry::create([
            'user_id' => $user->id,
            'auction_lot_id' => $request->auction_lot_id,
            'message' => $request->message,
            'contact_email' => $user->email,
            'contact_phone' => $user->phone ?? null,
            'contact_name' => $user->name,
            'status' => 'new',
        ]);

        // Log notification intent (mirroring archive)
        if ($email = env('ARCHIVE_ENQUIRY_NOTIFY_EMAIL') ?? env('AUCTION_ENQUIRY_NOTIFY_EMAIL')) { // Fallback or separate env? I'll stick to mostly mirroring or minimal addition.
             Log::info("Auction Enquiry received for lot #{$request->auction_lot_id}. Notification would go to admin.");
        }

        return $this->success([
            'id' => $enquiry->id,
            'status' => $enquiry->status,
            'message' => 'Enquiry submitted successfully.',
            'created_at' => $enquiry->created_at->toIso8601String(),
        ]);
    }
}
