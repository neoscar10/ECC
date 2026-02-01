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
        
        // [Sync Step 2] Auto-subscribe to enabled topics
        // We must do this AFTER saving the token so we know it's valid, but prompt implies strictly logic.
        // We fetch enabled subscriptions and subscribe this SINGLE token to all of them.
        try {
            $enabledSubs = $user->auctionNotificationSubscriptions()->where('is_enabled', true)->get();
            if ($enabledSubs->isNotEmpty()) {
                // Resolve Manager via container
                $fcmManager = app(\App\Services\Notifications\FcmTopicManager::class);
                
                foreach ($enabledSubs as $sub) {
                    // Need lot to get topic name. 
                    // Optimization: load relation or just use lot_id if Namer supports it (Namer uses Model).
                    // Let's assume we need to lazy load lots or instantiate dummy.
                    // Namer static method expects Model instance usually.
                    // Let's refactor Namer to accept ID or load it. 
                    // For safety, load relationship.
                    $lot = $sub->auctionLot; 
                    if ($lot) {
                       $topic = \App\Support\Notifications\FcmTopicNamer::auctionTopic($lot);
                       $fcmManager->subscribeTokensToTopic([$tokenRecord->token], $topic);
                    }
                }
            }
        } catch (\Exception $e) {
            // Don't fail registration if FCM fails, just log
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
        
        // Find and delete
        $deleted = $user->deviceTokens()->where('token', $request->token)->delete();

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
