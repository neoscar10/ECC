<?php

namespace App\Services\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\MembershipApplication;
use Illuminate\Support\Facades\DB;
use App\Services\Otp\OtpValidator;
use App\Exceptions\OtpException;

class RegistrationFinalizer
{
    public function __construct(protected OtpValidator $otpValidator)
    {}

    /**
     * Finalize the pending registration and create the permanent user.
     */
    public function finalize(string $phone, string $otp): User
    {
        // 1. Verify OTP first (which throws OtpException on failure)
        $this->otpValidator->validate($phone, 'signup', $otp);

        // 2. Load the pending registration record
        $pending = PendingRegistration::valid()
            ->where('phone', $phone)
            ->first();

        if (!$pending) {
            throw new OtpException("Registration session expired. Please register again.", 400);
        }

        // 3. Create permanent user and application in database transaction
        return DB::transaction(function () use ($pending) {
            $user = User::create([
                'name' => $pending->name,
                'email' => $pending->email,
                'phone' => $pending->phone,
                'password' => $pending->password_hash, // Already hashed, HashedCast will bypass NeedsRehash
                'phone_verified_at' => now(),
            ]);

            // Assign default user role
            $user->assignRole('user');

            // Create Initial Membership Application
            MembershipApplication::create([
                'user_id' => $user->id,
                'status' => 'draft',
                'current_step' => 'personal_details'
            ]);

            // Delete pending registration
            $pending->delete();

            return $user;
        });
    }
}
