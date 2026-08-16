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
     *  - unit_price: float|null
     *  - quantity: int|null
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
            'unit_price' => $data['unit_price'] ?? ($data['price'] ?? null),
            'price' => $data['price'] ?? ($data['unit_price'] ?? null),
            'quantity' => $data['quantity'] ?? 1,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get vault summary for a user.
     */
    public function getVaultSummary(User $user): array
    {
        $items = $user->vaultItems()->locked()->get();
        $totalItems = $items->count();
        $totalValue = $items->sum(fn($item) => $item->total_value);
        
        $pendingRequestsCount = \App\Models\VaultRemovalRequest::where('user_id', $user->id)
            ->where('status', \App\Models\VaultRemovalRequest::STATUS_PENDING)
            ->count();

        return [
            'total_items_count' => $totalItems,
            'total_value' => $totalValue,
            'pending_requests_count' => $pendingRequestsCount,
            'has_access' => $this->userHasAccess($user),
        ];
    }

    /**
     * Create a removal (physical delivery) request for multiple vault items.
     */
    public function requestRemoval($items, User $user, ?string $message = null, $addressId = null, $addressData = null, ?array $quoteData = null): \App\Models\VaultRemovalRequest
    {
        // 1. Authorization
        foreach ($items as $item) {
            if ($item->user_id !== $user->id) {
                throw new \Exception("Unauthorized: You do not own vault item #{$item->id}.");
            }

            if ($item->status !== 'locked') {
                throw new \Exception("Invalid state: Vault item #{$item->id} is already removed or processed.");
            }
        }

        // 2. Prevent duplicate active requests
        $itemIds = is_array($items) ? array_map(fn($i) => $i->id, $items) : $items->pluck('id')->toArray();
        $exists = DB::table('user_vault_item_vault_removal_request')
            ->join('vault_removal_requests', 'vault_removal_requests.id', '=', 'user_vault_item_vault_removal_request.vault_removal_request_id')
            ->whereIn('user_vault_item_id', $itemIds)
            ->whereIn('status', [\App\Models\VaultRemovalRequest::STATUS_PENDING, \App\Models\VaultRemovalRequest::STATUS_APPROVED])
            ->whereNull('vault_removal_requests.deleted_at')
            ->exists();

        if ($exists) {
            throw new \Exception("A removal request for one or more of these items is already in progress.");
        }

        // 3. Address resolution
        $resolvedAddressId = null;
        $snapshot = [];

        if ($addressId) {
            $userAddress = $user->addresses()->find($addressId);
            if (!$userAddress) {
                throw new \Exception("The selected address is invalid or does not belong to you.");
            }
            $resolvedAddressId = $userAddress->id;
            $snapshot = [
                'delivery_name' => $userAddress->full_name,
                'delivery_phone' => $userAddress->phone,
                'delivery_line1' => $userAddress->line1,
                'delivery_line2' => $userAddress->line2,
                'delivery_city' => $userAddress->city,
                'delivery_state' => $userAddress->state,
                'delivery_postal_code' => $userAddress->postal_code,
                'delivery_country' => $userAddress->country,
            ];
        } elseif ($addressData && is_array($addressData)) {
            // Save new address to user's address book
            $userAddress = $user->addresses()->create([
                'label' => $addressData['label'] ?? 'Delivery Address',
                'full_name' => $addressData['full_name'],
                'phone' => $addressData['phone'],
                'line1' => $addressData['line1'],
                'line2' => $addressData['line2'] ?? null,
                'city' => $addressData['city'],
                'state' => $addressData['state'],
                'postal_code' => $addressData['postal_code'],
                'country' => $addressData['country'] ?? 'India',
                'is_default' => $addressData['is_default'] ?? false,
                'type' => 'shipping',
            ]);

            if (!empty($addressData['is_default'])) {
                $user->addresses()->where('id', '!=', $userAddress->id)->update(['is_default' => false]);
            }

            $resolvedAddressId = $userAddress->id;
            $snapshot = [
                'delivery_name' => $userAddress->full_name,
                'delivery_phone' => $userAddress->phone,
                'delivery_line1' => $userAddress->line1,
                'delivery_line2' => $userAddress->line2,
                'delivery_city' => $userAddress->city,
                'delivery_state' => $userAddress->state,
                'delivery_postal_code' => $userAddress->postal_code,
                'delivery_country' => $userAddress->country,
            ];
        } else {
             // In a perfect world we throw exception here. But to gracefully not break
             // completely legacy calls, it's optional, though the UI will enforce it.
        }

        // Quote & payment attributes
        $quoteAttributes = [];
        if ($quoteData) {
            $quoteAttributes = [
                'delivery_fee' => $quoteData['delivery_fee'] ?? null,
                'delivery_currency' => $quoteData['delivery_currency'] ?? 'INR',
                'shipping_rate_quote_id' => $quoteData['shipping_rate_quote_id'] ?? null,
                'selected_courier_company_id' => $quoteData['selected_courier_company_id'] ?? null,
                'selected_courier_name' => $quoteData['selected_courier_name'] ?? null,
                'package_weight_kg' => $quoteData['package_weight_kg'] ?? null,
                'package_length_cm' => $quoteData['package_length_cm'] ?? null,
                'package_breadth_cm' => $quoteData['package_breadth_cm'] ?? null,
                'package_height_cm' => $quoteData['package_height_cm'] ?? null,
                'payment_status' => $quoteData['payment_status'] ?? \App\Models\VaultRemovalRequest::PAYMENT_NONE,
            ];
        }

        $initialStatus = \App\Models\VaultRemovalRequest::STATUS_PENDING;
        if (isset($quoteAttributes['payment_status']) && $quoteAttributes['payment_status'] === \App\Models\VaultRemovalRequest::PAYMENT_PENDING) {
            $initialStatus = 'draft';
        }

        // 4. Create request
        $request = \App\Models\VaultRemovalRequest::create(array_merge([
            'user_id' => $user->id,
            'status' => $initialStatus,
            'message' => $message,
            'requested_at' => now(),
            'address_id' => $resolvedAddressId,
        ], $snapshot, $quoteAttributes));

        $request->vaultItems()->attach($itemIds);

        return $request;
    }

    /**
     * ADMIN: Approve removal request.
     */
    public function approveRemoval(\App\Models\VaultRemovalRequest $request, User $admin, ?string $note = null): \App\Models\VaultRemovalRequest
    {
        // Block approval if there is a delivery fee and the user hasn't paid it yet
        if ($request->delivery_fee !== null && $request->payment_status !== \App\Models\VaultRemovalRequest::PAYMENT_PAID) {
            throw new \Exception("Delivery fee must be paid before this request can be approved.");
        }

        $request->update([
            'status' => \App\Models\VaultRemovalRequest::STATUS_APPROVED,
            'admin_note' => $note,
            'reviewed_at' => now(),
            'reviewed_by_admin_id' => $admin->id,
        ]);

        return $request;
    }

    /**
     * ADMIN: Reject removal request.
     */
    public function rejectRemoval(\App\Models\VaultRemovalRequest $request, User $admin, ?string $note = null): \App\Models\VaultRemovalRequest
    {
        if ($request->payment_status === \App\Models\VaultRemovalRequest::PAYMENT_PAID) {
            $request->update([
                'status' => \App\Models\VaultRemovalRequest::STATUS_REJECTED,
                'payment_status' => \App\Models\VaultRemovalRequest::PAYMENT_REFUND_REQUIRED,
                'admin_note' => $note ? ($note . "\n[System]: Request rejected after payment. Manual refund required.") : 'Request rejected after payment. Manual refund required.',
                'reviewed_at' => now(),
                'reviewed_by_admin_id' => $admin->id,
                'rejected_after_payment_at' => now(),
                'refund_required_at' => now(),
            ]);
        } else {
            $request->update([
                'status' => \App\Models\VaultRemovalRequest::STATUS_REJECTED,
                'admin_note' => $note,
                'reviewed_at' => now(),
                'reviewed_by_admin_id' => $admin->id,
            ]);
        }

        return $request;
    }

    /**
     * ADMIN: Mark refund as handled for a rejected request.
     */
    public function markRefundHandled(\App\Models\VaultRemovalRequest $request, User $admin, string $refundReference, ?string $note = null): \App\Models\VaultRemovalRequest
    {
        $request->update([
            'payment_status' => \App\Models\VaultRemovalRequest::PAYMENT_REFUNDED,
            'refund_reference' => $refundReference,
            'refunded_at' => now(),
            'admin_note' => $note ? ($request->admin_note . "\n[Refund Handled]: " . $note) : $request->admin_note,
        ]);

        return $request;
    }

    /**
     * ADMIN: Complete removal request (actual release).
     */
    public function completeRemoval(\App\Models\VaultRemovalRequest $request, User $admin, ?string $note = null): \App\Models\VaultRemovalRequest
    {
        if ($request->delivery_fee !== null && !$request->isReadyForFulfillment()) {
            throw new \Exception("This paid delivery request must be approved by admin before completion.");
        }

        DB::transaction(function() use ($request, $admin, $note) {
            // 1. Mark vault items as removed
            foreach ($request->vaultItems as $item) {
                $this->markRemoved($item, $admin, $note ?: $request->admin_note);
            }

            // 2. Mark request as completed
            $request->update([
                'status' => \App\Models\VaultRemovalRequest::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        });

        return $request;
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

    /**
     * ADMIN: Get unattended (pending) removal request count.
     */
    public function getPendingRemovalRequestsCount(): int
    {
        return \App\Models\VaultRemovalRequest::where('status', \App\Models\VaultRemovalRequest::STATUS_PENDING)->count();
    }
}
