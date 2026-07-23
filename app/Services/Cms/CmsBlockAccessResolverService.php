<?php

namespace App\Services\Cms;

use App\Models\Cms\CmsBlock;
use App\Models\User;
use App\Models\MembershipTier;

class CmsBlockAccessResolverService
{
    /**
     * Resolve the access object for a CMS block.
     * Mirrors ArchiveAccessResolver::resolveProductAccess (minus Early Access / Go Live logic)
     */
    public function resolve(CmsBlock $block, ?User $user): array
    {
        $userTier = $user?->currentMembership?->membershipTier;
        return $this->resolveInternal($block, $userTier);
    }

    /**
     * Resolve the access object for a CMS block using a specific tier (for preview).
     */
    public function resolveForTier(CmsBlock $block, ?int $tierId): array
    {
        $tier = $tierId ? MembershipTier::find($tierId) : null;
        return $this->resolveInternal($block, $tier);
    }

    /**
     * Internal generic resolver logic.
     */
    protected function resolveInternal(CmsBlock $block, ?MembershipTier $userTier): array
    {

        // 1. Check Standard Restrictions (Hierarchy, Private, Allowlist)
        if ($block->restriction_mode === 'public') {
            // Check Blur first
            if ($block->blur_enabled) {
                 if (!$this->hasClearViewAccess($block, $userTier)) {
                      $upgrade = $this->findClearViewUpgrade($block);
                      $targetTier = data_get($upgrade, 'tier');

                      return $this->buildAccessResponse(
                          'blur',
                          'blurred',
                          ['clear_view_tier_name' => $targetTier?->name ?? 'Higher Tier'],
                          [
                            'type' => 'upgrade_membership',
                            'label' => 'Upgrade for Clear View',
                            'target_tier' => $targetTier ? $this->formatTier($targetTier) : null,
                            'deeplink' => '/membership/tiers'
                          ],
                          $userTier,
                          'lock'
                      );
                 }
            }
            return $this->buildOpenAccess('Block is public.', $userTier);
        }
        
        if (!$userTier && $block->restriction_mode !== 'public') {
             return $this->buildLockedAccess(
                 'block_restricted',
                 ['required_tier_name' => 'Member'],
                 ['type' => 'subscribe', 'label' => 'Join Now', 'deeplink' => '/gated-entry'],
                 $userTier,
                 'lock'
             );
        }

        // Check strict visibility permission
        if ($this->checkStandardRestriction($block, $userTier)) {
            // Access Granted via Visibility... check blur
             if ($block->blur_enabled) {
                  if (!$this->hasClearViewAccess($block, $userTier)) {
                       // Visible but Blurred
                       $upgrade = $this->findClearViewUpgrade($block);
                       $targetTier = data_get($upgrade, 'tier');

                       return $this->buildAccessResponse(
                           'blur',
                           'blurred',
                           ['clear_view_tier_name' => $targetTier?->name ?? 'Higher Tier'],
                           [
                             'type' => 'upgrade_membership',
                             'label' => 'Upgrade for Clear View',
                             'target_tier' => $targetTier ? $this->formatTier($targetTier) : null,
                             'deeplink' => '/membership/tiers'
                           ],
                           $userTier,
                           'lock'
                       );
                  }
             }
             
            return $this->buildOpenAccess('Access Granted.', $userTier);
        }

        // Recommend Upgrade (Totally Blocked / Hidden)
        // Note: Usually hidden by scope, but if accessed directly via ID or if logic demands "Teaser" for blocked...
        // Archive treats "Restricted" as "Hidden" in list, but "Locked" if you try to view detail? 
        // Actually Archive has scopeVisibleTo. If not visible, it returns 404.
        // BUT, if we want "Teaser" behavior for BLOCKED items, the scope would need to be permissive.
        // The Prompt says: "If existing modules currently return 'locked/teaser' cards (e.g., blur/visible-but-locked)..."
        // My CmsBlock::scopeVisibleTo ALREADY mimics ArchiveProduct::scopeVisibleTo.
        // That scope allows "Restricted" items IF the user meets criteria.
        // If they DON'T meet criteria, the scope excludes them.
        // So `resolve` will only be called for items the user CAN see.
        // Therefore, this "Blocked" block might be unreachable via list API if scope is strict.
        // However, for direct access or if we want to show "Locked" cards in a customized way, we keep this logic.
        
        $upgrade = $this->findBaseRestrictionUpgrade($block);
        $targetTier = data_get($upgrade, 'tier');
        
        $context = [];
        if ($targetTier) {
            if ($block->restriction_type === 'private') {
                 $context['private_tier_name'] = $targetTier->name;
            } else {
                 $context['required_tier_name'] = $targetTier->name;
            }
        } else {
             $context['required_tier_name'] = 'Membership';
        }

        return $this->buildLockedAccess(
            'block_restricted',
            $context,
            [
                'type' => 'upgrade_membership',
                'label' => 'Upgrade',
                'target_tier' => $targetTier ? $this->formatTier($targetTier) : null,
                'deeplink' => '/membership/tiers'
            ],
            $userTier,
            'lock'
        );
    }

    // --- Logic Mirrors ---

    protected function checkStandardRestriction(CmsBlock $block, ?MembershipTier $userTier): bool
    {
        if ($block->restriction_mode === 'public') return true;
        if (!$userTier) return false;

        $type = $block->restriction_type; 
        
        // Hierarchical
        if ($type === 'hierarchical') {
            $minTierId = data_get($block, 'restricted_min_tier_id');
            if (!$minTierId) return true; 
            
            $minTier = $block->restrictedMinTier ?? MembershipTier::find($minTierId);
            if ($minTier) {
                return (int)($userTier->level ?? 0) >= (int)($minTier->level ?? 0);
            }
            return false;
        }

        // Allowlist / Random
        if ($type === 'random' || $type === 'allowlist') {
             // Check Visibility Tiers Pivot
             return $block->visibilityTiers()->where('membership_tier_id', $userTier->id)->exists();
        }

        // Private
        if ($type === 'private') {
            return $block->restricted_private_tier_id === $userTier->id;
        }

        return false;
    }

    protected function hasClearViewAccess(CmsBlock $block, ?MembershipTier $userTier): bool
    {
        if (!$userTier) return false;
        
        // [ADAPTATION] CMS Block Schema mirrors Auction/Archive strategy? 
        // Migration has `blur_strategy`.
        $strategy = $block->blur_strategy ?? 'hierarchical';

        if ($strategy === 'hierarchical') {
             $minClearId = data_get($block, 'min_clear_view_tier_id');
             if (!$minClearId) return true;
             $minClear = $block->minClearViewTier ?? MembershipTier::find($minClearId);
             
             return $minClear && (int)($userTier->level ?? 0) >= (int)($minClear->level ?? 0);
        }
        
        // If we added Private/Allowlist strategies to migration, handle them:
        // Migration: $table->string('blur_strategy')->default('hierarchical');
        // Migration: $table->foreignId('min_clear_view_tier_id')...
        // Migration: cms_block_clear_tier pivot...
        
        if ($strategy === 'allowlist' || $strategy === 'random') { // Assuming 'random' maps to pivot like Archive
             return $block->clearViewTiers()->where('membership_tier_id', $userTier->id)->exists();
        }
        
        // Private blur strategy? (Not explicitly in migration FKs but maybe reused restricted_private?)
        // Let's stick to pivot/hierarchical as primary.
        
        return false;
    }

    protected function findBaseRestrictionUpgrade(CmsBlock $block): ?array
    {
        if ($block->restriction_type === 'hierarchical') {
             $minTier = $block->restrictedMinTier ?? MembershipTier::find($block->restricted_min_tier_id);
             if ($minTier) return ['tier' => $minTier, 'message' => null];
        }

        if ($block->restriction_type === 'random' || $block->restriction_type === 'allowlist') {
             $tier = $block->visibilityTiers()->orderBy('level', 'asc')->first();
             if ($tier) return ['tier' => $tier, 'message' => null];
        }

        if ($block->restriction_type === 'private') {
              $p = $block->restrictedPrivateTier ?? MembershipTier::find($block->restricted_private_tier_id);
              if ($p) return ['tier' => $p, 'message' => null];
        }
        
        return null;
    }

    protected function findClearViewUpgrade(CmsBlock $block): ?array
    {
        $strategy = $block->blur_strategy ?? 'hierarchical';

        if ($strategy === 'hierarchical') {
            $minClear = $block->minClearViewTier ?? MembershipTier::find($block->min_clear_view_tier_id);
            if ($minClear) return ['tier' => $minClear, 'message' => null];
        }

        if ($strategy === 'allowlist' || $strategy === 'random') {
            $tier = $block->clearViewTiers()->orderBy('level', 'asc')->first();
            if ($tier) {
                 return [
                    'tier' => $tier,
                    'message' => "Upgrade to {$tier->name} to view clearly."
                ];
            }
        }
        
        return null;
    }

    // --- Response Builders (Copied) ---

    protected function buildOpenAccess(string $title, ?MembershipTier $userTier = null): array
    {
        return [
            'view_mode' => 'clear', 
            'reason' => null,
            'source' => 'cms_block', 
            'viewer' => $this->formatViewer($userTier),
            'message' => [
                'title' => 'Open',
                'body' => $title,
                'icon' => 'info'
            ],
            'actions' => [],
        ];
    }
    
    protected function buildAccessResponse(string $viewMode, string $reason, array $context, array $actions, ?MembershipTier $userTier, string $icon = 'lock'): array
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
            'source' => 'cms_block', 
            'viewer' => $this->formatViewer($userTier),
            'message' => $message,
            'actions' => $normalizedActions,
        ];
    }

    protected function buildLockedAccess(string $reason, array $context, array $actions, ?MembershipTier $userTier, string $icon = 'lock'): array
    {
        return $this->buildAccessResponse('blocked', $reason, $context, $actions, $userTier, $icon);
    }
    
    private function buildMessage(string $reason, array $context = []): array
    {
        // Rule 1: Visibility Restricted
        if (in_array($reason, ['block_restricted'])) {
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
        
        // Blur
        if ($reason === 'blurred') {
             $tierName = $context['clear_view_tier_name'] ?? 'Higher Tier';
             return [
                'title' => 'Restricted View',
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
        if (!$tier) {
            return [
                'id' => null,
                'name' => 'Restricted',
                'level' => 0,
                'price' => '0.00',
                'currency' => 'INR'
            ];
        }

        return [
            'id' => $tier->id,
            'name' => $tier->name,
            'level' => (int)($tier->level ?? 0),
            'price' => (string) ($tier->price ?? '0.00'),
            'currency' => $tier->currency ?? 'INR'
        ];
    }

    /**
     * Safe helper to get restriction keys.
     */
    protected function getRestriction(CmsBlock $block, string $key, $default = null)
    {
        return data_get($block->restrictions_json ?? [], $key, $default);
    }

    /**
     * Safe helper to get content/payload keys.
     */
    protected function getPayload(CmsBlock $block, string $key, $default = null)
    {
        return data_get($block->payload_json ?? [], $key, $default);
    }
}
