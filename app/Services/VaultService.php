<?php

namespace App\Services;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserVaultItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class VaultService
{
    /**
     * Check if a user has vault access based on their CURRENT membership tier.
     */
    public function userHasAccess(User $user): bool
    {
        // 1. Get current active membership
        $membership = $user->currentMembership;
        
        if (!$membership) {
            return false;
        }

        // 2. Check tier flag
        return (bool) $membership->membershipTier->has_vault_access;
    }

    /**
     * Lock an item in the user's vault.
     * 
     * @param User $user
     * @param array $data
     *  - source_type: string (archive_product|auction)
     *  - source_id: int
     *  - sale_context_type: string (optional)
     *  - sale_context_id: int (optional)
     *  - item_title: string
     *  - item_ref: string|null
     *  - item_image_url: string|null
     *  - currency: string
     *  - price: float|null
     *  - notes: string|null
     */
    public function lockItemForUser(User $user, array $data): UserVaultItem
    {
        if (!$this->userHasAccess($user)) {
            throw new \Exception("User does not have vault access.");
        }

        return UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'],
            'sale_context_type' => $data['sale_context_type'] ?? null,
            'sale_context_id' => $data['sale_context_id'] ?? null,
            'status' => 'locked',
            'locked_at' => now(),
            'item_title' => $data['item_title'],
            'item_ref' => $data['item_ref'] ?? null,
            'item_image_url' => $data['item_image_url'] ?? null,
            'currency' => $data['currency'] ?? 'INR',
            'price' => $data['price'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Mark an item as removed from the vault.
     */
    public function markRemoved(UserVaultItem $item, User $adminUser, ?string $notes = null): UserVaultItem
    {
        if ($item->status === 'removed') {
            return $item;
        }

        $item->update([
            'status' => 'removed',
            'removed_at' => now(),
            'removed_by_admin_id' => $adminUser->id,
            'notes' => $notes ? ($item->notes . "\n[Removed]: " . $notes) : $item->notes,
        ]);

        return $item;
    }
}
