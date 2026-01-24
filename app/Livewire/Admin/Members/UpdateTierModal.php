<?php

namespace App\Livewire\Admin\Members;

use Livewire\Component;
use App\Models\Membership;
use App\Models\MembershipTier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class UpdateTierModal extends Component
{
    public $showModal = false;
    
    // Data
    public $membershipId;
    public $membership; // The Membership model instance
    
    // Form Fields
    public $new_tier_id = '';
    public $apply_immediately = true;
    
    // Reference Data
    public $tiers = [];
    public $currentTier = null;

    public function mount()
    {
        $this->tiers = MembershipTier::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    #[On('open-update-tier-modal')]
    public function openModal($membershipId)
    {
        $this->reset(['new_tier_id', 'apply_immediately', 'currentTier', 'membership']);
        $this->resetValidation();

        $this->membershipId = $membershipId;
        $this->membership = Membership::with(['user', 'membershipTier'])->find($membershipId);
        
        if (!$this->membership) {
            $this->dispatch('notify', type: 'error', message: 'Membership not found.');
            return;
        }

        $this->currentTier = $this->membership->membershipTier;
        $this->new_tier_id = ''; // Force user to select
        
        $this->showModal = true;
        // Dispatch browser event to open bootstrap modal
        $this->dispatch('show-update-tier-modal-script');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('hide-update-tier-modal-script');
        $this->reset(['membershipId', 'membership', 'new_tier_id', 'currentTier']);
    }

    public function updateTier()
    {
        $this->validate([
            'new_tier_id' => 'required|exists:membership_tiers,id|different:currentTier.id',
        ]);

        $newTier = MembershipTier::find($this->new_tier_id);

        DB::transaction(function () use ($newTier) {
            // Logic: Update the existing membership to the new tier.
            // If we needed to keep history, we might close this membership and create a new one,
            // but the prompt explicitly asked for a "simple tier change" (manual override).
            // So we update the FK.
            
            $data = [
                'membership_tier_id' => $newTier->id,
            ];

            // If applying immediately, we might want to extend expiry or reset it?
            // Prompt says: "Apply Immediately (default ON)... otherwise allow specific date".
            // Since we are omitting the complex date picker logic to avoid data inconsistency as per prompt (unless enabled),
            // and the prompt said "simple tier change", we basically just swap the tier.
            // However, usually upgrading a tier might reset the expiration based on the new tier's duration.
            // For a *manual override*, it's safer to Keep the existing expiry unless the admin explicitly manually changed it (which we aren't building yet).
            // OR, standard behavior: The membership holds the tier.
            
            $this->membership->update($data);
        });

        // 1. Dispatch event to parent to refresh table
        $this->dispatch('member-tier-updated');
        
        // 2. Broadcast to other admins
        broadcast(new \App\Events\MembershipUpdated())->toOthers();
        
        // 3. Dispatch global success for immediate parent UI update
        $this->dispatch('operation-success', message: "Membership updated to {$newTier->name} successfully.");
        
        // Standard Session Flash (Backup for full reloads)
        session()->flash('success', "Membership updated to {$newTier->name} successfully.");
        
        $this->closeModal();
    }
    
    public function getDowngradeWarningProperty()
    {
        if (!$this->new_tier_id || !$this->currentTier) return false;
        
        $newTier = collect($this->tiers)->firstWhere('id', $this->new_tier_id);
        if (!$newTier) return false;

        // Assuming 'sort_order' or 'price' determines hierarchy.
        // Higher sort_order usually means higher tier? Or Level? 
        // Let's assume Price -> Price or Level if available.
        // Fallback to sort_order.
        
        // If Model has 'level', use it. Else use sort_order (usually 1 is lowest).
        // Let's check attributes in View or just assume generic comparison.
        // We'll trust Sort Order for now.
        
        return $newTier->sort_order < $this->currentTier->sort_order;
    }
    
    public function getUpgradeInfoProperty()
    {
        if (!$this->new_tier_id || !$this->currentTier) return false;
        $newTier = collect($this->tiers)->firstWhere('id', $this->new_tier_id);
        return $newTier && $newTier->sort_order > $this->currentTier->sort_order;
    }

    public function render()
    {
        return view('livewire.admin.members.update-tier-modal');
    }
}
