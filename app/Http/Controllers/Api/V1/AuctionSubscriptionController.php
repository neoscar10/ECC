<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionNotificationSubscription;
use App\Services\Notifications\FcmTopicManager;
use App\Support\Notifications\FcmTopicNamer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuctionSubscriptionController extends Controller
{
    protected $fcmManager;

    public function __construct(FcmTopicManager $fcmManager)
    {
        $this->fcmManager = $fcmManager;
    }

    /**
     * Toggle subscription for an auction.
     * PUT /auctions/{lot}/notification-subscription
     */
    public function toggle(Request $request, $lotId)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $user = Auth::guard('api')->user();
        
        // Find Lot or 404
        $lot = AuctionLot::findOrFail($lotId);

        // Optional: Check permissions via AuctionAccessResolverService if strictly required.
        // For Step 2, we assume authenticated users can subscribe unless "permissions" are blocked.
        // We will proceed with standard authorized toggle.

        $subscription = AuctionNotificationSubscription::updateOrCreate(
            ['user_id' => $user->id, 'auction_lot_id' => $lot->id],
            ['is_enabled' => $request->enabled] // This updates timestamp too if changed
        );

        // Sync FCM
        $activeTokens = $user->deviceTokens()->pluck('token')->toArray();
        $topic = FcmTopicNamer::auctionTopic($lot);

        if (!empty($activeTokens)) {
            if ($request->enabled) {
                $this->fcmManager->subscribeTokensToTopic($activeTokens, $topic);
            } else {
                $this->fcmManager->unsubscribeTokensFromTopic($activeTokens, $topic);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $request->enabled ? 'Subscribed to auction notifications.' : 'Unsubscribed from auction notifications.',
            'data' => [
                'is_enabled' => $subscription->is_enabled
            ]
        ]);
    }

    /**
     * List my subscriptions.
     * GET /me/auction-notification-subscriptions
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        $subscriptions = $user->auctionNotificationSubscriptions()
            ->where('is_enabled', true)
            ->with(['auctionLot' => function($q) {
                $q->select('id', 'title', 'lot_no', 'status', 'ends_at');
            }])
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $subscriptions
        ]);
    }
}
