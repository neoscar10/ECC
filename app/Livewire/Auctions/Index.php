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

    public function render(AuctionAccessResolverService $accessResolver)
    {
        $user = Auth::user();
        $userTier = $user?->currentMembership?->membershipTier;

        $query = AuctionLot::query();

        // Filter by Tab (Status)
        if ($this->activeTab === 'past') {
            $query->whereIn('status', ['closed']);
            // If they have distinct 'processing' or 'ended' statuses, add them here.
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
            
            // Replicate 'view_mode' logic for details/pricing
            $isClear = $access['view_mode'] === 'clear';
            
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
            ];
        });

        return view('livewire.auctions.index', [
            'lots' => $formattedLots,
            'totalLots' => $totalLots,
            'visibleLotsCount' => $formattedLots->count(),
            'hasMoreLots' => $totalLots > $this->perPage,
        ])->layout('layouts.user.app', ['title' => 'Auctions']);
    }
}
