<?php

namespace App\Services\Otp;

/**
 * Data Transfer Object representing the result of an OTP delivery attempt.
 *
 * This DTO isolates the rest of the application from raw HTTP response
 * details, providing a clean contract for delivery outcomes.
 */
class OtpDeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $provider,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $failureReason = null,
        public readonly bool $retryable = false,
    ) {}

    /**
     * Create a successful delivery result.
     */
    public static function success(string $provider, string $messageId): self
    {
        return new self(
            success: true,
            provider: $provider,
            providerMessageId: $messageId,
        );
    }

    /**
     * Create a failed delivery result.
     */
    public static function failure(string $provider, string $reason, bool $retryable = false): self
    {
        return new self(
            success: false,
            provider: $provider,
            failureReason: $reason,
            retryable: $retryable,
        );
    }

    /**
     * Create a skipped delivery result (e.g., when WhatsApp is not configured).
     */
    public static function skipped(string $provider, string $reason): self
    {
        return new self(
            success: false,
            provider: $provider,
            failureReason: $reason,
            retryable: false,
        );
    }
}
