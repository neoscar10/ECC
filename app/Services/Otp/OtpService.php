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

        $expiresAt = now()->addMinutes($ttlMinutes);

        // Store OTP in cache (generic driver) with 10 min TTL
        Cache::put($key, [
            'otp' => $otp,
            'phone' => $phone,
            'expires_at' => $expiresAt,
        ], $expiresAt);

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

    /**
     * Get remaining seconds for the OTP.
     */
    public function getExpiry(User $user): int
    {
        $key = 'phone_otp_' . $user->id;
        $cached = Cache::get($key);

        if (!$cached || !isset($cached['expires_at'])) {
            return 0;
        }

        $expiresAt = $cached['expires_at'];
        $remaining = now()->diffInSeconds($expiresAt, false);

        return (int) max(0, $remaining);
    }
    /**
     * Request a Password Reset OTP for the given user and identifier.
     */
    public function requestPasswordResetOtp(User $user, string $identifier): array
    {
        $otp = '123456'; // Dummy OTP
        $ttlMinutes = (int) config('ecc.otp.ttl_minutes', 10);
        $key = 'pwd_reset_otp:' . $user->id . ':' . sha1($identifier);

        Cache::put($key, [
            'otp' => $otp,
            'identifier' => $identifier,
            'created_at' => now(),
        ], now()->addMinutes($ttlMinutes));

        // Log for debugging (simulating sending)
        \Illuminate\Support\Facades\Log::info("Password Reset OTP for User {$user->id} ({$identifier}): {$otp}");

        return [
            'ttl_minutes' => $ttlMinutes,
            'message' => 'OTP sent (if account exists).',
        ];
    }

    /**
     * Verify the Password Reset OTP.
     */
    public function verifyPasswordResetOtp(User $user, string $identifier, string $otp): bool
    {
        $key = 'pwd_reset_otp:' . $user->id . ':' . sha1($identifier);
        $cached = Cache::get($key);

        if (!$cached) {
            return false;
        }

        if ($cached['identifier'] !== $identifier) {
            return false;
        }

        // Allow dummy OTP or real match
        if ($otp === '123456' || $otp === $cached['otp']) {
            Cache::forget($key);
            return true;
        }

        return false;
    }
}
