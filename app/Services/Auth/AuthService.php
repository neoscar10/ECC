<?php

namespace App\Services\Auth;

use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AuthService
{
    /**
     * Register a new user and create an initial membership application.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            // Assign default role
            $user->assignRole('user');

            // Create Initial Membership Application
            MembershipApplication::create([
                'user_id' => $user->id,
                'status' => 'draft',
                'current_step' => 'personal_details'
            ]);

            return $user;
        });
    }

    /**
     * Request an OTP for a phone number.
     */
    public function requestOtp(string $phone): ?User
    {
        $normalizedPhone = $this->normalizePhone($phone);
        return User::where('phone', $normalizedPhone)->first();
    }

    /**
     * Verify an OTP for a phone number.
     */
    public function verifyOtp(string $phone, string $otp): ?User
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return null;
        }

        // Dummy Verification: Any 6-digit OTP is accepted if user exists.
        // In a real app, check DB/Cache here.
        return $user;
    }

    /**
     * Resolve user by login identifier (email or phone).
     */
    public function resolveUser(string $identifier): ?User
    {
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $userQuery = User::query();

        if ($isEmail) {
            $userQuery->where('email', $identifier);
        } else {
            $userQuery->where('phone', $this->normalizePhone($identifier));
        }

        return $userQuery->first();
    }

    /**
     * Attempt to login a user and return token + related data.
     */
    public function login(array $credentials, string $guard = 'api'): array
    {
        if (!$token = auth($guard)->attempt($credentials)) {
            throw new \Illuminate\Auth\AuthenticationException('Incorrect password. Please try again.');
        }

        $user = auth($guard)->user();
        
        return [
            'token' => $token,
            'user' => $user,
            'application' => $this->getPendingApplication($user),
            'active_subscriptions' => $this->getActiveSubscriptions($user)
        ];
    }

    /**
     * Get the pending membership application for a user.
     */
    public function getPendingApplication(User $user)
    {
        return MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();
    }

    /**
     * Get active subscriptions for FCM topics.
     */
    public function getActiveSubscriptions(User $user): array
    {
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
            ->map(fn($id) => (string)$id)
            ->values()
            ->all();

        return [
            'baseline_topics' => $baseline,
            'enabled_auction_lot_ids' => $enabledLotIds
        ];
    }

    /**
     * Safely delete a user account.
     */
    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user) {
            // 1. Unregister Device Tokens (Stops notifications)
            $user->deviceTokens()->each(function ($token) {
                try {
                    $fcmManager = app(\App\Services\Notifications\FcmTopicManager::class);
                    $namer = \App\Support\Notifications\FcmTopicNamer::class;
                    
                    $fcmManager->unsubscribeTokensFromTopic([$token->token], $namer::globalTopic());
                    $fcmManager->unsubscribeTokensFromTopic([$token->token], $namer::userTopic($token->user_id));
                    
                    $currentMembership = $token->user->currentMembership;
                    if ($currentMembership && $currentMembership->membership_tier_id) {
                        $fcmManager->unsubscribeTokensFromTopic([$token->token], $namer::membershipTierTopic($currentMembership->membership_tier_id));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('FCM Unsubscribe failed during account deletion: ' . $e->getMessage());
                }
                $token->delete();
            });

            // 2. Invalidate JWT Token
            auth('api')->logout();

            // 3. Soft Delete User
            $user->delete();
        });
    }

    /**
     * Minimal Phone Normalization.
     */
    private function normalizePhone(string $phone): string
    {
        return trim(str_replace(' ', '', $phone));
    }
}
