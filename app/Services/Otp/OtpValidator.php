<?php

namespace App\Services\Otp;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Exceptions\OtpException;

class OtpValidator
{
    /**
     * Enforce validation policies on OTP verification.
     */
    private function validateVerifyState(string $phone, string $purpose): void
    {
        switch ($purpose) {
            case 'signup':
                $exists = \App\Models\User::where('phone', $phone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();
                if ($exists) {
                    throw new OtpException("This phone number is already registered.", 422);
                }
                break;

            case 'login':
                $existingUser = \App\Models\User::where('phone', $phone)->first();
                if (!$existingUser) {
                    throw new OtpException("We could not find an account with that email/phone.", 404);
                }
                if ($existingUser->is_suspended) {
                    $config = \App\Models\ContactConfig::first();
                    $email = $config->support_email ?? 'support@executivecricketclub.com';
                    $phoneMsg = $config->concierge_phone ?? '';
                    throw new OtpException("Your account has been suspended. Please contact support at {$email}" . ($phoneMsg ? " or call {$phoneMsg}" : "") . " to restore access.", 403);
                }
                break;

            case 'password_reset':
                $existingUser = \App\Models\User::where('phone', $phone)->first();
                if (!$existingUser) {
                    throw new OtpException("We could not find an account with that email/phone.", 404);
                }
                break;

            case 'phone_change':
                $user = auth()->user() ?? auth('api')->user();
                if (!$user || !$user->exists) {
                    throw new OtpException("Unauthorized. Please log in first.", 401);
                }
                $exists = \App\Models\User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();
                if ($exists) {
                    throw new OtpException("This phone number is already in use.", 422);
                }
                break;
        }
    }

    /**
     * Verify the provided OTP against the latest record.
     *
     * @param string $phone
     * @param string $purpose
     * @param string $otp
     * @return bool
     * @throws OtpException
     */
    public function validate(string $phone, string $purpose, string $otp): bool
    {
        if ($purpose instanceof \App\Enums\Otp\OtpPurpose) {
            $purpose = $purpose->value;
        }

        // Run validation policies for the state first
        $this->validateVerifyState($phone, $purpose);

        $ipVerifyKey = 'otp_verify_ip:' . $purpose . ':' . request()->ip();
        $phoneVerifyKey = 'otp_verify_phone:' . $purpose . ':' . $phone;

        if (RateLimiter::tooManyAttempts($ipVerifyKey, 10)) {
            $seconds = RateLimiter::availableIn($ipVerifyKey);
            Log::warning('OtpValidator: IP OTP verification limit exceeded.', ['ip' => request()->ip(), 'purpose' => $purpose]);
            throw new OtpException("Too many verification attempts. Please try again in " . ceil($seconds / 60) . " minutes.", 429);
        }

        if (RateLimiter::tooManyAttempts($phoneVerifyKey, 10)) {
            $seconds = RateLimiter::availableIn($phoneVerifyKey);
            Log::warning('OtpValidator: Phone OTP verification limit exceeded.', ['phone_last4' => substr($phone, -4), 'purpose' => $purpose]);
            throw new OtpException("Too many verification attempts for this phone number. Please try again in " . ceil($seconds / 60) . " minutes.", 429);
        }

        RateLimiter::hit($ipVerifyKey, 900); // 15 minutes = 900 seconds
        RateLimiter::hit($phoneVerifyKey, 900);

        $verification = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if (!$verification) {
            throw new OtpException("Invalid verification code.", 400);
        }

        if ($verification->isVerified()) {
            throw new OtpException("This verification code has already been used.", 400);
        }

        if ($verification->isExpired()) {
            throw new OtpException("The verification code has expired.", 400);
        }

        $config = config("otp.purposes.{$purpose}");
        $maxVerifyAttempts = $config['max_verify_attempts'] ?? $verification->max_attempts ?? 5;

        if ($verification->attempts >= $maxVerifyAttempts || !$verification->hasAttemptsRemaining()) {
            Log::warning('OtpValidator: Locked out due to max attempts exceeded.', [
                'phone_last4' => substr($phone, -4),
                'attempts' => $verification->attempts,
                'max_attempts' => $maxVerifyAttempts,
                'purpose' => $purpose,
            ]);
            throw new OtpException("Too many incorrect attempts. Please request a new verification code.", 400);
        }

        $verification->incrementAttempts();

        if (Hash::check($otp, $verification->otp_hash)) {
            $verification->markVerified();
            RateLimiter::clear($ipVerifyKey);
            RateLimiter::clear($phoneVerifyKey);
            return true;
        }

        // Check if the incremented attempts reached the max attempts
        if ($verification->refresh()->attempts >= $maxVerifyAttempts || !$verification->hasAttemptsRemaining()) {
            Log::warning('OtpValidator: Locked out due to max attempts exceeded.', [
                'phone_last4' => substr($phone, -4),
                'attempts' => $verification->attempts,
                'max_attempts' => $maxVerifyAttempts,
                'purpose' => $purpose,
            ]);
            throw new OtpException("Too many incorrect attempts. Please request a new verification code.", 400);
        }

        throw new OtpException("Invalid verification code.", 400);
    }
}

