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
        $validator = Validator::make($request->all(), AuthRules::register(), [
            'email.unique' => 'This email is already registered.',
            'phone.unique' => 'This phone number is already registered.',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            $user = $this->authService->register($request->all());

            // Generate Token
            $token = auth('api')->login($user);

            // Return Success Response
            return $this->success([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
                'application' => $user->memberships()->where('status', 'draft')->latest()->first() ?? 
                               MembershipApplication::where('user_id', $user->id)->latest()->first(),
            ], 'Registration successful');

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'users_phone_unique')) {
                return $this->error('Validation Error', 422, [
                    'phone' => ['This phone number is already registered.']
                ]);
            }
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'users_email_unique')) {
                return $this->error('Validation Error', 422, [
                    'email' => ['This email is already registered.']
                ]);
            }
            return $this->error('Registration failed: A database error occurred.', 500);
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
