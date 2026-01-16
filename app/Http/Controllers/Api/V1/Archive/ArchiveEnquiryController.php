<?php

namespace App\Http\Controllers\Api\V1\Archive;

use App\Http\Controllers\Controller;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArchiveEnquiryController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $request->validate([
            'archive_product_id' => 'required|exists:archive_products,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        $enquiry = ArchiveProductEnquiry::create([
            'user_id' => $user->id,
            'archive_product_id' => $request->archive_product_id,
            'message' => $request->message,
            'contact_email' => $user->email,
            'contact_phone' => $user->phone ?? null,
            'contact_name' => $user->name,
            'status' => 'new',
        ]);

        // Optional: Log or Notify
        // Mail::to(config('app.admin_email'))->send(new EnquiryReceived($enquiry));
        if ($email = env('ARCHIVE_ENQUIRY_NOTIFY_EMAIL')) {
             Log::info("Enquiry received for product #{$request->archive_product_id}. Notification would go to $email");
        }

        return $this->success([
            'id' => $enquiry->id,
            'status' => $enquiry->status,
            'message' => 'Enquiry submitted successfully.',
            'created_at' => $enquiry->created_at->toIso8601String(),
        ]);
    }
}
