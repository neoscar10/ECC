<?php

namespace App\Services\Auctions;

use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use App\Support\Archive\AccessIconNormalizer;
use Carbon\Carbon;

class AuctionAccessResolverService
{
    /**
     * Resolve the access object for an auction lot.
     * Mirrors ArchiveAccessResolver::resolveProductAccess
     */
    /**
     * Determine if a user can subscribe to the auction lot's websocket channel.
     * This decouples channel auth from the resolve() response structure (e.g. 'has_visibility').
     */
    public function canSubscribeToLotChannel(AuctionLot $lot, ?User $user): bool
    {
        if (!$user) return false;
        
        // Public -> Always allowed
        if ($lot->restriction_mode === 'public') {
            return true;
        }

        // Check standard restriction logic (hierarchical, allowlist, etc.)
        $userTier = $user->currentMembership?->membershipTier;
        return $this->checkStandardRestriction($lot, $userTier);
    }

    /**
     * Determine if bidding is currently open for a specific user.
     * Accounts for global 'live' status AND active early-access windows.
     */
    public function isBiddingOpenForUser(AuctionLot $lot, ?User $user): bool
    {
        // 1. If globally live, it's open for everyone with access
        if ($lot->status === 'live') {
            return true;
        }

        // 2. If not live (e.g. upcoming), check if user is in an early access window
        if ($lot->status === 'upcoming' && $lot->early_access_enabled && $user) {
            $userTier = $user->currentMembership?->membershipTier;
            if ($userTier && $userTier->has_early_access) {
                return $lot->earlyAccessWindows()
                    ->where('membership_tier_id', $userTier->id)
                    ->where('access_at', '<=', now())
                    ->exists();
            }
        }

        // 3. Fallback for superadmin (optional, but usually helpful)
        if ($user && $user->id === 1) {
            return true;
        }

        return false;
    }

    /**
     * Resolve the access object for an auction lot.
     * Mirrors ArchiveAccessResolver::resolveProductAccess
     */
    public function resolve(AuctionLot $lot, ?User $user, ?MembershipTier $userTier = null): array
    {
        $userTier = $userTier ?? $user?->currentMembership?->membershipTier;
        
        // --- STEP 1: VISIBILITY CHECK (The Archive-style Gate) ---
        // This determines if the user can even see the lot in the list or open the detail page.
        $hasVisibility = $this->checkStandardRestriction($lot, $userTier);

        if (!$hasVisibility) {
             // User is blocked from even seeing the lot details
             if (!$user) {
                  return $this->buildLockedAccess(
                      'product_restricted',
                      ['required_tier_name' => 'Member'],
                      ['type' => 'subscribe', 'label' => 'Join Now', 'deeplink' => '/register'],
                      $userTier,
                      'lock'
                  );
             }

             $upgrade = $this->findBaseRestrictionUpgrade($lot);
             $context = [];
             if ($upgrade) {
                 if ($lot->restriction_type === 'private') {
                      $context['private_tier_name'] = $upgrade['tier']?->name ?? 'Private';
                 } else {
                      $context['required_tier_name'] = $upgrade['tier']?->name ?? 'Higher';
                 }
             } else {
                  $context['required_tier_name'] = 'Membership';
             }

             $context['body'] = app(\App\Services\Common\AccessMessagingService::class)
                 ->composeSmartAccessMessage($lot, $userTier, $upgrade['tier'] ?? null);

             return $this->buildLockedAccess(
                 'product_restricted',
                 $context,
                 [
                     'type' => 'upgrade_membership',
                     'label' => 'Upgrade',
                     'target_tier' => isset($upgrade['tier']) ? $this->formatTier($upgrade['tier']) : null,
                     'deeplink' => '/membership/tiers'
                 ],
                 $userTier,
                 'lock'
             );
        }

        // --- STEP 2: BLUR / CLEAR VIEW CHECK ---
        // User has basic visibility, now check if they see it blurred.
        // Rule: Clear view should NOT be lost just because the lot is scheduled.
        $isBiddingOpen = $this->isBiddingOpenForUser($lot, $user);
        $viewModeResponse = $this->checkBlueDisabledOrClear($lot, $userTier);

        if ($isBiddingOpen) {
             // Bidding is currently open for this user (either global live or early access)
             $viewModeResponse['message']['title'] = 'Auction Live';
             $viewModeResponse['message']['body'] = 'Bidding is now open.';
             $viewModeResponse['message']['icon'] = 'hammer'; // hammer icon for bidding open
             
             if ($lot->status === 'upcoming') {
                  $viewModeResponse['message']['title'] = 'Early Access Live';
                  $viewModeResponse['message']['body'] = 'You have early access to bid now.';
             }
        } elseif ($lot->status === 'upcoming') {
             // Bidding is NOT yet open for this user -> Calculate "Upcoming" messaging
             if ($lot->early_access_enabled) {
                  // Early access logic (Mirror Archive)
                  $activeEarlyTierNow = $lot->earlyAccessWindows()
                       ->where('access_at', '<=', now())
                       ->with('tier')
                       ->get()
                       ->sortBy(fn($w) => $w->tier->level ?? 999)
                       ->first();

                  $nextEarlyWindow = $lot->earlyAccessWindows()
                       ->where('access_at', '>', now())
                       ->with('tier')
                       ->orderBy('access_at', 'asc')
                       ->first();

                  $recommendation = null;
                  if ($nextEarlyWindow) {
                      $recommendation = ['tier' => $nextEarlyWindow->tier, 'access_at' => $nextEarlyWindow->access_at, 'is_future' => true];
                  } elseif ($activeEarlyTierNow) {
                      $recommendation = ['tier' => $activeEarlyTierNow->tier, 'access_at' => $lot->starts_at, 'is_future' => false];
                  }

                  if ($recommendation) {
                      $viewerNextWindowFuture = null;
                      if ($userTier?->has_early_access) {
                           $viewerNextWindowFuture = $lot->earlyAccessWindows()
                               ->where('membership_tier_id', $userTier->id)
                               ->where('access_at', '>', now())
                               ->orderBy('access_at', 'asc')
                               ->first();
                      }

                      $viewerUnlockAt = $viewerNextWindowFuture ? $viewerNextWindowFuture->access_at : $lot->starts_at;

                      $days = 0;
                      if ($viewerUnlockAt instanceof \Carbon\Carbon) {
                          $days = now()->gte($viewerUnlockAt)
                              ? 0
                              : now()->copy()->startOfDay()->diffInDays($viewerUnlockAt->copy()->startOfDay());
                      }

                      $context = [
                          'days_remaining' => $days,
                          'early_access_tier_name' => $recommendation['tier']->name,
                          'body' => app(\App\Services\Common\AccessMessagingService::class)
                               ->composeSmartAccessMessage($lot, $userTier, $recommendation['tier'])
                      ];

                      if (($recommendation['is_future'] ?? false) && $userTier && ($recommendation['tier']->id === $userTier->id)) {
                          $context['body'] = 'Coming soon...';
                      }

                      $actions = [];
                      $primaryTargetTier = $recommendation['tier'];
                      $hasRequiredTier = $userTier && $userTier->id === $primaryTargetTier->id;

                      if (!$hasRequiredTier) {
                          $actions[] = [
                              'type' => 'upgrade_membership',
                              'label' => $recommendation['is_future'] ? 'Get Early Access' : 'Upgrade to View',
                              'target_tier' => $this->formatTier($primaryTargetTier),
                              'deeplink' => '/membership/tiers',
                              'priority' => 'primary'
                          ];
                      }

                      // Update the message and actions but KEEP the view_mode from checkBlueDisabledOrClear
                      $earlyAccessMessage = $this->buildMessage('early_access_locked', $context);
                      $viewModeResponse['message'] = $earlyAccessMessage;
                      $viewModeResponse['actions'] = array_merge($viewModeResponse['actions'], $actions);
                      $viewModeResponse['reason'] = 'early_access_locked';
                      $viewModeResponse['timing'] = array_merge($viewModeResponse['timing'], [
                          'go_live_at' => $lot->starts_at?->toIso8601String(),
                          'next_access_at' => ($viewerUnlockAt instanceof \Carbon\Carbon) ? $viewerUnlockAt->toIso8601String() : $viewerUnlockAt
                      ]);
                  }
             } else {
                 // Fallback for upcoming with no specific early access window context
                 $goLiveText = $lot->starts_at ? "Goes live on " . $lot->starts_at->format('d M Y, h:i A') : 'Stay tuned.';
                 $viewModeResponse['message'] = ['title' => 'Coming Soon', 'body' => $goLiveText, 'icon' => 'clock'];
                 $viewModeResponse['reason'] = 'not_live_yet';
             }
        }

        return [
            'view_mode' => $viewModeResponse['view_mode'],
            'is_clear' => $viewModeResponse['view_mode'] === 'clear',
            'is_blurred' => $viewModeResponse['view_mode'] === 'blur',
            'is_blocked' => $viewModeResponse['view_mode'] === 'blocked',
            'can_bid' => $isBiddingOpen && ($viewModeResponse['view_mode'] === 'clear'),
            'is_early_access_active' => ($lot->status === 'upcoming' && $isBiddingOpen),
            'reason' => $viewModeResponse['reason'] ?? 'unknown',
            'message' => $viewModeResponse['message'] ?? null,
            'actions' => $viewModeResponse['actions'] ?? [],
            'timing' => $viewModeResponse['timing'] ?? [],
        ];
    }

    private function checkBlueDisabledOrClear(AuctionLot $lot, ?MembershipTier $userTier): array
    {
        if ($lot->blur_enabled) {
             if (!$this->hasClearViewAccess($lot, $userTier)) {
                  // Visible but Blurred
                  $upgrade = $this->findClearViewUpgrade($lot);
                  return $this->buildAccessResponse(
                      'blur',
                      'blurred',
                      ['clear_view_tier_name' => $upgrade['tier']?->name ?? 'Higher Tier'],
                      [
                        'type' => 'upgrade_membership',
                        'label' => 'Upgrade for Clear View',
                        'target_tier' => $upgrade['tier'] ? $this->formatTier($upgrade['tier']) : null,
                        'deeplink' => '/membership/tiers'
                      ],
                      $userTier,
                      'lock'
                  );
             }
         }
         
        return $this->buildOpenAccess('Access Granted', $userTier);
    }
    
    // --- Access Logic ---

    protected function checkStandardRestriction(AuctionLot $lot, ?MembershipTier $userTier): bool
    {
        if ($lot->restriction_mode === 'public') return true;
        if (!$userTier) return false;

        $type = $lot->restriction_type; 
        
        // Hierarchical
        if ($type === 'hierarchical') {
            $minTierId = $lot->restricted_min_tier_id;
            if (!$minTierId) return true; 
            
            $minTier = $lot->restrictedMinTier ?? MembershipTier::find($minTierId);
            if ($minTier) {
                return $userTier->level >= $minTier->level;
            }
            return false; // Fail safe
        }

        // Allowlist / Random
        if ($type === 'random' || $type === 'allowlist') {
             // Check Visibility Tiers Pivot
             return $lot->visibilityTiers()->where('membership_tier_id', $userTier->id)->exists();
        }

        // Private
        if ($type === 'private') {
            return $lot->restricted_private_tier_id === $userTier->id;
        }

        return false;
    }

    protected function hasClearViewAccess(AuctionLot $lot, ?MembershipTier $userTier): bool
    {
        if (!$userTier) return false;
        
        $strategy = $lot->blur_strategy ?? 'hierarchical'; // Default to hierarchical check if configured as such

        // [ADAPTATION] Support Auction Blur Strategies
        if ($strategy === 'hierarchical') {
             $minClearId = $lot->min_clear_view_tier_id;
             if (!$minClearId) return true; // No restriction?
             $minClear = $lot->minClearViewTier ?? MembershipTier::find($minClearId);
             
             return $minClear && $userTier->level >= $minClear->level;
        }
        
        if ($strategy === 'private') {
            return $lot->clear_private_tier_id === $userTier->id;
        }

        // Allowlist / Random
        return $lot->clearViewTiers()->where('membership_tier_id', $userTier->id)->exists();
    }

    protected function findBaseRestrictionUpgrade(AuctionLot $lot): ?array
    {
        if ($lot->restriction_type === 'hierarchical') {
             $minTier = $lot->restrictedMinTier ?? MembershipTier::find($lot->restricted_min_tier_id);
             if ($minTier) return ['tier' => $minTier, 'message' => null];
        }

        if ($lot->restriction_type === 'random' || $lot->restriction_type === 'allowlist') {
             // Find lowest tier in allowlist
             $tier = $lot->visibilityTiers()->orderBy('level', 'asc')->first();
             if ($tier) return ['tier' => $tier, 'message' => null];
        }

        if ($lot->restriction_type === 'private') {
              $p = $lot->restrictedPrivateTier ?? MembershipTier::find($lot->restricted_private_tier_id);
              if ($p) return ['tier' => $p, 'message' => null];
        }
        
        return null;
    }

    protected function findClearViewUpgrade(AuctionLot $lot): ?array
    {
        // [ADAPTATION] Strategy aware upgrade finder
        $strategy = $lot->blur_strategy ?? 'hierarchical';

        if ($strategy === 'hierarchical') {
            $minClear = $lot->minClearViewTier ?? MembershipTier::find($lot->min_clear_view_tier_id);
            if ($minClear) return ['tier' => $minClear, 'message' => null];
        }
        
        if ($strategy === 'private') {
             $minClear = $lot->clearPrivateTier ?? MembershipTier::find($lot->clear_private_tier_id);
             if ($minClear) return ['tier' => $minClear, 'message' => null];
        }

        // Allowlist
        $tier = $lot->clearViewTiers()->orderBy('level', 'asc')->first();
        if ($tier) {
             return [
                'tier' => $tier,
                'message' => "Upgrade to {$tier->name} to view clearly."
            ];
        }
        return null;
    }

    // --- Response Builders (Copied from ArchiveAccessResolver) ---

    // Note: buildMessage was private in Archive, but we need it here.
    // I am including it inline as per instruction to be self-contained within this service if needed,
    // or reusing the logic if I can.
    // To match Archive STRICTLY, I will copy the logic.

    private function buildMessage(string $reason, array $context = []): array
    {
        // Rule 1: Visibility Restricted
        if (in_array($reason, ['product_restricted', 'category_restricted', 'visibility_restricted', 'attachment_restricted'])) {
            if (!empty($context['private_tier_name'])) {
                return [
                    'title' => strtoupper($context['private_tier_name']),
                    'body' => 'Members Only',
                    'icon' => 'diamond'
                ];
            }

            $tierName = $context['required_tier_name'] ?? 'Membership';
            return [
                'title' => 'Restricted View',
                'body' => "{$tierName} Tier Required",
                'icon' => 'lock'
            ];
        }

        // Rule 3: Early Access
        if (in_array($reason, ['early_access_locked', 'early_access_tier_required'])) {
            $days = $context['days_remaining'] ?? 0;
            $tierName = $context['early_access_tier_name'] ?? 'Member';
            
            return [
                'title' => "Unlocks in {$days} days",
                'body' => $context['body'] ?? "Early Access: {$tierName}",
                'icon' => 'clock'
            ];
        }
        
        // Blur
        if ($reason === 'blurred') {
             $tierName = $context['clear_view_tier_name'] ?? 'Higher Tier';
             return [
                'title' => $context['title'] ?? 'Restricted View',
                'body' => $context['body'] ?? "{$tierName} Tier Required",
                'icon' => 'lock'
             ];
        }

        return [
            'title' => $context['title'] ?? 'Info',
            'body' => $context['body'] ?? 'Access Info',
            'icon' => $context['icon'] ?? 'lock'
        ];
    }

    protected function buildOpenAccess(string $title, ?MembershipTier $userTier = null, array $timing = []): array
    {
        return [
            'view_mode' => 'clear', 
            'reason' => null,
            'source' => 'lot', // ADAPTED: source=lot
            'viewer' => $this->formatViewer($userTier),
            'message' => [
                'title' => 'Open',
                'body' => $title,
                'icon' => 'info'
            ],
            'actions' => [],
            'timing' => array_merge(['go_live_at' => null, 'next_access_at' => null], $timing)
        ];
    }
    
    protected function buildAccessResponse(string $viewMode, string $reason, array $context, array $actions, ?MembershipTier $userTier, string $icon = 'lock', array $timing = []): array
    {
         $message = $this->buildMessage($reason, $context);
         
         if (isset($actions['type'])) {
             $actions = [$actions];
         }
         
         $normalizedActions = array_map(function ($action) {
             return array_merge(['type' => 'wait', 'label' => null, 'target_tier' => null, 'deeplink' => null, 'priority' => 'primary'], $action);
         }, $actions);

         return [
            'view_mode' => $viewMode,
            'reason' => $reason,
            'source' => 'lot', 
            'viewer' => $this->formatViewer($userTier),
            'message' => $message,
            'actions' => $normalizedActions,
            'timing' => array_merge(['go_live_at' => null, 'next_access_at' => null], $timing)
        ];
    }

    protected function buildLockedAccess(string $reason, array $context, array $actions, ?MembershipTier $userTier, string $icon = 'lock', array $timing = []): array
    {
        return $this->buildAccessResponse('blocked', $reason, $context, $actions, $userTier, $icon, $timing);
    }

    protected function formatViewer(?MembershipTier $tier): array
    {
        return [
            'membership_tier_id' => $tier?->id,
            'membership_tier_name' => $tier?->name,
            'membership_level' => $tier?->level
        ];
    }

    protected function formatTier($tier): array
    {
        return [
            'id' => $tier->id,
            'name' => $tier->name,
            'level' => $tier->level,
            'price' => (string) ($tier->price ?? '0.00'),
            'currency' => $tier->currency ?? 'INR'
        ];
    }
}
