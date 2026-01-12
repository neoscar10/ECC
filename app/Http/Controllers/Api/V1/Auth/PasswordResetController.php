<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Request an OTP for password reset.
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required_without_all:email,phone',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
        ]);

        $identifier = $request->input('identifier') 
            ?? $request->input('email') 
            ?? $request->input('phone');

        // Try to find user by email or phone
        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        // Security: Always return success to prevent user enumeration
        if ($user) {
            $data = $this->otpService->requestPasswordResetOtp($user, $identifier);
            return response()->json([
                'success' => true,
                'message' => $data['message'],
                'data' => ['ttl_minutes' => $data['ttl_minutes']],
                'meta' => null,
                'errors' => null,
            ]);
        }

        // Fake response if user not found
        return response()->json([
            'success' => true,
            'message' => 'OTP sent (if account exists).',
            'data' => ['ttl_minutes' => 10], // Default mock TTL
            'meta' => null,
            'errors' => null,
        ]);
    }

    /**
     * Reset password using OTP.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'identifier' => 'required_without_all:email,phone',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'otp' => 'required|string',
            'password' => 'required|confirmed|min:8',
        ]);

        $identifier = $request->input('identifier') 
            ?? $request->input('email') 
            ?? $request->input('phone');
        
        $otp = $request->input('otp');

        $user = User::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if (!$user) {
             return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.', // Generic error
                'data' => null,
                'meta' => null,
                'errors' => null,
            ], 400);
        }

        if ($this->otpService->verifyPasswordResetOtp($user, $identifier, $otp)) {
            $user->password = Hash::make($request->input('password'));
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
                'data' => null,
                'meta' => null,
                'errors' => null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired OTP.',
            'data' => null,
            'meta' => null,
            'errors' => null, // keeping strict consistent format
        ], 400);
    }
}
