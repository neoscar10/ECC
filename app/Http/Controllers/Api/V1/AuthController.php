<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MembershipApplication;
use App\Support\ApiResponse;
use App\Services\Auth\AuthService;
use App\Validation\Auth\AuthRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $normalizedPhone = null;
        if ($request->has('phone') && !is_null($request->input('phone'))) {
            try {
                $normalizedPhone = app(\App\Services\Otp\PhoneNormalizer::class)->normalize($request->input('phone'));
                $request->merge(['phone' => $normalizedPhone]);
            } catch (\Exception $e) {
                return $this->error('Validation Error', 422, [
                    'phone' => [$e->getMessage() ?: 'The phone number format is invalid.']
                ]);
            }
        }

        $email = $request->input('email');

        // 1. Check for existing user by Email or Phone
        $existingUser = User::query()
            ->when($email, function ($query, $email) {
                return $query->where('email', $email);
            })
            ->when($normalizedPhone, function ($query, $phone) {
                return $query->orWhere('phone', $phone);
            })
            ->first();

        // CASE A: User exists AND is already verified (phone_verified_at is not null)
        if ($existingUser && !is_null($existingUser->phone_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this email or phone number is already registered and verified. Please log in.',
                'code' => 'ACCOUNT_VERIFIED_PLEASE_LOGIN',
                'errors' => [
                    'email' => ['An account with this email or phone number is already registered and verified. Please log in.'],
                    'phone' => ['An account with this email or phone number is already registered and verified. Please log in.'],
                ],
                'data' => [
                    'should_login' => true,
                    'email' => $existingUser->email,
                    'phone' => $existingUser->phone,
                ]
            ], 422);
        }

        // CASE B: User exists BUT is UNVERIFIED (phone_verified_at is null) -> Resume Registration (Option B)
        if ($existingUser && is_null($existingUser->phone_verified_at)) {
            $rules = AuthRules::register();
            $rules['email'] = 'required|email|unique:users,email,' . $existingUser->id;
            $rules['phone'] = 'required|unique:users,phone,' . $existingUser->id;

            $validator = Validator::make($request->all(), $rules, [
                'email.unique' => 'This email is already registered to another account.',
                'phone.unique' => 'This phone number is already registered to another account.',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation Error', 422, $validator->errors());
            }

            try {
                // Update user details with latest registration inputs
                $updateData = [
                    'name' => $request->input('name', $existingUser->name),
                    'email' => $request->input('email', $existingUser->email),
                    'phone' => $normalizedPhone ?? $existingUser->phone,
                ];

                if ($request->filled('password')) {
                    $updateData['password'] = Hash::make($request->input('password'));
                }

                $existingUser->update($updateData);

                // Ensure MembershipApplication draft exists
                if (!$this->authService->getPendingApplication($existingUser)) {
                    MembershipApplication::create([
                        'user_id' => $existingUser->id,
                        'status' => 'draft',
                        'current_step' => 'personal_details'
                    ]);
                }

                // Send a fresh OTP
                $otpResult = app(\App\Services\Otp\OtpService::class)->requestPhoneOtp($existingUser, $existingUser->phone);

                // Generate JWT token
                $token = auth('api')->login($existingUser);

                $responseData = [
                    'is_resumed_registration' => true,
                    'verified' => false,
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'user' => $existingUser,
                    'application' => $this->authService->getPendingApplication($existingUser),
                    'active_subscriptions' => $this->getActiveSubscriptions($existingUser),
                    
                    'ttl_minutes' => $otpResult['ttl_minutes'] ?? 5,
                    'otp_method' => $otpResult['otp_method'] ?? config('services.whatsapp.otp_method', 'template'),
                    'whatsapp_number' => $otpResult['whatsapp_number'] ?? config('services.whatsapp.phone_number', ''),
                ];

                if (isset($otpResult['dev_otp'])) {
                    $responseData['dev_otp'] = $otpResult['dev_otp'];
                }

                return $this->success($responseData, 'Registration resumed. A new verification OTP has been sent via WhatsApp.');
            } catch (\Exception $e) {
                return $this->error('Failed to resume registration: ' . $e->getMessage(), 500);
            }
        }

        // CASE C: New User -> Standard Registration
        $validator = Validator::make($request->all(), AuthRules::register(), [
            'email.unique' => 'This email is already registered.',
            'phone.unique' => 'This phone number is already registered.',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            $user = $this->authService->register($request->all());
            $otpResult = app(\App\Services\Otp\OtpService::class)->requestPhoneOtp($user, $user->phone);
            $token = auth('api')->login($user);

            $responseData = [
                'is_resumed_registration' => false,
                'verified' => false,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => $user,
                'application' => $this->authService->getPendingApplication($user),
                'active_subscriptions' => $this->getActiveSubscriptions($user),
                
                'ttl_minutes' => $otpResult['ttl_minutes'] ?? 5,
                'otp_method' => $otpResult['otp_method'] ?? config('services.whatsapp.otp_method', 'template'),
                'whatsapp_number' => $otpResult['whatsapp_number'] ?? config('services.whatsapp.phone_number', ''),
            ];

            if (isset($otpResult['dev_otp'])) {
                $responseData['dev_otp'] = $otpResult['dev_otp'];
            }

            return $this->success($responseData, 'Registration successful. Please verify OTP.');
        } catch (\Exception $e) {
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Log the user in (Get the token)
     */
    public function login(Request $request): JsonResponse
    {
        // 1. Light Validation (Accept email OR phone OR login)
        $request->validate(AuthRules::login());

        if (!$request->hasAny(['email', 'phone', 'login'])) {
             return $this->error('Please provide email or phone number.', 422);
        }

        // 2. Determine Identifier
        $identifier = $request->input('login') 
            ?? $request->input('email') 
            ?? $request->input('phone');

        // 3. Resolve User
        $user = $this->authService->resolveUser($identifier);

        if (! $user) {
            return $this->error('We could not find an account with that email/phone.', 404);
        }

        // Check if user is suspended
        if ($user->is_suspended) {
            $config = \App\Models\ContactConfig::first();
            $email = $config->support_email ?? 'support@executivecricketclub.com';
            $phone = $config->concierge_phone ?? '';
            $msg = "Your account has been suspended. Please contact support at {$email}" . ($phone ? " or call {$phone}" : "") . " to restore access.";
            
            return $this->error($msg, 403);
        }

        try {
            // Determine credentials for attempt
            $credentials = [
                filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone' => $user->phone ?? $user->email, // Using whatever matched
                'password' => $request->password
            ];
            
            // If it was phone, ensure we use Normalized one from DB
            if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $credentials = ['phone' => $user->phone, 'password' => $request->password];
            } else {
                $credentials = ['email' => $user->email, 'password' => $request->password];
            }

            $result = $this->authService->login($credentials);

            return $this->respondWithToken($result['token'], $result['user'], $result['application']);

        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return $this->error($e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->error('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the authenticated User
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        return $this->success([
            'user' => $user,
            'application' => $this->authService->getPendingApplication($user)
        ]);
    }

    /**
     * Log the user out (Invalidate the token)
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->success(null, 'Successfully logged out');
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $user = auth('api')->user();

        // 1. Verify Current Password
        if (!Hash::check($request->current_password, $user->password)) {
             return $this->error('The provided current password does not match your actual password.', 422, [
                 'current_password' => ['The provided current password does not match your actual password.']
             ]);
        }

        // 2. Update Password
        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->setRememberToken(\Illuminate\Support\Str::random(60));

        $user->save();

        // 3. Return Success (Token remains valid)
        return $this->success(null, 'Password changed successfully');
    }

    /**
     * Delete the authenticated user's account.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        try {
            $this->authService->deleteAccount($user);
            return $this->success(null, 'Account deleted successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to delete account.', 500);
        }
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, $user = null, $application = null): JsonResponse
    {
        $user = $user ?? auth('api')->user();
        $application = $application ?? $this->authService->getPendingApplication($user);

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
            'application' => $application,
            'active_subscriptions' => $this->authService->getActiveSubscriptions($user)
        ]);
    }

    /**
     * Get active subscriptions for the user to sync client state.
     */
    protected function getActiveSubscriptions($user)
    {
        if (!$user) return [];

        $namer = \App\Support\Notifications\FcmTopicNamer::class;
        
        $baseline = [
            $namer::globalTopic(),
            $namer::userTopic($user->id),
        ];

        // Tier Topic
        $currentMembership = $user->currentMembership;
        if ($currentMembership && $currentMembership->membership_tier_id) {
            $baseline[] = $namer::membershipTierTopic($currentMembership->membership_tier_id);
        }

        // Auction Subscriptions
        $enabledLotIds = $user->auctionNotificationSubscriptions()
            ->where('is_enabled', true)
            ->pluck('auction_lot_id')
            ->map(function($id) { return (string)$id; })
            ->values()
            ->all();

        return [
            'baseline_topics' => $baseline,
            'enabled_auction_lot_ids' => $enabledLotIds
        ];
    }
}
