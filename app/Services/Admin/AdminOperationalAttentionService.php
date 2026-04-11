<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\MembershipApplication;
use App\Models\Auctions\AuctionEnquiry;
use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\Shop\ShopOrder;
use App\Models\ContactEnquiry;
use App\Models\VaultRemovalRequest;
use Illuminate\Support\Collection;

class AdminOperationalAttentionService
{
    /**
     * Get a summary of all outstanding operational items for the admin.
     *
     * @param User $user
     * @return array
     */
    public function getAttentionSummary(User $user): array
    {
        $items = collect();

        // Enquiries
        if ($this->canAccess($user, 'enquiries')) {
            $this->addEnquiryItems($items);
        }

        // Orders
        if ($this->canAccess($user, 'orders')) {
            $this->addOrderItems($items);
        }

        // Requests / Approvals
        if ($this->canAccess($user, 'requests')) {
            $this->addRequestItems($items);
        }

        $totalCount = $items->sum('count');

        return [
            'total_count' => (int)$totalCount,
            'items' => $items,
            'grouped' => [
                'enquiries' => $items->where('tab', 'enquiries'),
                'orders' => $items->where('tab', 'orders'),
                'requests' => $items->where('tab', 'requests'),
            ]
        ];
    }

    /**
     * Check if user has permission to see notifications for a certain grouping.
     */
    protected function canAccess(User $user, string $module): bool
    {
        // Currently, super_admin and ecc_admin have full oversight.
        return $user->hasRole(['super_admin', 'ecc_admin']);
    }

    protected function addEnquiryItems(Collection $items): void
    {
        $archiveCount = ArchiveProductEnquiry::where('status', 'new')->count();
        if ($archiveCount > 0) {
            $items->push([
                'id' => 'archive_enquiries',
                'title' => 'Archive Enquiries',
                'description' => "{$archiveCount} new archive enquiries",
                'count' => $archiveCount,
                'route' => 'admin.archive.enquiries',
                'icon' => 'ri-archive-line',
                'color' => 'primary',
                'tab' => 'enquiries'
            ]);
        }

        $auctionCount = AuctionEnquiry::where('status', 'new')->count();
        if ($auctionCount > 0) {
            $items->push([
                'id' => 'auction_enquiries',
                'title' => 'Auction Enquiries',
                'description' => "{$auctionCount} new auction enquiries",
                'count' => $auctionCount,
                'route' => 'admin.auctions.enquiries',
                'icon' => 'ri-auction-line',
                'color' => 'success',
                'tab' => 'enquiries'
            ]);
        }

        $contactCount = ContactEnquiry::where('status', 'new')->count();
        if ($contactCount > 0) {
            $items->push([
                'id' => 'contact_enquiries',
                'title' => 'General Enquiries',
                'description' => "{$contactCount} new contact enquiries",
                'count' => $contactCount,
                'route' => 'admin.enquiries.index',
                'icon' => 'ri-chat-voice-line',
                'color' => 'info',
                'tab' => 'enquiries'
            ]);
        }
    }

    protected function addOrderItems(Collection $items): void
    {
        $shopOrderCount = ShopOrder::where('status', 'placed')->count();
        if ($shopOrderCount > 0) {
            $items->push([
                'id' => 'shop_orders',
                'title' => 'Shop Orders',
                'description' => "{$shopOrderCount} orders awaiting fulfillment",
                'count' => $shopOrderCount,
                'route' => 'admin.shop.orders',
                'icon' => 'ri-store-2-line',
                'color' => 'danger',
                'tab' => 'orders'
            ]);
        }
    }

    protected function addRequestItems(Collection $items): void
    {
        $membershipCount = MembershipApplication::whereIn('status', ['pending', 'draft', 'submitted'])->count();
        if ($membershipCount > 0) {
            $items->push([
                'id' => 'membership_applications',
                'title' => 'Membership Applications',
                'description' => "{$membershipCount} applications to review",
                'count' => $membershipCount,
                'route' => 'admin.membership.applications',
                'icon' => 'ri-vip-crown-line',
                'color' => 'warning',
                'tab' => 'requests'
            ]);
        }

        $vaultCount = VaultRemovalRequest::where('status', 'pending')->count();
        if ($vaultCount > 0) {
            $items->push([
                'id' => 'vault_removal_requests',
                'title' => 'Vault Removals',
                'description' => "{$vaultCount} removal requests pending",
                'count' => $vaultCount,
                'route' => 'admin.vault.removal-requests',
                'icon' => 'ri-safe-2-line',
                'color' => 'secondary',
                'tab' => 'requests'
            ]);
        }
    }
}
