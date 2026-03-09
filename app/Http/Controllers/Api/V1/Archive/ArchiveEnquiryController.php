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

    public function store(Request $request, \App\Services\Archive\ArchiveEnquiryService $service)
    {
        $request->validate([
            'archive_product_id' => 'required|exists:archive_products,id',
            'message' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        $enquiry = $service->createEnquiry(
            $user,
            $request->archive_product_id,
            $request->message
        );

        return $this->success([
            'id' => $enquiry->id,
            'status' => $enquiry->status,
            'message' => 'Enquiry submitted successfully.',
            'created_at' => $enquiry->created_at->toIso8601String(),
        ]);
    }
}
