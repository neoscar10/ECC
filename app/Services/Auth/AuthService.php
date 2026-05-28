<?php

namespace App\Services\Auth;

use App\Models\MembershipApplication;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function __construct(
        protected \App\Services\Otp\PhoneNormalizer $phoneNormalizer
    ) {}
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
     * Request an OTP for a user identifier (email/phone).
     */
    public function requestOtp(string $identifier): ?array
    {
        $user = $this->resolveUser($identifier);
        
        if (!$user) {
            return null;
        }

        return app(\App\Services\Otp\OtpService::class)->requestLoginOtp($user, $identifier);
    }

    /**
     * Verify an OTP for a user identifier.
     */
    public function verifyOtp(string $identifier, string $otp): ?User
    {
        $user = $this->resolveUser($identifier);

        if (!$user) {
            return null;
        }

        $isValid = app(\App\Services\Otp\OtpService::class)->verifyLoginOtp($user, $identifier, $otp);

        return $isValid ? $user : null;
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
            try {
                $userQuery->where('phone', $this->normalizePhone($identifier));
            } catch (\Exception $e) {
                return null;
            }
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
     * Normalize Phone using PhoneNormalizer.
     */
    private function normalizePhone(string $phone): string
    {
        return $this->phoneNormalizer->normalize($phone);
    }
}
