<?php

namespace App\Services\Otp\Delivery;

use App\Services\Otp\OtpDeliveryResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DevOtpDeliveryService implements OtpDeliveryInterface
{
    /**
     * Dev mode OTP delivery: simply log the OTP and return a mock successful delivery result.
     */
    public function sendOtp(string $phone, string $otp, ?string $templateName = null): OtpDeliveryResult
    {
        // Safe logging in development mode
        Log::info("DEV OTP MODE: Generated OTP for phone [{$phone}] is [{$otp}] (Template: {$templateName})");

        return OtpDeliveryResult::success('dev', 'dev_mock_msg_id_' . Str::random(12));
    }
}
