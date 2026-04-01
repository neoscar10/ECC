<?php

namespace App\Services\Club;

use App\Models\User;
use App\Services\Profile\ProfileService;
use App\Services\Archive\ArchiveConciergeService;
use App\Services\Auctions\AuctionDossierService;
use Illuminate\Support\Carbon;

class ClubPageService
{
    protected $profileService;
    protected $conciergeService;
    protected $auctionService;

    public function __construct(
        ProfileService $profileService,
        ArchiveConciergeService $conciergeService,
        AuctionDossierService $auctionService
    ) {
        $this->profileService = $profileService;
        $this->conciergeService = $conciergeService;
        $this->auctionService = $auctionService;
    }

    public function getViewModel(User $user): array
    {
        // 1. Header Data
        $profile = $this->profileService->getMembershipDetails($user);
        $tier = $profile['tier'];
        
        $header = [
            'member_name' => $user->full_name ?? $user->name,
            'tier_name' => $tier ? $tier->name : 'Guest',
            'member_id' => $user->member_id ?? '—',
            'valid_thru' => (isset($profile['expires_at']) && $profile['expires_at']) ? Carbon::parse($profile['expires_at'])->format('d M Y') : 'Lifetime',
            'avatar_url' => $user->avatar_path ? \Illuminate\Support\Facades\Storage::url($user->avatar_path) : null,
            'is_verified' => (bool)$user->is_verified,
        ];

        // 2. Privileges (from membership details)
        $privileges = collect($profile['privileges'] ?? [])->map(function($p) {
            return [
                'title' => $p->name,
                'subtitle' => $p->description, // Assuming description exists
                'icon' => $p->icon ?? 'stars',
            ];
        })->toArray();

        // 3. Concierge Ledger (Top 3 for the page)
        $conciergePaginator = $this->conciergeService->getUserRequests($user, 4);
        $concierge = collect($conciergePaginator->items())->filter()->map(function($item) {
            return [
                'title' => $item['title'],
                'meta' => $item['meta'],
                'status' => $item['status'],
                'status_label' => $item['status_label'],
                'icon' => 'assignment_turned_in', // Default icon for ledger
                'url' => $item['url'],
            ];
        })->toArray();

        // 4. Auction Dossier (Top 3 for the page)
        $dossierPaginator = $this->auctionService->getDossier($user, 3);
        $dossier = collect($dossierPaginator->items())->map(function($item) {
            return [
                'title' => $item['title'],
                'badge' => $item['labels']['top_right'],
                'badge_label' => $item['labels']['top_right'],
                'meta' => $item['labels']['line_1'],
                'thumb_url' => $item['image_url'],
                'substatus' => $item['sale']['payment_status'],
                'substatus_label' => $item['sale']['payment_status_label'],
                'url' => route('pavilion.detail', ['type' => 'auction', 'slugOrId' => $item['auction_id']]),
            ];
        })->toArray();

        // 5. Presentation Helpers
        $stats = [
            ['label' => 'Active Requests', 'value' => count($concierge)],
            ['label' => 'Auction Dossier', 'value' => count($dossier)],
            ['label' => 'Member Tier', 'value' => $tier ? $tier->name : 'N/A'],
        ];

        return [
            'header' => array_merge($header, [
                'member_since' => $user->created_at->format('M Y'),
                'is_verified' => (bool)$user->is_verified,
            ]),
            'privileges' => $privileges,
            'concierge' => $concierge,
            'auction_dossier' => $dossier,
            'tier_headline' => $tier ? strtoupper($tier->name) . ' PRIVILEGES' : 'ECC PRIVILEGES',
            'tier_quote' => ($tier->description ?? null) ?: 'Experience the pinnacle of luxury and exclusivity with Executive Cricket Club.',
            'club_stats' => $stats,
            'urls' => [
                'settings' => route('settings'),
                'back_fallback' => route('home'),
                'privileges_all' => null, // TBD route
                'contact_concierge' => '#', // TBD route or livewire action
                'new_concierge' => '#',
                'auction_history' => route('auctions.index'),
            ],
        ];
    }
}
