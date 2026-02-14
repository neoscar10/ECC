<?php

namespace App\Services\Vault;

use App\Models\MembershipTier;
use App\Models\User;
use App\Support\Archive\AccessIconNormalizer;

class VaultAccessResolver
{
    /**
     * Resolve the access object for the vault.
     */
    public function resolveVaultAccess(?User $user, ?MembershipTier $userTier): array
    {
        $hasAccess = $user && $user->has_vault_access;

        if ($hasAccess) {
            return $this->buildOpenAccess('Access Granted.', $userTier);
        }

        // Recommend Upgrade
        $upgrade = $this->findVaultUpgrade();
        
        $context = [
            'required_tier_name' => $upgrade ? $upgrade->name : 'Executive'
        ];

        return $this->buildLockedAccess(
            'vault_access_required',
            $context,
            [
                'type' => 'upgrade_membership',
                'label' => 'Upgrade for Vault Access',
                'target_tier' => $upgrade ? $this->formatTier($upgrade) : null,
                'deeplink' => '/membership/tiers',
                'priority' => 'primary'
            ],
            $userTier
        );
    }

    protected function buildOpenAccess(string $body, ?MembershipTier $userTier): array
    {
        return [
            'view_mode' => 'clear',
            'reason' => null,
            'source' => 'vault',
            'viewer' => $this->formatViewer($userTier),
            'message' => [
                'title' => 'Open',
                'body' => $body,
                'icon' => null
            ],
            'actions' => [],
            'timing' => [
                'go_live_at' => null,
                'next_access_at' => null
            ]
        ];
    }

    protected function buildLockedAccess(string $reason, array $context, array $actions, ?MembershipTier $userTier): array
    {
        $tierName = $context['required_tier_name'] ?? 'Higher';
        
        // Normalize actions list
        if (isset($actions['type'])) {
            $actions = [$actions];
        }

        $normalizedActions = array_map(function ($action) {
            return array_merge([
                'type' => 'wait',
                'label' => null,
                'target_tier' => null,
                'deeplink' => null,
                'priority' => 'primary'
            ], $action);
        }, $actions);

        return [
            'view_mode' => 'blocked',
            'reason' => $reason,
            'source' => 'vault',
            'viewer' => $this->formatViewer($userTier),
            'message' => [
                'title' => 'Restricted View',
                'body' => "{$tierName} Tier Required",
                'icon' => AccessIconNormalizer::normalize($reason, 'blocked')
            ],
            'actions' => $normalizedActions,
            'timing' => [
                'go_live_at' => null,
                'next_access_at' => null
            ]
        ];
    }

    protected function findVaultUpgrade(): ?MembershipTier
    {
        return MembershipTier::where('has_vault_access', true)
            ->orderBy('level', 'asc')
            ->first();
    }

    protected function formatViewer(?MembershipTier $tier): array
    {
        return [
            'membership_tier_id' => $tier?->id,
            'membership_tier_name' => $tier?->name,
            'membership_level' => $tier?->level
        ];
    }

    protected function formatTier(MembershipTier $tier): array
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
