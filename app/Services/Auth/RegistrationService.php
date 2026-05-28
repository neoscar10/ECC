<?php

namespace App\Services\Auth;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Exceptions\OtpException;

class RegistrationService
{
    public function __construct(
        protected PendingRegistrationService $pendingRegistrationService,
        protected RegistrationFinalizer $registrationFinalizer,
        protected OtpService $otpService
    ) {}

    /**
     * Initiate registration: validate, save pending, send OTP.
     */
    public function initiate(array $data): array
    {
        // 1. Create PendingRegistration record
        $pending = $this->pendingRegistrationService->create($data);

        // 2. Request OTP via OtpService
        $otpResult = $this->otpService->requestRegistrationOtp($pending->phone);

        return [
            'pending_registration_id' => $pending->id,
            'phone' => $pending->phone,
            'otp_result' => $otpResult,
        ];
    }

    /**
     * Complete registration: verify OTP, finalize user, return JWT or session status.
     */
    public function complete(string $phone, string $otp): User
    {
        return $this->registrationFinalizer->finalize($phone, $otp);
    }
}
