<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use App\Services\Auth\AuthService;
use App\Validation\Auth\AuthRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Exceptions\OtpException;

class AuthOtpController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Request an OTP for login (Dummy).
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), AuthRules::requestOtp());

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            $otpData = $this->authService->requestOtp($request->phone);
            if (!$otpData) {
                return $this->error('We could not find an account with that email/phone.', 404);
            }
        } catch (OtpException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }

        $responseData = [
            'ttl_minutes' => $otpData['ttl_minutes'],
            'otp_method' => $otpData['otp_method'] ?? config('services.whatsapp.otp_method', 'template'),
            'whatsapp_number' => $otpData['whatsapp_number'] ?? config('services.whatsapp.phone_number', '')
        ];

        if (isset($otpData['dev_otp'])) {
            $responseData['dev_otp'] = $otpData['dev_otp'];
        }

        return $this->success($responseData, $otpData['message']);
    }

    /**
     * Verify OTP and Login (Dummy).
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), AuthRules::verifyOtp());

        if ($validator->fails()) {
             return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            $user = $this->authService->verifyOtp($request->phone, $request->otp);
            if (!$user) {
                return $this->error('Invalid OTP or account not found.', 404);
            }
        } catch (OtpException $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
        
        // Generate Token
        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, $user)
    {
        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
            'application' => $this->authService->getPendingApplication($user),
            'active_subscriptions' => $this->authService->getActiveSubscriptions($user)
        ]);
    }
}
