<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Otp\OtpService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PhoneVerificationController extends Controller
{
    use ApiResponse;

    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 400, $validator->errors());
        }

        $user = $request->user();
        if ($user->phone_verified_at) {
            return $this->error('Phone already verified.', 400);
        }

        $result = $this->otpService->requestPhoneOtp($user, $request->phone);

        return $this->success($result, 'OTP sent successfully.');
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 400, $validator->errors());
        }

        $user = $request->user();
        $success = $this->otpService->verifyPhoneOtp($user, $request->phone, $request->otp);

        if (!$success) {
            return $this->error('Invalid OTP.', 400);
        }

        // Update user
        $user->forceFill([
            'phone' => $request->phone,
            'phone_verified_at' => now(),
        ])->save();

        return $this->success(['verified' => true], 'Phone verified successfully.');
    }
}
