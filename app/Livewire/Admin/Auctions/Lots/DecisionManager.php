<?php

namespace App\Livewire\Admin\Auctions\Lots;

use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use App\Services\Auctions\AuctionOutcomeService;
use App\Models\MembershipTier;
use Livewire\Component;
use Livewire\Attributes\On;

class DecisionManager extends Component
{
    public AuctionLot $lot;
    
    // UI State
    public $showDecisionModal = false;
    
    // Computed Outcome Data
    public $outcomeComparison = [];

    public function mount(AuctionLot $lot)
    {
        $this->lot = $lot;
        $this->calculateOutcome();
        
        // Auto-Open Logic (Change 1: Page Load)
        // If we represent a "Decision Required" state, auto-open.
        // Logic: Component relies on lot status being 'pending_decision' (via blade @if)
        // So simply dispatching show event on mount is sufficient.
        $this->dispatch('open-modal', modalId: 'decisionModal');
    }
    
    public function calculateOutcome()
    {
        $service = new AuctionOutcomeService();
        $this->outcomeComparison = $service->determineOutcome($this->lot);
    }
    
    public function openDecisionModal()
    {
        $this->calculateOutcome(); // Refresh to be safe
        $this->showDecisionModal = true;
        $this->dispatch('open-modal', modalId: 'decisionModal');
    }

    public function declareWinner()
    {
        // Allow if there is a winner identified (even if reserve not met)
        if (!$this->outcomeComparison['winner_user_id']) {
            $this->dispatch('operation-failed', message: 'Cannot declare winner: No bids found.');
            return;
        }

        $winnerId = $this->outcomeComparison['winner_user_id'];
        
        \DB::transaction(function () use ($winnerId) {
            $this->lot->status = 'ended';
            $this->lot->winner_user_id = $winnerId;
            $this->lot->decision_made_at = now();
            $this->lot->decision_made_by = auth()->id();
            $this->lot->save();
            
            // Event
            $event = AuctionEvent::create([
                'auction_lot_id' => $this->lot->id,
                'actor_type' => 'admin',
                'event_type' => 'admin_declared_winner',
                'payload' => [
                    'winner_user_id' => $winnerId,
                    'final_price' => $this->outcomeComparison['highest_bid_amount'],
                    'reserve' => $this->lot->min_selling_price,
                    'decided_at' => now()->toIso8601String()
                ]
            ]);
            
            event(new \App\Events\AuctionTimelineEventCreated($event));
            event(new \App\Events\AuctionStatusChanged($this->lot, 'ended'));
        });
        
        $this->dispatch('hide-decision-modal');
        $this->dispatch('operation-success', message: 'Winner declared successfully.');
        $this->redirect(request()->header('Referer'));
    }

    #[On('mark-unsold-confirmed')]
    public function markUnsold()
    {
        \DB::transaction(function () {
            $this->lot->status = 'unsold';
            $this->lot->winner_user_id = null;
            $this->lot->decision_made_at = now();
            $this->lot->decision_made_by = auth()->id();
            $this->lot->save();
            
            // Event
            $event = AuctionEvent::create([
                'auction_lot_id' => $this->lot->id,
                'actor_type' => 'admin',
                'event_type' => 'admin_marked_unsold',
                'payload' => [
                    'decided_at' => now()->toIso8601String()
                ]
            ]);
            
            event(new \App\Events\AuctionTimelineEventCreated($event));
            event(new \App\Events\AuctionStatusChanged($this->lot, 'unsold'));
        });
        
        $this->dispatch('hide-decision-modal');
        $this->dispatch('operation-success', message: 'Lot marked as Unsold.');
        $this->redirect(request()->header('Referer'));
    }

    public function reauction()
    {
         // 1. Mark current as reauctioned (or unsold + reauctioned flag logic? Prompt says status='reauctioned' preferred)
         // Wait, Prompt says "Clicking Re-auction should open the existing Add Lot modal prefilled."
         // AND "Saving creates a NEW AuctionLot instance."
         // AND "The current lot should be updated AFTER creation... set reauctioned_to_lot_id = new_lot_id"
         
         // Ah, so "Re-auction" action in this component just triggers the prefill on the OTHER component?
         // No, the prompt says "On submit in this mode (Create Mode with prefill): create new lot, then update old lot fields".
         // This means the `LotFormModal` needs to handle the updating of the OLD lot if it was created from a re-auction flow.
         
         // Wait, the `LotFormModal` logic I verified earlier just does `resetForm` and `isEditMode = false`.
         // It doesn't know it's "source" logic.
         // I need to update `LotFormModal` to track `$sourceLotId` so it can update the old lot after creating the new one?
         // OR, `DecisionManager` just triggers the modal, the user creates the lot, and... how does the system link them?
         
         // Detailed Requirement Check:
         // "On submit in this mode: create new lot... then update old lot fields per above"
         // This IMPLIES `LotFormModal` needs to know it is re-auctioning a specific lot.
         
         // I missed adding `$reauctionSourceId` to `LotFormModal`.
         // I will trigger the prefill here, and assume I need to Fix LotFormModal in the next step or right now.
         
         $this->dispatch('hide-decision-modal');
         $this->dispatch('auctionLotReauctionPrefill', sourceLotId: $this->lot->id);
    }

    public function render()
    {
        return view('livewire.admin.auctions.lots.decision-manager');
    }
}
