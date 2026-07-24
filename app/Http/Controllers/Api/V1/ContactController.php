<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Support\ApiResponse;

class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Get contact configuration options.
     */
    public function config()
    {
        $config = \App\Models\ContactConfig::first();
        $subjects = \App\Models\ContactSubject::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        // Return DB values directly. If config doesn't exist, we return nulls.
        $conciergePhone = $config?->concierge_phone; // Nullable in DB
        $supportEmail = $config?->support_email;     // Nullable in DB
        $contactAddress = $config?->contact_address;   // Nullable in DB

        // If no subjects in DB, return empty array. Do not auto-fill defaults.
        $mappedSubjects = $subjects->map(function ($subject) {
            return [
                'key' => $subject->key,
                'label' => $subject->label,
            ];
        });

        return $this->success([
            'direct_lines' => [
                [
                    'key' => 'club_concierge',
                    'label' => 'Club Concierge',
                    'type' => 'phone',
                    'value' => $conciergePhone
                ],
                [
                    'key' => 'membership_support',
                    'label' => 'Membership Support',
                    'type' => 'email',
                    'value' => $supportEmail
                ],
                [
                    'key' => 'contact_address',
                    'label' => 'Club Contact Address',
                    'type' => 'address',
                    'value' => $contactAddress
                ]
            ],
            'contact_address' => $contactAddress,
            'subjects' => $mappedSubjects
        ]);
    }

    /**
     * Submit a new contact enquiry.
     */
    public function store(Request $request, \App\Services\Common\ContactEnquiryService $service)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|in:membership_upgrade,dining_reservations,general_feedback,other',
            'message' => 'required|string|min:5|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $enquiry = $service->submit($request->user(), $request->only(['subject', 'message']));

        // Optional: Dispatch notification here

        return $this->success([
            'id' => $enquiry->id,
            'subject' => $enquiry->subject,
            'status' => $enquiry->status,
            'created_at' => $enquiry->created_at->toIso8601String(),
            'message' => 'Enquiry submitted successfully.' // Friendly success message
        ], 201);
    }

    /**
     * List authenticated user's enquiries.
     */
    public function index(Request $request)
    {
        $enquiries = ContactEnquiry::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->query('per_page', 20));

        return $this->success([
            'data' => $enquiries->items(),
            'meta' => [
                'current_page' => $enquiries->currentPage(),
                'last_page' => $enquiries->lastPage(),
                'per_page' => $enquiries->perPage(),
                'total' => $enquiries->total(),
            ]
        ]);
    }
}
