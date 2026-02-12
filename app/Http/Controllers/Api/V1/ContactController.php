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

        // Fallback defaults if DB is empty (should be seeded, but good to have)
        $conciergePhone = $config?->concierge_phone ?? '+44 (0) 20 7123 4567';
        $supportEmail = $config?->support_email ?? 'members@executivecricket.club';

        $mappedSubjects = $subjects->isEmpty() 
            ? [
                ['key' => 'membership_upgrade', 'label' => 'Membership Upgrade'],
                ['key' => 'dining_reservations', 'label' => 'Dining Reservations'],
                ['key' => 'general_feedback', 'label' => 'General Feedback'],
                ['key' => 'other', 'label' => 'Other']
            ]
            : $subjects->map(function ($subject) {
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
                ]
            ],
            'subjects' => $mappedSubjects
        ]);
    }

    /**
     * Submit a new contact enquiry.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|in:membership_upgrade,dining_reservations,general_feedback,other',
            'message' => 'required|string|min:5|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $user = $request->user();

        $enquiry = ContactEnquiry::create([
            'user_id' => $user->id,
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'contact_phone' => $user->phone ?? null,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'new',
        ]);

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
