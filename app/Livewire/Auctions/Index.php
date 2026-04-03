<?php

namespace App\Livewire\Auctions;

use App\Models\Auctions\AuctionLot;
use App\Services\Auctions\AuctionAccessResolverService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public $activeTab = 'live'; // 'live', 'upcoming', 'past'
    public $perPage = 20;

    // Premium Modal State
    public bool $showAccessModal = false;
    public ?array $modalData = null;

    protected $queryString = [
        'activeTab' => ['except' => 'live', 'as' => 'tab'],
    ];

    public function mount()
    {
        // Valid tabs
        if (!in_array($this->activeTab, ['live', 'upcoming', 'past'])) {
            $this->activeTab = 'live';
        }
    }

    public function setTab($tab)
    {
        if (in_array($tab, ['live', 'upcoming', 'past'])) {
            $this->activeTab = $tab;
            $this->perPage = 20; // reset pagination on tab switch
        }
    }

    public function loadMore()
    {
        $this->perPage += 20;
    }

    public function openAccessModal(int $lotId, AuctionAccessResolverService $resolver, \App\Services\Membership\MembershipTierResolver $tierResolver)
    {
        $user = Auth::user();
        $tier = $tierResolver->resolveForUser($user);
        
        $lot = AuctionLot::with(['restrictedMinTier', 'restrictedPrivateTier', 'clearViewTiers', 'visibilityTiers'])->find($lotId);
        if (!$lot) return;

        $access = $resolver->resolve($lot, $user, $tier);

        // Find the target tier from the access actions
        $targetTierId = null;
        if (!empty($access['actions'])) {
            foreach ($access['actions'] as $action) {
                if ($action['type'] === 'upgrade_membership' && !empty($action['target_tier']['id'])) {
                    $targetTierId = $action['target_tier']['id'];
                    break;
                }
            }
        }

        if (!$targetTierId) {
            return redirect('/membership/apply-intro');
        }

        $targetTierModel = $tierResolver->getTierWithDetails($targetTierId);
        
        if (!$targetTierModel) {
            return redirect('/membership/apply-intro');
        }

        $this->modalData = [
            'tier_id' => $targetTierModel->id,
            'tier_name' => $targetTierModel->name,
            'price_formatted' => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label' => 'Year',
            'icon' => \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? null, $access['view_mode'] ?? 'blocked'),
            'privileges' => $targetTierModel->privileges->toArray(),
            'features' => $targetTierModel->features->toArray(),
            'product_title' => $lot->title,
        ];

        $this->showAccessModal = true;
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->modalData = null;
    }

    public function proceedToSubscribe(\App\Services\Membership\ApplicationWizardService $wiz)
    {
        if (!Auth::check()) {
            return redirect('/membership/apply-intro');
        }

        if (!$this->modalData || empty($this->modalData['tier_id'])) {
            return redirect(route('membership.application.step1'));
        }

        $draft = $wiz->getOrCreateDraft();
        
        if ($draft instanceof \App\Models\MembershipApplication) {
            $draft->update([
                'selected_tier_id' => $this->modalData['tier_id']
            ]);
        }

        return redirect()->route('membership.upgrade.payment');
    }

    public function render(AuctionAccessResolverService $accessResolver)
    {
        $user = Auth::user();
        $userTier = $user?->currentMembership?->membershipTier;

        $query = AuctionLot::query();

        // Filter by Tab (Status)
        if ($this->activeTab === 'past') {
            $query->whereIn('status', ['closed', 'ended']);
        } elseif ($this->activeTab === 'live') {
            $query->where(function($q) use ($user) {
                $q->where('status', 'live')
                  ->orWhere(function($sq) use ($user) {
                      $sq->where('status', 'upcoming')
                         ->where('early_access_enabled', true)
                         ->whereHas('earlyAccessWindows', function($w) use ($user) {
                             $w->where('membership_tier_id', $user?->currentMembership?->membership_tier_id)
                               ->where('access_at', '<=', now());
                         });
                  });
            });
        } else {
            $query->where('status', $this->activeTab);
        }

        // Visibility Rule (Same as Archive/Auction API)
        $query->visibleTo($user, $userTier);

        $totalLots = $query->count();
        $lotsData = $query->orderBy('starts_at', 'asc')->take($this->perPage)->get(); // Order can be optimized based on 'past' vs 'live/upcoming'

        if ($this->activeTab === 'past') {
              $lotsData = $lotsData->sortByDesc('ends_at');
        }

        $formattedLots = $lotsData->map(function ($lot) use ($user, $userTier, $accessResolver) {
            $access = $accessResolver->resolve($lot, $user, $userTier);
            
            // Replicate Archive 'view_mode' logic
            $canView = ($access['view_mode'] === 'clear' || $access['view_mode'] === 'blur');
            $isBlurred = ($access['view_mode'] === 'blur');
            $isClear = $access['view_mode'] === 'clear';
            
            $icon = \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? null, $access['view_mode'] ?? 'blocked');
            
            $now = Carbon::now();
            $closesInHuman = null;
            $opensInHuman = null;

            if ($lot->ends_at && $lot->ends_at > $now) {
                $closesInHuman = $lot->ends_at->diffForHumans($now, [
                     'parts' => 2,
                     'short' => true,
                     'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE
                ]);
            }
            if ($lot->starts_at && $lot->starts_at > $now) {
                $opensInHuman = $lot->starts_at->diffForHumans($now, [
                     'parts' => 2,
                     'short' => true,
                     'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE
                ]);
            }

            // Primary Image extraction (robust)
            $imageUrl = null;
            $primaryImage = $lot->images->sortBy('sort_order')->first();
            if ($primaryImage) {
                 $imageUrl = method_exists($primaryImage, 'getUrlAttribute') 
                        ? $primaryImage->url 
                        : url(\Illuminate\Support\Facades\Storage::url($primaryImage->path));
            }

            return [
                'id' => $lot->id,
                'lot_number' => $lot->lot_no,
                'title' => $lot->title,
                'is_star_lot' => $lot->is_featured ?? false, // Assuming is_featured maps to star lot. Adjust if db column differs.
                'requires_premium_access' => $lot->blur_enabled && !($accessResolver->resolve($lot, clone $user)?->clear_view ?? true), // Simplification. Badge can also come from Access data.
                'image_url' => $imageUrl,
                'current_bid' => $isClear ? $lot->current_highest_bid : null,
                'starting_price' => $isClear ? $lot->starting_price : null,
                'closes_in_human' => $closesInHuman,
                'opens_in_human' => $opensInHuman,
                'details_url' => route('auctions.show', $lot->id),
                'bid_url' => route('auctions.show', $lot->id),
                'is_hot' => ($lot->bids()->count() > 5), // Arbitrary logic for 'hot' badge since it varies by business rule
                'can_view' => $canView,
                'is_blurred' => $isBlurred,
                'lock_type' => $icon ?? 'lock',
                'lock_title' => $access['message']['title'] ?? 'Restricted View',
                'lock_hint' => $access['message']['body'] ?? 'Membership Required',
                'is_early_access_active' => $access['is_early_access_active'] ?? false,
                'is_effectively_live' => $lot->status === 'live' || ($access['is_early_access_active'] ?? false),
            ];
        });

        return view('livewire.auctions.index', [
            'lots' => $formattedLots,
            'totalLots' => $totalLots,
            'visibleLotsCount' => $formattedLots->count(),
            'hasMoreLots' => $totalLots > $this->perPage,
        ])->layout('layouts.web-app', ['title' => 'Auctions']);
    }
}
