<?php

namespace App\Services\Common;

use App\Models\ContactEnquiry;
use App\Models\User;

class ContactEnquiryService
{
    /**
     * Submit a new contact enquiry.
     *
     * @param User $user
     * @param array $data
     * @return ContactEnquiry
     */
    public function submit(User $user, array $data): ContactEnquiry
    {
        return ContactEnquiry::create([
            'user_id' => $user->id,
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'contact_phone' => $user->phone ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'new',
        ]);
    }
}
