<?php

namespace App\Services\Otp\Delivery;

use App\Services\Otp\OtpDeliveryResult;

interface OtpDeliveryInterface
{
    /**
     * Send an OTP to the given phone number.
     *
     * @param string $phone E.164 formatted phone number.
     * @param string $otp   Plaintext 6-digit OTP.
     * @param string|null $templateName Custom template name/identifier.
     * @return OtpDeliveryResult
     */
    public function sendOtp(string $phone, string $otp, ?string $templateName = null): OtpDeliveryResult;
}
