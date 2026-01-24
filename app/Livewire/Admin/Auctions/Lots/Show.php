<?php

namespace App\Livewire\Admin\Auctions\Lots;

use Livewire\Component;
use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionEvent;
use Livewire\Attributes\On;

class Show extends Component
{
    public $lotId;
    public $lot;
    
    // Realtime UI state
    public $lastBids = [];
    public $timelineEvents = [];
    public $bidCount = 0;
    public $hasBids = false;
    public $highestBid = null;
    public $docsCount = 0;
    
    // Modals
    public $showExtendModal = false;
    public $extendMinutes = 5;
    public $extendReason = '';
    
    public $showBidsModal = false;
    public $allBids = [];
    
    public function mount($id)
    {
        $this->lotId = $id;
        $this->loadLot(); // Initial load
    }
    
    public function loadLot()
    {
        // Full load
        $this->lot = AuctionLot::with(['images', 'visibilityTiers', 'clearViewTiers', 'earlyAccessWindows', 'earlyAccessWindows.tier'])->findOrFail($this->lotId);
        $this->refreshPanels();
    }
    
    public function refreshPanels()
    {
        // Lightweight refresh for polling
        // Ensure lot is loaded if called from poll without full hydration (rare but possible if model not bound)
        if(!$this->lot) $this->lot = AuctionLot::find($this->lotId);

        $this->bidCount = $this->lot->bids()->count();
        $this->hasBids = $this->bidCount > 0;
        $this->highestBid = $this->hasBids ? (int) $this->lot->bids()->max('amount') : null;
        
        $this->lastBids = $this->lot->bids()->with('user')->latest('placed_at')->take(10)->get();
        $this->timelineEvents = $this->lot->events()->latest()->take(30)->get();
        $this->docsCount = $this->lot->attachments()->count();
    }
    
    public function requestEdit()
    {
        $this->dispatch('auction-lot:edit', lotId: $this->lot->id);
    }
    
    // --- Bids Modal ---
    
    public function prepareAllBids()
    {
        // Load all bids (or paginate if really large, sticking to prompt's 'allBids' var for now)
        $this->allBids = $this->lot->bids()->with('user')->latest('placed_at')->limit(200)->get();
        // $this->dispatch('show-bids-modal'); // If using JS event
    }
    
    public function resetBidsModalState()
    {
        $this->reset('allBids');
    }
    
    // --- Extend Modal ---
    
    public function prepareExtend()
    {
        $this->reset('extendMinutes', 'extendReason');
    }

    public function extendAuction()
    {
        $this->validate([
            'extendMinutes' => 'required|integer|min:1',
        ]);

        $this->lot->ends_at = $this->lot->ends_at->addMinutes($this->extendMinutes);
        $this->lot->save();
        
        AuctionEvent::create([
            'auction_lot_id' => $this->lot->id,
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'event_type' => 'extended',
            'payload' => [
                'minutes' => $this->extendMinutes, 
                'reason' => $this->extendReason,
                'old_ends_at' => $this->lot->ends_at->subMinutes($this->extendMinutes)->toIso8601String()
            ]
        ]);
        
        $this->showExtendModal = false; // logic handled by bs-dismiss usually, but keeping state clean
        $this->loadLot();
        $this->dispatch('hide-extend-modal'); // If needed
    }
    
    public function confirmCancel()
    {
        // In Velzon usually we use SweetAlert.
        // Here we just trigger the action. The confirmation dialogue should happen in frontend.
        // Prompt said: "use SweetAlert/Velzon confirm pattern, then cancelAuction()"
        $this->dispatch('confirm-cancel-auction'); 
    }

    #[On('cancel-auction-confirmed')]
    public function cancelAuction()
    {
        $this->lot->update(['status' => 'cancelled']);
        AuctionEvent::create([
            'auction_lot_id' => $this->lot->id,
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'event_type' => 'cancelled'
        ]);
        $this->loadLot();
    }
    
    public function getAccessSummaryProperty()
    {
        if ($this->lot->restriction_mode == 'public') return 'Public (All Members)';
        return 'Restricted (' . $this->lot->visibilityTiers->count() . ' Tiers)';
    }

    public function render()
    {
        return view('livewire.admin.auctions.lots.show')->layout('layouts.admin');
    }
    
    #[On('auction-updated')] 
    public function handleUpdates() { $this->loadLot(); }
}
