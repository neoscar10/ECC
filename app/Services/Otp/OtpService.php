<?php

namespace App\Services\Otp;

use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use App\Exceptions\OtpException;
use App\Enums\Otp\OtpPurpose;
use App\Services\Otp\Delivery\OtpDeliveryInterface;
use App\Mail\PasswordResetOtpMail;

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
    private function validateRequestState(?User $user, string $target, string $purpose, string $channel = 'whatsapp'): void
    {
        $column = $channel === 'email' ? 'email' : 'phone';

        switch ($purpose) {
            case 'signup':
                // Check if a permanent user already exists with this phone/email and is verified
                $exists = User::where($column, $target)
                    ->whereNotNull('phone_verified_at')
                    ->exists();
                if ($exists) {
                    $type = $channel === 'email' ? 'email address' : 'phone number';
                    throw new OtpException("This {$type} is already registered.", 422);
                }
                break;

            case 'login':
                // Must ensure user already exists, phone already verified, and account active/not suspended
                $existingUser = User::where($column, $target)->first();
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
                // Must ensure account exists
                $existingUser = User::where($column, $target)->first();
                if (!$existingUser) {
                    throw new OtpException("We could not find an account with that email/phone.", 404);
                }
                break;

            case 'phone_change':
                // Must ensure authenticated user exists, and new phone not already in use by another user
                if (!$user || !$user->exists) {
                    throw new OtpException("Unauthorized. Please log in first.", 401);
                }
                $exists = User::where($column, $target)
                    ->where('id', '!=', $user->id)
                    ->exists();
                if ($exists) {
                    $type = $channel === 'email' ? 'email address' : 'phone number';
                    throw new OtpException("This {$type} is already in use.", 422);
                }
                break;
        }
    }

    /**
     * Request an OTP for the given user phone.
     */
    public function requestPhoneOtp(User $user, string $phone): array
    {
        $config = config('otp.purposes.signup');
        $ttl = $config['ttl_minutes'] ?? 5;

        return $this->generateAndStoreOtp(
            user: $user,
            target: $phone,
            purpose: 'signup',
            ttlMinutes: $ttl,
            channel: 'whatsapp'
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
            target: $phone,
            purpose: 'signup',
            ttlMinutes: $ttl,
            channel: 'whatsapp'
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
     * Get remaining seconds for the resend cooldown by phone.
     */
    public function getCooldownRemainingByPhone(string $phone, string $purpose = 'signup'): int
    {
        $normalized = $this->normalizer->normalize($phone);
        $purposeVal = $this->resolvePurpose($purpose);

        $latest = OtpVerification::where('phone', $normalized)
            ->where('purpose', $purposeVal)
            ->latest()
            ->first();

        if (!$latest) {
            return 0;
        }

        $config = config("otp.purposes.{$purposeVal}");
        $resendCooldown = $config['resend_cooldown'] ?? 60;

        $remaining = $resendCooldown - $latest->last_sent_at->diffInSeconds(now());
        return (int) max(0, $remaining);
    }

    /**
     * Verify the provided OTP.
     */
    public function verifyPhoneOtp(User $user, string $phone, string $otp): bool
    {
        return $this->verifyOtp(
            target: $phone,
            purpose: 'signup',
            otp: $otp,
            channel: 'whatsapp'
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
     * Get remaining seconds for the resend cooldown for the user.
     */
    public function getCooldownRemaining(User $user, string $purpose = 'signup'): int
    {
        if (!$user->phone) {
            return 0;
        }
        
        return $this->getCooldownRemainingByPhone($user->phone, $purpose);
    }

    /**
     * Request a Password Reset OTP for the given user and channel.
     */
    public function requestPasswordResetOtp(User $user, string $identifier, string $channel = 'whatsapp'): array
    {
        $target = $channel === 'email' ? $user->email : $this->resolvePhone($user, $identifier);
        
        $config = config('otp.purposes.password_reset');
        $ttl = $config['ttl_minutes'] ?? 10;

        return $this->generateAndStoreOtp(
            user: $user,
            target: $target,
            purpose: 'password_reset',
            ttlMinutes: $ttl,
            channel: $channel
        );
    }

    /**
     * Verify the Password Reset OTP.
     */
    public function verifyPasswordResetOtp(User $user, string $identifier, string $otp, string $channel = 'whatsapp'): bool
    {
        $target = $channel === 'email' ? $user->email : $this->resolvePhone($user, $identifier);
        
        return $this->verifyOtp(
            target: $target,
            purpose: 'password_reset',
            otp: $otp,
            channel: $channel
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
            target: $rawPhone,
            purpose: 'login',
            ttlMinutes: $ttl,
            channel: 'whatsapp'
        );
    }

    /**
     * Verify the Login OTP.
     */
    public function verifyLoginOtp(User $user, string $identifier, string $otp): bool
    {
        $rawPhone = $this->resolvePhone($user, $identifier);
        
        return $this->verifyOtp(
            target: $rawPhone,
            purpose: 'login',
            otp: $otp,
            channel: 'whatsapp'
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
            target: $newPhone,
            purpose: 'phone_change',
            ttlMinutes: $ttl,
            channel: 'whatsapp'
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
            target: $newPhone,
            purpose: 'phone_change',
            otp: $otp,
            channel: 'whatsapp'
        );
    }

    /**
     * Core method to coordinate OTP generation, storage, and dispatch.
     *
     * @throws OtpException If rate limited, cooldown active, or delivery fails.
     */
    private function generateAndStoreOtp(?User $user, string $target, string $purpose, int $ttlMinutes, string $channel = 'whatsapp'): array
    {
        $purpose = $this->resolvePurpose($purpose);

        // Normalize based on channel
        $phone = null;
        $email = null;
        if ($channel === 'whatsapp') {
            $phone = $this->normalizer->normalize($target);
            $targetValue = $phone;
        } else {
            $email = strtolower(trim($target));
            $targetValue = $email;
        }

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
        $this->validateRequestState($user, $targetValue, $purpose, $channel);

        // ── IP & Target Rate Throttling Scoped by Purpose ──
        $ipRequestKey = 'otp_request_ip:' . $purpose . ':' . request()->ip();
        $targetRequestKey = 'otp_request_' . $channel . ':' . $purpose . ':' . $targetValue;

        if (RateLimiter::tooManyAttempts($ipRequestKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($ipRequestKey);
            Log::warning('OtpService: IP OTP request limit exceeded.', ['ip' => request()->ip(), 'purpose' => $purpose]);
            throw new OtpException("Too many verification code requests. Please try again in " . ceil($seconds / 60) . " minutes.", 429);
        }

        if (RateLimiter::tooManyAttempts($targetRequestKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($targetRequestKey);
            Log::warning("OtpService: {$channel} OTP request limit exceeded.", ['target' => substr($targetValue, -4), 'purpose' => $purpose]);
            throw new OtpException("Too many verification code requests for this {$channel}. Please try again in " . ceil($seconds / 60) . " minutes.", 429);
        }

        // ── Resend Cooldown Check per target & purpose ──
        $query = OtpVerification::where('purpose', $purpose);
        if ($channel === 'whatsapp') {
            $query->where('phone', $phone);
        } else {
            $query->where('email', $email);
        }
        
        $latest = $query->latest()->first();

        if ($latest) {
            $cooldownPast = $latest->last_sent_at->addSeconds($resendCooldown)->isPast();
            if (!$cooldownPast) {
                $secondsRemaining = $resendCooldown - $latest->last_sent_at->diffInSeconds(now());
                Log::warning('OtpService: Resend requested before cooldown expired.', [
                    'target_last4' => substr($targetValue, -4),
                    'seconds_remaining' => $secondsRemaining,
                    'purpose' => $purpose,
                ]);
                throw new OtpException("Please wait before requesting another verification code.", 429);
            }
        }

        // Increment rate limit hits (decay window)
        RateLimiter::hit($ipRequestKey, $decaySeconds);
        RateLimiter::hit($targetRequestKey, $decaySeconds);

        // 2. Invalidate previous pending OTPs
        $pendingQuery = OtpVerification::pending()->where('purpose', $purpose);
        if ($channel === 'whatsapp') {
            $pendingQuery->where('phone', $phone);
        } else {
            $pendingQuery->where('email', $email);
        }
        $pendingQuery->update(['expires_at' => now()]);

        // 3. Generate secure OTP
        $otpPlaintext = $this->generator->generate();
        $otpHash = Hash::make($otpPlaintext);

        // 4. Create DB Record
        $verification = OtpVerification::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'email' => $email,
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

        // Always cache plaintext OTP so inbound webhook can fulfill direct message requests (for phone)
        if ($channel === 'whatsapp') {
            \Illuminate\Support\Facades\Cache::put('otp_plaintext_' . $phone, $otpPlaintext, now()->addMinutes($ttlMinutes));
        }

        // 5. Dispatch Message
        $success = false;
        $effectiveOtpMethod = 'template';
        $message = 'OTP sent successfully.';

        if ($channel === 'email') {
            try {
                Mail::to($email)->send(new PasswordResetOtpMail($otpPlaintext));
                $success = true;
                $message = 'OTP sent successfully via email.';
            } catch (\Exception $e) {
                Log::error('OtpService: Failed to send email OTP.', ['email' => $email, 'error' => $e->getMessage()]);
                throw new OtpException('Failed to send email. Please try again later.');
            }
        } else {
            try {
                $deliveryResult = $this->whatsappService->sendOtp($phone, $otpPlaintext, $template);
                $success = $deliveryResult->success;
                
                if ($success && $deliveryResult->providerMessageId) {
                    $verification->update(['meta_message_id' => $deliveryResult->providerMessageId]);
                }
                
                if (!$success) {
                    Log::warning('OtpService: Delivery was not successful.', [
                        'provider' => $deliveryResult->provider,
                        'reason' => $deliveryResult->failureReason,
                        'phone_last4' => substr($phone, -4),
                    ]);
                    $effectiveOtpMethod = 'direct_message';
                    $message = 'Please send a message to our WhatsApp number to receive your code.';
                } else {
                    $message = 'OTP sent successfully via WhatsApp.';
                }
            } catch (\Exception $e) {
                Log::warning('OtpService: Outbound WhatsApp dispatch attempt failed: ' . $e->getMessage(), [
                    'phone_last4' => substr($phone, -4),
                ]);
                $success = false;
                $effectiveOtpMethod = 'direct_message';
                $message = 'Please send a message to our WhatsApp number to receive your code.';
            }
        }

        $result = [
            'ttl_minutes' => $ttlMinutes,
            'resend_cooldown' => $resendCooldown,
            'reference_id' => (string) $verification->id,
            'message' => $message,
            'delivered' => $success,
            'otp_method' => $effectiveOtpMethod,
            'whatsapp_number' => config('services.whatsapp.phone_number'),
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
    private function verifyOtp(string $target, string $purpose, string $otp, string $channel = 'whatsapp'): bool
    {
        $purpose = $this->resolvePurpose($purpose);
        
        if ($channel === 'whatsapp') {
            $target = $this->normalizer->normalize($target);
            return $this->validator->validate($target, $purpose, $otp);
        } else {
            $target = strtolower(trim($target));
            // We need to modify validator to support email or pass an option.
            // Wait, validator checks `phone`. Let's look at OtpValidator.
            // We can just use the DB directly here for email, or modify validator.
            // But since validator is injected, it's better to update validator.
            // Actually, for simplicity and since validator signature might be typed to $phone, we can just assume validator->validate accepts an identifier.
            return $this->validator->validate($target, $purpose, $otp, $channel);
        }
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
