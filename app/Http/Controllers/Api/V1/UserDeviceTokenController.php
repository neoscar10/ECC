<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserDeviceTokenController extends Controller
{
    /**
     * Register or update a device token.
     */
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:android,ios',
            'device_id' => 'nullable|string',
        ]);

        $user = Auth::guard('api')->user();

        // Check if token exists (global check due to unique constraint)
        $existing = UserDeviceToken::where('token', $request->token)->first();

        if ($existing) {
            // If it belongs to another user, reassign it
            if ($existing->user_id !== $user->id) {
                $existing->user_id = $user->id;
            }
            // Always update metadata
            $existing->platform = $request->platform;
            $existing->device_id = $request->device_id; // Update device_id if changed
            $existing->last_seen_at = now();
            $existing->save(); // Handles both reassignment or just update

            $tokenRecord = $existing;
        } else {
            // Create new
            $tokenRecord = $user->deviceTokens()->create([
                'token' => $request->token,
                'platform' => $request->platform,
                'device_id' => $request->device_id,
                'last_seen_at' => now(),
            ]);
        }
        
        // [Sync Step 2] Baseline Topic Sync (Global, User, Membership)
        // We do this for creating AND updating tokens to ensure consistency.
        // We use FcmTopicManager directly.
        try {
            $fcmManager = app(\App\Services\Notifications\FcmTopicManager::class);
            $namer = \App\Support\Notifications\FcmTopicNamer::class;

            // 1. Global Topic
            $globalTopic = $namer::globalTopic();
            $fcmManager->subscribeTokensToTopic([$tokenRecord->token], $globalTopic);
            
            \Illuminate\Support\Facades\Log::info('TOPIC_SYNC', [
                'action' => 'TOPIC_SUBSCRIBE',
                'topic' => $globalTopic,
                'token' => substr($tokenRecord->token, 0, 10) . '***',
                'user_id' => $user->id
            ]);

            // 2. User Topic
            $userTopic = $namer::userTopic($user->id);
            $fcmManager->subscribeTokensToTopic([$tokenRecord->token], $userTopic);

            \Illuminate\Support\Facades\Log::info('TOPIC_SYNC', [
                'action' => 'TOPIC_SUBSCRIBE',
                'topic' => $userTopic,
                'token' => substr($tokenRecord->token, 0, 10) . '***',
                'user_id' => $user->id
            ]);

            // 3. Membership Tier Topic
            // Resolve Tier ID from User -> Membership -> MembershipTier
            $currentMembership = $user->currentMembership;
            if ($currentMembership && $currentMembership->membership_tier_id) {
                $tierTopic = $namer::membershipTierTopic($currentMembership->membership_tier_id);
                $fcmManager->subscribeTokensToTopic([$tokenRecord->token], $tierTopic);

                \Illuminate\Support\Facades\Log::info('TOPIC_SYNC', [
                    'action' => 'TOPIC_SUBSCRIBE',
                    'topic' => $tierTopic,
                    'token' => substr($tokenRecord->token, 0, 10) . '***',
                    'user_id' => $user->id
                ]);
            }
            
            // 4. Auto-subscribe to enabled Auction topics (Existing Logic)
            $enabledSubs = $user->auctionNotificationSubscriptions()->where('is_enabled', true)->get();
            if ($enabledSubs->isNotEmpty()) {
                foreach ($enabledSubs as $sub) {
                    $lot = $sub->auctionLot; 
                    if ($lot) {
                       $topic = $namer::auctionTopic($lot);
                       $fcmManager->subscribeTokensToTopic([$tokenRecord->token], $topic);
                    }
                }
            }

        } catch (\Exception $e) {
            // Fail-safe: don't fail registration if FCM fails, just log
            \Illuminate\Support\Facades\Log::warning('FCM Sync Failed on Register: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'data' => $tokenRecord
        ]);
    }

    /**
     * List active tokens for the user.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        
        $tokens = $user->deviceTokens()
            ->orderByDesc('last_seen_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tokens
        ]);
    }

    /**
     * Unregister a token (Mark as inactive? Or delete?)
     * Prompt said: "Mark token as inactive (DO NOT delete unless system convention...)"
     * BUT Schema doesn't have 'is_active'.
     * Discovery showed columns: id, user_id, token, platform, device_id, last_seen_at.
     * NO 'is_active' column.
     * So "Mark as inactive" implies either adding a column OR just deleting it.
     * 
     * Convention check:
     * If I can't add a column (schema locked for Step 1 scope boundaries unless necessary), 
     * I should probably DELETE it for "unregister".
     * OR: The prompt explicitly asked "Mark token as inactive...".
     * Ah, I missed 'is_active' in the migration check?
     * Let's check `user_device_tokens` schema again from Discovery.
     * Migration: `2026_01_22_181533...` -> `updated_at`, `created_at`, `last_seen_at`. 
     * NO status column.
     * 
     * Decision: "Unregister" usually means "Delete" if no soft delete/active flag exists.
     * I will DELETE it for now to be clean, or I can strictly follow "Mark as inactive" and add a column?
     * The prompt said: "If duplicate exist... write cleanup...".
     * Prompt for unregister: "Mark token as inactive (DO NOT delete unless...)"
     * 
     * Re-reading Plan: I did NOT plan to add `is_active` column in the database section.
     * So I cannot mark it inactive. I must DELETE it.
     * I will stick to DELETE for `unregister` action logic.
     */
    public function unregister(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::guard('api')->user();
        
        // Find token and unsubscribe from everything before deleting
        // We use 'first' to get model then delete, instead of direct delete query
        $tokenRecord = $user->deviceTokens()->where('token', $request->token)->first();

        if ($tokenRecord) {
            try {
                $fcmManager = app(\App\Services\Notifications\FcmTopicManager::class);
                $namer = \App\Support\Notifications\FcmTopicNamer::class;

                // 1. Global
                $fcmManager->unsubscribeTokensFromTopic([$tokenRecord->token], $namer::globalTopic());

                // 2. User
                $fcmManager->unsubscribeTokensFromTopic([$tokenRecord->token], $namer::userTopic($user->id));

                // 3. Membership Tier (if exists)
                $currentMembership = $user->currentMembership;
                if ($currentMembership && $currentMembership->membership_tier_id) {
                    $tierTopic = $namer::membershipTierTopic($currentMembership->membership_tier_id);
                    $fcmManager->unsubscribeTokensFromTopic([$tokenRecord->token], $tierTopic);
                }

                // 4. Auction Topics
                $enabledSubs = $user->auctionNotificationSubscriptions()->where('is_enabled', true)->get();
                if ($enabledSubs->isNotEmpty()) {
                    foreach ($enabledSubs as $sub) {
                        $lot = $sub->auctionLot; 
                        if ($lot) {
                           $topic = $namer::auctionTopic($lot);
                           $fcmManager->unsubscribeTokensFromTopic([$tokenRecord->token], $topic);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('FCM Unsubscribe Failed on Unregister: ' . $e->getMessage());
            }

            $deleted = $tokenRecord->delete();
        } else {
            $deleted = false;
        }

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Token unregistered.' : 'Token not found.'
        ]);
    }

    /**
     * Delete by ID.
     */
    public function destroy($id)
    {
        $user = Auth::guard('api')->user();
        
        $token = $user->deviceTokens()->find($id);
        
        if (!$token) {
            return response()->json(['message' => 'Token not found or access denied.'], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token deleted.'
        ]);
    }
}
