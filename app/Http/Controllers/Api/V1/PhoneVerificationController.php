<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Otp\OtpService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Exceptions\OtpException;

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

        $user = $request->user('api');

        if (!$user) {
            // Check for temporary registration JWT
            try {
                $token = \Tymon\JWTAuth\Facades\JWTAuth::parseToken();
                $payload = $token->getPayload();
                $pendingRegistrationId = $payload->get('pending_registration_id');
            } catch (\Exception $e) {
                return $this->error('Unauthorized. Please log in or register.', 401);
            }

            if (!$pendingRegistrationId) {
                return $this->error('Unauthorized. Please log in or register.', 401);
            }

            $pending = \App\Models\PendingRegistration::valid()->find($pendingRegistrationId);
            if (!$pending) {
                return $this->error('Registration session expired. Please register again.', 400);
            }

            try {
                $result = $this->otpService->requestRegistrationOtp($request->phone);
            } catch (OtpException $e) {
                return $this->error($e->getMessage(), $e->getCode() ?: 400);
            }

            return $this->success($result, 'OTP sent successfully.');
        }

        if ($user->phone_verified_at) {
            return $this->error('Phone already verified.', 400);
        }

        try {
            $result = $this->otpService->requestPhoneOtp($user, $request->phone);
        } catch (OtpException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }

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

        $user = $request->user('api');

        if (!$user) {
            // Check for temporary registration JWT
            try {
                $token = \Tymon\JWTAuth\Facades\JWTAuth::parseToken();
                $payload = $token->getPayload();
                $pendingRegistrationId = $payload->get('pending_registration_id');
            } catch (\Exception $e) {
                return $this->error('Unauthorized. Invalid verification session.', 401);
            }

            if (!$pendingRegistrationId) {
                return $this->error('Unauthorized. Invalid verification session.', 401);
            }

            try {
                // Complete registration: verify OTP and finalize user
                $finalizer = app(\App\Services\Auth\RegistrationFinalizer::class);
                $createdUser = $finalizer->finalize($request->phone, $request->otp);

                // Generate real user JWT token
                $newToken = auth('api')->login($createdUser);

                return $this->success([
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                    'user' => $createdUser,
                    'application' => $createdUser->memberships()->where('status', 'draft')->latest()->first() ?? 
                                   \App\Models\MembershipApplication::where('user_id', $createdUser->id)->latest()->first(),
                ], 'Phone verified and registration completed successfully.');
            } catch (OtpException $e) {
                return $this->error($e->getMessage(), $e->getCode() ?: 400);
            }
        }

        try {
            $success = $this->otpService->verifyPhoneOtp($user, $request->phone, $request->otp);
            if (!$success) {
                return $this->error('Invalid OTP.', 400);
            }
        } catch (OtpException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }

        // Update user
        $user->forceFill([
            'phone' => $request->phone,
            'phone_verified_at' => now(),
        ])->save();

        return $this->success(['verified' => true], 'Phone verified successfully.');
    }
}
