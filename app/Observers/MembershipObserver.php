<?php

namespace App\Observers;

use App\Models\Membership;
use App\Services\Notifications\FcmTopicManager;
use App\Support\Notifications\FcmTopicNamer;
use Illuminate\Support\Facades\Log;

class MembershipObserver
{
    /**
     * Handle the Membership "updated" event.
     */
    public function updated(Membership $membership): void
    {
        // Check if membership_tier_id changed
        if ($membership->isDirty('membership_tier_id')) {
            $oldTierId = $membership->getOriginal('membership_tier_id');
            $newTierId = $membership->membership_tier_id;
            
            $user = $membership->user;
            if (!$user) return; // Should have user

            // Retrieve all active device tokens
            $tokens = $user->deviceTokens()->pluck('token')->toArray();
            
            if (!empty($tokens)) {
                $fcmManager = app(FcmTopicManager::class);
                $namer = FcmTopicNamer::class;

                // 1. Unsubscribe from OLD tier (if existed)
                if ($oldTierId) {
                    $oldTopic = $namer::membershipTierTopic($oldTierId);
                    $fcmManager->unsubscribeTokensFromTopic($tokens, $oldTopic);
                }

                // 2. Subscribe to NEW tier (if exists)
                if ($newTierId) {
                    $newTopic = $namer::membershipTierTopic($newTierId);
                    $fcmManager->subscribeTokensToTopic($tokens, $newTopic);
                }

                // Log Sync
                Log::info('TOPIC_TIER_SWAP', [
                    'user_id' => $user->id,
                    'old_tier' => $oldTierId,
                    'new_tier' => $newTierId,
                    'tokens_count' => count($tokens)
                ]);
            }
        }
    }

    /**
     * Handle the Membership "created" event.
     * (Usually registration handles initial sync, but if admin creates membership manually, this covers it)
     */
    public function created(Membership $membership): void
    {
        // If created with a tier, sync it. 
        // Note: New user registration flow typically registers token AFTER membership creation or during. 
        // If token registered AFTER, Register endpoint handles it. 
        // If token registered BEFORE (rare/impossible for new user?), or existing user gets new membership.
        
        if ($membership->membership_tier_id) {
            $user = $membership->user;
             if (!$user) return;

            $tokens = $user->deviceTokens()->pluck('token')->toArray();
            
            if (!empty($tokens)) {
                $fcmManager = app(FcmTopicManager::class);
                $namer = FcmTopicNamer::class;
                
                $topic = $namer::membershipTierTopic($membership->membership_tier_id);
                $fcmManager->subscribeTokensToTopic($tokens, $topic);

                Log::info('TOPIC_TIER_SYNC_CREATED', [
                    'user_id' => $user->id,
                    'new_tier' => $membership->membership_tier_id,
                    'tokens_count' => count($tokens)
                ]);
            }
        }
    }
}
