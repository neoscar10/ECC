<?php

namespace App\Services\Otp;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Request an OTP for the given user phone.
     * Returns generic info about the OTP request (TTL).
     */
    public function requestPhoneOtp(User $user, string $phone): array
    {
        $otp = '123456'; // Dummy OTP for now
        $ttlMinutes = 10;
        $key = 'phone_otp_' . $user->id;

        // Store OTP in cache (generic driver) with 10 min TTL
        Cache::put($key, [
            'otp' => $otp,
            'phone' => $phone
        ], now()->addMinutes($ttlMinutes));

        return [
            'ttl_minutes' => $ttlMinutes,
            'reference_id' => (string) Str::uuid(), // Dummy reference
            'message' => 'OTP sent successfully (Debugging: default is 123456)',
        ];
    }

    /**
     * Verify the provided OTP.
     */
    public function verifyPhoneOtp(User $user, string $phone, string $otp): bool
    {
        $key = 'phone_otp_' . $user->id;
        $cached = Cache::get($key);

        if (!$cached) {
            return false;
        }

        if ($cached['phone'] !== $phone) {
            return false;
        }

        // Check generic 6-digit match or the cached one (dummy logic)
        // Allowing '123456' always or the exact stored one
        if ($otp === '123456' || $otp === $cached['otp']) {
            Cache::forget($key);
            return true;
        }

        return false;
    }
}
