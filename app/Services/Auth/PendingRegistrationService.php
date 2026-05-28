<?php

namespace App\Services\Auth;

use App\Models\PendingRegistration;
use Illuminate\Support\Facades\Hash;

class PendingRegistrationService
{
    /**
     * Create a new pending registration record.
     */
    public function create(array $data): PendingRegistration
    {
        // Cleanup expired records dynamically on new registrations
        $this->cleanupExpired();

        $expiresAt = now()->addMinutes(15);

        return PendingRegistration::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => Hash::make($data['password']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Clean up expired registrations.
     */
    public function cleanupExpired(): int
    {
        return PendingRegistration::expired()->delete();
    }
}
