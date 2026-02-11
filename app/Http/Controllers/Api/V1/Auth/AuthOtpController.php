<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthOtpController extends Controller
{
    use ApiResponse;

    /**
     * Request an OTP for login (Dummy).
     */
    public function requestOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        // Normalize phone (minimal)
        $normalizedPhone = trim(str_replace(' ', '', $request->phone));

        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return $this->error('We could not find an account with that email/phone.', 404);
        }

        // Dummy Success - No storage, no sending
        return $this->success(null, 'OTP requested. Use any 6-digit code to continue.');
    }

    /**
     * Verify OTP and Login (Dummy).
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
             return $this->error('Validation Error', 422, $validator->errors());
        }

        // Normalize user lookup
        $normalizedPhone = trim(str_replace(' ', '', $request->phone));
        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return $this->error('We could not find an account with that email/phone.', 404);
        }

        // Dummy Verification: Any 6-digit OTP is accepted if user exists.
        
        // Login & Generate Token
        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user);
    }

    /**
     * Get the token array structure.
     * Replicated from AuthController to avoid inheritance complexity for now.
     */
    protected function respondWithToken(string $token, $user)
    {
        $application = \App\Domain\Membership\MembershipApplication::where('user_id', $user->id)
            ->where('status', '!=', 'rejected')
            ->latest()
            ->first();

        // Get active subscriptions logic (simplified or duplicated from AuthController)
        // Ideally this should be in a service, but for minimal changes we'll duplicate the logic for now 
        // OR instantiate AuthController locally (a bit hacky).
        // Let's duplicate the Subscription logic as it's cleaner than coupling controllers.
        
        $activeSubscriptions = $this->getActiveSubscriptions($user);

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
            'application' => $application,
            'active_subscriptions' => $activeSubscriptions
        ]);
    }

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
