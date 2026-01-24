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
    
    // Gallery State
    public $activeImageId = null;
    
    public function mount($id)
    {
        $this->lotId = $id;
        $this->loadLot(); // Initial load
    }
    
    // loadLot is defined further down
    
    public function selectImage($id)
    {
        $this->activeImageId = $id;
    }

    public function getActiveImageProperty()
    {
        if (!$this->activeImageId) return null;
        return $this->lot->images->firstWhere('id', $this->activeImageId);
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
            'extendMinutes' => 'required|integer|min:1|max:1440',
        ]);

        if (!$this->lot->ends_at) {
             $this->dispatch('show-alert', type: 'error', message: 'Cannot extend an auction without an end date.');
             return;
        }

        $minutes = (int) $this->extendMinutes;
        $this->lot->ends_at = $this->lot->ends_at->copy()->addMinutes($minutes);
        $this->lot->save();
        
        $timelineEvent = AuctionEvent::create([
            'auction_lot_id' => $this->lot->id,
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'event_type' => 'extended',
            'payload' => [
                'minutes' => $minutes, 
                'reason' => $this->extendReason,
                'old_ends_at' => $this->lot->ends_at->subMinutes($minutes)->toIso8601String()
            ]
        ]);

        event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
        event(new \App\Events\AuctionExtended($this->lot, 'manual'));
        
        $this->showExtendModal = false; 
        $this->loadLot(); // Refresh local state
        
        $this->dispatch('hide-extend-modal'); 
        $this->dispatch('close-modal', modalId: 'extendModal');
        
        // Broadcast new time to JS timer immediately
        $this->dispatch('auction-ends-updated', ends_at: $this->lot->ends_at->toIso8601String());
        
        $this->dispatch('show-alert', type: 'success', message: 'Auction time extended successfully.');
        $this->reset('extendMinutes', 'extendReason');
    }

    // --- Winner Modal ---
    
    public $showWinnerModal = false;

    public function openWinnerModal()
    {
        $this->showWinnerModal = true;
        $this->dispatch('open-modal', modalId: 'winnerModal');
    }

    public function closeWinnerModal()
    {
        $this->showWinnerModal = false;
        $this->dispatch('close-modal', modalId: 'winnerModal');
    }
    
    // --- Realtime Sync ---

    #[On('auction-countdown-ended')]
    public function onCountdownEnded()
    {
        // 1. Refresh to get latest server state (maybe a job ran)
        $this->loadLot();
        
        // 2. Logic: If strictly past ends_at AND still live, we might need to finalize
        // However, usually a Scheduler/Job handles this. 
        // We will just reflect what the DB says.
        // If DB says 'live' but time is up, we can forcingly show 'Ended' UI or trigger a check.
        
        if ($this->lot->status == 'live' && $this->lot->ends_at && now()->gt($this->lot->ends_at)) {
             // Optional: Trigger a service to close it if the scheduler hasn't run yet?
             // For now, let's assume the scheduler is frequent. 
             // Or self-correct:
             // $this->lot->status = 'ended'; $this->lot->save(); // DANGEROUS without service logic
             
             // Safer: Dispatch a toast saying "Processing results..."
        }
    }
    
    public function loadLot()
    {
        // Full load with winner
        $this->lot = AuctionLot::with([
            'images', 
            'visibilityTiers', 
            'clearViewTiers', 
            'earlyAccessWindows.tier',
            'winner.currentMembership.membershipTier', // Eager load winner tier via membership
            'order' // Eager load existing sales order
        ])->findOrFail($this->lotId);
        
        // Initialize Active Image if not set or invalid
        if (!$this->activeImageId || !$this->lot->images->contains('id', $this->activeImageId)) {
            $this->activeImageId = $this->lot->images->sortBy('sort_order')->first()?->id;
        }
        
        $this->refreshPanels();
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
        $timelineEvent = AuctionEvent::create([
            'auction_lot_id' => $this->lot->id,
            'actor_type' => 'admin',
            'actor_id' => auth()->id(),
            'event_type' => 'cancelled'
        ]);

        event(new \App\Events\AuctionTimelineEventCreated($timelineEvent));
        event(new \App\Events\AuctionStatusChanged($this->lot, 'cancelled'));
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
    #[On('auction-lot-updated')]
    public function handleUpdates() { $this->loadLot(); }

    public function getListeners()
    {
        return [
            "echo-private:auctions.lot.{$this->lotId},.bid.placed" => 'handleBidPlaced',
            "echo-private:auctions.lot.{$this->lotId},.status.changed" => 'handleLotUpdated',
            "echo-private:auctions.lot.{$this->lotId},.auction.extended" => 'handleExtended',
            "echo-private:auctions.lot.{$this->lotId},.lot.updated" => 'handleLotUpdated',
            "echo-private:auctions.lot.{$this->lotId},.timeline.created" => 'handleTimelineCreated', // Separate handler if needed, or just refreshPanels
        ];
    }

    public function handleBidPlaced($payload)
    {
        // 1. Refresh Bids & Summary
        $this->refreshPanels();

        // 2. Check for anti-sniping extension (ends_at change)
        if (isset($payload['ends_at'])) {
            $this->dispatch('auction-ends-updated', ends_at: $payload['ends_at']);
        }
    }

    public function handleExtended($payload)
    {
        $this->refreshPanels();
        // Force update countdown
        if (isset($payload['new_ends_at'])) {
            $this->dispatch('auction-ends-updated', ends_at: $payload['new_ends_at']);
        }
    }

    public function handleLotUpdated($payload)
    {
        $this->loadLot(); // Full reload for status/details changes
        if (isset($payload['ends_at'])) {
            $this->dispatch('auction-ends-updated', ends_at: $payload['ends_at']);
        }
    }
    
    public function handleTimelineCreated($payload)
    {
         $this->timelineEvents = $this->lot->events()->latest()->take(30)->get();
    }

    // --- Timeline Helpers ---
    public function formatEventTitle($event)
    {
        $map = [
            'created' => 'Auction Created',
            'bid_placed' => 'Bid Placed',
            'extended' => 'Auction Extended',
            'cancelled' => 'Auction Cancelled',
            'ended' => 'Auction Ended',
            'updated' => 'Auction Details Updated',
            'starts_at_reached' => 'Auction Started',
            'ends_at_reached' => 'Auction Ended',
        ];
        return $map[$event->event_type] ?? ucfirst(str_replace('_', ' ', $event->event_type));
    }

    public function formatActorLabel($event)
    {
        if ($event->actor_type === 'system') return 'System';
        
        // Optimistic label if we don't have relation loaded
        $label = ucfirst($event->actor_type) . ' #' . $event->actor_id;
        
        // If we really want names, we would need a polymorphic relation on AuctionEvent 
        // OR manually load users. For now, let's try to be smart with payload data if available
        if ($event->event_type === 'bid_placed' && isset($event->payload['user_id'])) {
             // We can maybe find this user in $this->lastBids or $this->allBids if loaded?
             // For performance, let's keep it simple or check if payload has name.
             // If not, 'User #ID' is acceptable for Admin view unless we eager load.
             return 'User #' . $event->actor_id;
        }
        
        return $label;
    }

    public function getTimelineEventDetails($event)
    {
        $p = $event->payload;
        if (empty($p) || !is_array($p)) return [];

        $details = [];
        $currency = $this->lot->currency;

        switch ($event->event_type) {
            case 'bid_placed':
                if(isset($p['amount'])) $details['Amount'] = $currency . ' ' . number_format($p['amount']);
                if(isset($p['is_auto']) && $p['is_auto']) $details['Type'] = 'Auto Bid';
                if(isset($p['extended']) && $p['extended']) $details['Extension'] = 'Anti-Sniping Triggered';
                break;
                
            case 'extended':
                if(isset($p['minutes'])) $details['Added'] = $p['minutes'] . ' mins';
                if(isset($p['reason'])) $details['Reason'] = $p['reason'];
                if(isset($p['old_ends_at'])) $details['Prev End'] = \Carbon\Carbon::parse($p['old_ends_at'])->format('h:i A');
                break;
                
            case 'cancelled':
                if(isset($p['reason'])) $details['Reason'] = $p['reason'];
                break;
                
            case 'updated':
                // Filter interesting keys
                foreach(['starts_at', 'ends_at', 'status', 'reserve_price'] as $key) {
                    if(isset($p[$key])) $details[ucfirst(str_replace('_',' ',$key))] = $p[$key];
                }
                break;
        }
        
        return $details;
    }

    public function getRawPayloadPretty($event)
    {
        return json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
