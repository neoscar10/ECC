<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        try {
            return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                // 1. Create User
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => $request->password,
                ]);

                // 2. Assign Role (Standard 'user' role)
                // We use 'web' guard role, ensure model picks it up. 
                // Spatie usually uses default guard from config if not specified.
                $user->assignRole('user');

                // 3. Create Application
                $application = \App\Domain\Membership\MembershipApplication::create([
                    'user_id' => $user->id,
                    'status' => 'draft',
                    'current_step' => 'personal_details'
                ]);

                // 4. Generate Token
                $token = auth('api')->login($user);

                // 5. Return Success Response
                return $this->success([
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                    'user' => $user,
                    'application' => $application,
                ], 'Registration successful');
            });

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
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!$request->hasAny(['email', 'phone', 'login'])) {
             return $this->error('Please provide email or phone number.', 422);
        }

        // 2. Determine Identifier
        $identifier = $request->input('login') 
            ?? $request->input('email') 
            ?? $request->input('phone');

        // 3. Detect Format (Email vs Phone)
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        // 4. Build Credentials
        if ($isEmail) {
            $credentials = [
                'email' => $identifier,
                'password' => $request->input('password')
            ];
        } else {
            // Minimal Phone Normalization (trim only as per instructions)
            $normalizedPhone = trim(str_replace(' ', '', $identifier));
            $credentials = [
                'phone' => $normalizedPhone,
                'password' => $request->input('password')
            ];
        }

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->error('Unauthorized', 401);
        }

        $user = auth('api')->user();
        $application = \App\Domain\Membership\MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();

        return $this->respondWithToken($token, $user, $application);
    }

    /**
     * Get the authenticated User
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $application = \App\Domain\Membership\MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();

        return $this->success([
            'user' => $user,
            'application' => $application
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
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, $user = null, $application = null): JsonResponse
    {
        $user = $user ?? auth('api')->user();

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
            'application' => $application,
            'active_subscriptions' => $this->getActiveSubscriptions($user) // [NEW] Sync Data
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
