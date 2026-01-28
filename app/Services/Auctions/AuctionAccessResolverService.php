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
    public function resolve(AuctionLot $lot, ?User $user): array
    {
        $userTier = $user?->currentMembership?->membershipTier;
        
        // 1. Check Live Status First (Auctions: status != upcoming?)
        // Archive uses go_live_at. Auctions use starts_at and status.
        // If status is 'draft' or 'closed' and we are here, controller might have filtered it,
        // but let's handle 'upcoming' logic if we want to support Early Access.
        // For now, assuming basic "Live" check via status for simplicity, or mirroring Archive's go_live check if applicable.
        // Auctions usually just check 'status'.
        
        // [ADAPTATION] Archive checks go_live_now/at. Auctions check status.
        // If status is 'upcoming', it's like "Not Live Yet".
        $isLive = ($lot->status === 'live');
        
        if (!$isLive && $lot->status === 'upcoming') {
            // Not Live Yet -> Check Early Access (Mirror Archive)
            if ($lot->early_access_enabled) {
                if (!$user) {
                     return $this->buildLockedAccess(
                         'early_access_locked',
                         ['days_remaining' => 0, 'early_access_tier_name' => 'Member'],
                         ['type' => 'subscribe', 'label' => 'Login', 'deeplink' => '/login'],
                         $userTier,
                         'time-lock',
                         ['go_live_at' => $lot->starts_at?->toIso8601String()]
                     );
                }

                $activeWindow = null;
                if ($userTier?->has_early_access) {
                    $activeWindow = $lot->earlyAccessWindows()
                        ->where('membership_tier_id', $userTier->id)
                        ->where('access_at', '<=', now())
                        ->first();
                }

                if ($activeWindow) {
                    return $this->buildOpenAccess('Early Access Granted.', $userTier, ['go_live_at' => $lot->starts_at?->toIso8601String()]);
                }
                
                // Recommendation Logic (Mirror Archive)
                $activeEarlyTierNow = $lot->earlyAccessWindows()
                     ->where('access_at', '<=', now())
                     // ->whereHas('tier', fn($q) => $q->where('has_early_access', true)) // Assuming relations exist
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
                    // NEW: Viewer-Specific Timing
                    $viewerNextWindowFuture = null;
                    if ($userTier?->has_early_access) {
                         $viewerNextWindowFuture = $lot->earlyAccessWindows()
                             ->where('membership_tier_id', $userTier->id)
                             ->where('access_at', '>', now())
                             ->orderBy('access_at', 'asc')
                             ->first();
                    }

                    $viewerUnlockAt = $viewerNextWindowFuture ? $viewerNextWindowFuture->access_at : $lot->starts_at;

                    // NEW: Calendar Days Calculation
                    $days = 0;
                    if ($viewerUnlockAt instanceof \Carbon\Carbon) {
                        $days = now()->gte($viewerUnlockAt)
                            ? 0
                            : now()->copy()->startOfDay()->diffInDays($viewerUnlockAt->copy()->startOfDay());
                    }

                    $context = [
                        'days_remaining' => $days,
                        'early_access_tier_name' => $recommendation['tier']->name
                    ];

                    // NEW: 'Coming soon...' Override
                    if (($recommendation['is_future'] ?? false) && $userTier && ($recommendation['tier']->id === $userTier->id)) {
                        $context['body'] = 'Coming soon...';
                    }

                    // Build Actions
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

                    return $this->buildLockedAccess(
                        'early_access_locked',
                        $context,
                        $actions,
                        $userTier,
                        'time-lock',
                        [
                            'go_live_at' => $lot->starts_at?->toIso8601String(),
                            'next_access_at' => ($viewerUnlockAt instanceof \Carbon\Carbon) ? $viewerUnlockAt->toIso8601String() : $viewerUnlockAt
                        ]
                    );
                }
            }
            
            // Not Live & No Early Access or Fallback
            $goLiveText = $lot->starts_at ? "Goes live on " . $lot->starts_at->format('d M Y, h:i A') : 'Stay tuned.';
            return $this->buildLockedAccess(
                'not_live_yet',
                ['title' => 'Coming Soon', 'body' => $goLiveText],
                ['type' => 'wait', 'label' => 'Coming Soon'],
                $userTier,
                'time-lock',
                ['go_live_at' => $lot->starts_at?->toIso8601String()]
            );
        }

        // 2. Lot is Live -> Check Standard Restrictions
        if ($lot->restriction_mode === 'public') {
            return $this->checkBlueDisabledOrClear($lot, $userTier);
        }
        
        if (!$user) {
             return $this->buildLockedAccess(
                 'product_restricted',
                 ['required_tier_name' => 'Member'],
                 ['type' => 'subscribe', 'label' => 'Join Now', 'deeplink' => '/register'],
                 $userTier,
                 'lock'
             );
        }

        if ($this->checkStandardRestriction($lot, $userTier)) {
             // Access Granted via Visibility... check blur
             return $this->checkBlueDisabledOrClear($lot, $userTier);
        }

        // Recommend Upgrade (Totally Blocked)
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
