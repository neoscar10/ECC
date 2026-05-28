<?php

namespace App\Services\Otp;

use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Exceptions\OtpException;
use App\Enums\Otp\OtpPurpose;
use App\Services\Otp\Delivery\OtpDeliveryInterface;

class OtpService
{
    public function __construct(
        protected OtpGenerator $generator,
        protected OtpValidator $validator,
        protected PhoneNormalizer $normalizer,
        protected OtpDeliveryInterface $whatsappService
    ) {}

    /**
     * Check if we should expose dev OTP in API/web responses.
     */
    public static function shouldExposeDevOtp(): bool
    {
        return config('otp.delivery_mode') === 'dev'
            && config('app.debug') === true;
    }

    /**
     * Resolve mixed purpose input to a standard string format.
     */
    private function resolvePurpose(string|OtpPurpose $purpose): string
    {
        if ($purpose instanceof OtpPurpose) {
            return $purpose->value;
        }

        $enum = OtpPurpose::tryFrom($purpose);
        if (!$enum) {
            throw new \InvalidArgumentException("Invalid OTP purpose: {$purpose}");
        }

        return $enum->value;
    }

    /**
     * Enforce request-level validation policies for specific OTP purposes.
     */
    private function validateRequestState(?User $user, string $phone, string $purpose): void
    {
        switch ($purpose) {
            case 'signup':
                // Check if a permanent user already exists with this phone number and is verified
                $exists = User::where('phone', $phone)
                    ->whereNotNull('phone_verified_at')
                    ->exists();
                if ($exists) {
                    throw new OtpException("This phone number is already registered.", 422);
                }
                break;

            case 'login':
                // Must ensure user already exists, phone already verified, and account active/not suspended
                $existingUser = User::where('phone', $phone)->first();
                if (!$existingUser) {
                    throw new OtpException("We could not find an account with that email/phone.", 404);
                }
                if (!$existingUser->phone_verified_at) {
                    throw new OtpException("Please verify your phone number before logging in.", 400);
                }
                if ($existingUser->is_suspended) {
                    $config = \App\Models\ContactConfig::first();
                    $email = $config->support_email ?? 'support@executivecricketclub.com';
                    $phoneMsg = $config->concierge_phone ?? '';
                    throw new OtpException("Your account has been suspended. Please contact support at {$email}" . ($phoneMsg ? " or call {$phoneMsg}" : "") . " to restore access.", 403);
                }
                break;

            case 'password_reset':
                // Must ensure account exists
                $existingUser = User::where('phone', $phone)->first();
                if (!$existingUser) {
                    throw new OtpException("We could not find an account with that email/phone.", 404);
                }
                break;

            case 'phone_change':
                // Must ensure authenticated user exists, and new phone not already in use by another user
                if (!$user || !$user->exists) {
                    throw new OtpException("Unauthorized. Please log in first.", 401);
                }
                $exists = User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();
                if ($exists) {
                    throw new OtpException("This phone number is already in use.", 422);
                }
                break;
        }
    }

    /**
     * Request an OTP for the given user phone.
     * Returns generic info about the OTP request (TTL).
     */
    public function requestPhoneOtp(User $user, string $phone): array
    {
        $config = config('otp.purposes.signup');
        $ttl = $config['ttl_minutes'] ?? 5;

        return $this->generateAndStoreOtp(
            user: $user,
            rawPhone: $phone,
            purpose: 'signup',
            ttlMinutes: $ttl
        );
    }

    /**
     * Request an OTP for a registration (no User model yet).
     */
    public function requestRegistrationOtp(string $phone): array
    {
        $config = config('otp.purposes.signup');
        $ttl = $config['ttl_minutes'] ?? 5;

        return $this->generateAndStoreOtp(
            user: null,
            rawPhone: $phone,
            purpose: 'signup',
            ttlMinutes: $ttl
        );
    }

    /**
     * Get remaining seconds for the OTP by phone.
     */
    public function getExpiryByPhone(string $phone, string $purpose = 'signup'): int
    {
        $normalized = $this->normalizer->normalize($phone);
        $purposeVal = $this->resolvePurpose($purpose);

        $verification = OtpVerification::pending()
            ->where('phone', $normalized)
            ->where('purpose', $purposeVal)
            ->latest()
            ->first();

        if (!$verification) {
            return 0;
        }

        $remaining = now()->diffInSeconds($verification->expires_at, false);
        return (int) max(0, $remaining);
    }

    /**
     * Verify the provided OTP.
     */
    public function verifyPhoneOtp(User $user, string $phone, string $otp): bool
    {
        return $this->verifyOtp(
            rawPhone: $phone,
            purpose: 'signup',
            otp: $otp
        );
    }

    /**
     * Get remaining seconds for the OTP.
     */
    public function getExpiry(User $user): int
    {
        $verification = OtpVerification::pending()
            ->where('user_id', $user->id)
            ->where('purpose', 'signup')
            ->latest()
            ->first();

        if (!$verification) {
            return 0;
        }

        $remaining = now()->diffInSeconds($verification->expires_at, false);
        return (int) max(0, $remaining);
    }

    /**
     * Request a Password Reset OTP for the given user and identifier.
     */
    public function requestPasswordResetOtp(User $user, string $identifier): array
    {
        $rawPhone = $this->resolvePhone($user, $identifier);
        $config = config('otp.purposes.password_reset');
        $ttl = $config['ttl_minutes'] ?? 10;

        return $this->generateAndStoreOtp(
            user: $user,
            rawPhone: $rawPhone,
            purpose: 'password_reset',
            ttlMinutes: $ttl
        );
    }

    /**
     * Verify the Password Reset OTP.
     */
    public function verifyPasswordResetOtp(User $user, string $identifier, string $otp): bool
    {
        $rawPhone = $this->resolvePhone($user, $identifier);
        
        return $this->verifyOtp(
            rawPhone: $rawPhone,
            purpose: 'password_reset',
            otp: $otp
        );
    }

    /**
     * Request a Login OTP for the given user and identifier.
     */
    public function requestLoginOtp(User $user, string $identifier): array
    {
        $rawPhone = $this->resolvePhone($user, $identifier);
        $config = config('otp.purposes.login');
        $ttl = $config['ttl_minutes'] ?? 5;

        return $this->generateAndStoreOtp(
            user: $user,
            rawPhone: $rawPhone,
            purpose: 'login',
            ttlMinutes: $ttl
        );
    }

    /**
     * Verify the Login OTP.
     */
    public function verifyLoginOtp(User $user, string $identifier, string $otp): bool
    {
        $rawPhone = $this->resolvePhone($user, $identifier);
        
        return $this->verifyOtp(
            rawPhone: $rawPhone,
            purpose: 'login',
            otp: $otp
        );
    }

    /**
     * Request a Phone Change OTP for the authenticated user and their new phone.
     */
    public function requestPhoneChangeOtp(User $user, string $newPhone): array
    {
        $config = config('otp.purposes.phone_change');
        $ttl = $config['ttl_minutes'] ?? 5;

        return $this->generateAndStoreOtp(
            user: $user,
            rawPhone: $newPhone,
            purpose: 'phone_change',
            ttlMinutes: $ttl
        );
    }

    /**
     * Verify the Phone Change OTP.
     */
    public function verifyPhoneChangeOtp(User $user, string $newPhone, string $otp): bool
    {
        $normalized = $this->normalizer->normalize($newPhone);

        // Run validation check: new phone must not be in use
        $exists = User::where('phone', $normalized)
            ->where('id', '!=', $user->id)
            ->exists();
        if ($exists) {
            throw new OtpException("This phone number is already in use.", 422);
        }

        return $this->verifyOtp(
            rawPhone: $newPhone,
            purpose: 'phone_change',
            otp: $otp
        );
    }

    /**
     * Core method to coordinate OTP generation, storage, and dispatch.
     *
     * @throws OtpException If rate limited, cooldown active, or WhatsApp delivery fails.
     */
    private function generateAndStoreOtp(?User $user, string $rawPhone, string $purpose, int $ttlMinutes): array
    {
        // 1. Normalize phone
        $phone = $this->normalizer->normalize($rawPhone);
        
        $purpose = $this->resolvePurpose($purpose);

        // Read purpose configurations
        $config = config("otp.purposes.{$purpose}", [
            'ttl_minutes' => $ttlMinutes,
            'rate_limit' => ['max_attempts' => 5, 'decay_seconds' => 900],
            'resend_cooldown' => 60,
            'max_verify_attempts' => 5,
            'whatsapp_template' => 'authentication',
        ]);

        $ttlMinutes = $config['ttl_minutes'] ?? $ttlMinutes;
        $maxAttempts = $config['rate_limit']['max_attempts'] ?? 5;
        $decaySeconds = $config['rate_limit']['decay_seconds'] ?? 900;
        $resendCooldown = $config['resend_cooldown'] ?? 60;
        $maxVerifyAttempts = $config['max_verify_attempts'] ?? 5;
        $template = $config['whatsapp_template'] ?? 'authentication';

        // Validate the state for this purpose
        $this->validateRequestState($user, $phone, $purpose);

        // ── IP & Phone Rate Throttling Scoped by Purpose ──
        $ipRequestKey = 'otp_request_ip:' . $purpose . ':' . request()->ip();
        $phoneRequestKey = 'otp_request_phone:' . $purpose . ':' . $phone;

        if (RateLimiter::tooManyAttempts($ipRequestKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($ipRequestKey);
            Log::warning('OtpService: IP OTP request limit exceeded.', ['ip' => request()->ip(), 'purpose' => $purpose]);
            throw new OtpException("Too many verification code requests. Please try again in " . ceil($seconds / 60) . " minutes.", 429);
        }

        if (RateLimiter::tooManyAttempts($phoneRequestKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($phoneRequestKey);
            Log::warning('OtpService: Phone OTP request limit exceeded.', ['phone_last4' => substr($phone, -4), 'purpose' => $purpose]);
            throw new OtpException("Too many verification code requests for this phone number. Please try again in " . ceil($seconds / 60) . " minutes.", 429);
        }

        // ── Resend Cooldown Check per phone & purpose ──
        $latest = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if ($latest) {
            $cooldownPast = $latest->last_sent_at->addSeconds($resendCooldown)->isPast();
            if (!$cooldownPast) {
                $secondsRemaining = $resendCooldown - $latest->last_sent_at->diffInSeconds(now());
                Log::warning('OtpService: Resend requested before cooldown expired.', [
                    'phone_last4' => substr($phone, -4),
                    'seconds_remaining' => $secondsRemaining,
                    'purpose' => $purpose,
                ]);
                throw new OtpException("Please wait before requesting another verification code.", 429);
            }
        }

        // Increment rate limit hits (decay window)
        RateLimiter::hit($ipRequestKey, $decaySeconds);
        RateLimiter::hit($phoneRequestKey, $decaySeconds);

        // 2. Invalidate previous pending OTPs for this phone and purpose
        OtpVerification::pending()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->update(['expires_at' => now()]);

        // 3. Generate secure OTP
        $otpPlaintext = $this->generator->generate();
        $otpHash = Hash::make($otpPlaintext);

        // 4. Create DB Record
        $verification = OtpVerification::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'purpose' => $purpose,
            'otp_hash' => $otpHash,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'last_sent_at' => now(),
            'resend_count' => $latest ? ($latest->resend_count + 1) : 0,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'attempts' => 0,
            'max_attempts' => $maxVerifyAttempts,
        ]);

        // 5. Dispatch WhatsApp Message
        $deliveryResult = $this->whatsappService->sendOtp($phone, $otpPlaintext, $template);

        // 6. Store Meta message ID if delivery was successful
        if ($deliveryResult->success && $deliveryResult->providerMessageId) {
            $verification->update(['meta_message_id' => $deliveryResult->providerMessageId]);
        }

        // 7. Log delivery outcome
        if (!$deliveryResult->success) {
            Log::warning('OtpService: Delivery was not successful.', [
                'provider' => $deliveryResult->provider,
                'reason' => $deliveryResult->failureReason,
                'retryable' => $deliveryResult->retryable,
                'phone_last4' => substr($phone, -4),
            ]);
        }

        $result = [
            'ttl_minutes' => $ttlMinutes,
            'reference_id' => (string) $verification->id,
            'message' => $deliveryResult->success
                ? 'OTP sent successfully via WhatsApp.'
                : 'OTP created but delivery may be delayed.',
            'delivered' => $deliveryResult->success,
        ];

        if (self::shouldExposeDevOtp()) {
            $result['dev_otp'] = $otpPlaintext;
            session(['ecc_dev_otp' => $otpPlaintext]);
        }

        return $result;
    }

    /**
     * Core method to coordinate OTP verification.
     */
    private function verifyOtp(string $rawPhone, string $purpose, string $otp): bool
    {
        $phone = $this->normalizer->normalize($rawPhone);
        $purpose = $this->resolvePurpose($purpose);
        
        return $this->validator->validate($phone, $purpose, $otp);
    }

    /**
     * Map a mixed identifier to a raw phone number.
     */
    private function resolvePhone(?User $user, string $identifier): string
    {
        if ($user && $user->phone) {
            return $user->phone;
        }
        
        return $identifier;
    }
}
