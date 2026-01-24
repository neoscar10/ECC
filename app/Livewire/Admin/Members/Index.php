<?php

namespace App\Livewire\Admin\Members;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Membership;
use App\Models\MembershipTier;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;
    
    #[On('member-tier-updated')]
    public function refresh() { $this->render(); } // Placeholder if needed

    public $search = '';
    
    // Alerts
    public $successMessage = '';
    
    #[On('operation-success')]
    public function showSuccessAlert($message)
    {
        $this->successMessage = $message;
    }
    public $tierFilter = '';
    public $statusFilter = 'active'; // Default to active members

    // Modal States
    public $selectedMembership = null;
    public $confirmingDeactivation = false;
    public $confirmingActivation = false;
    public $membershipIdToToggle = null;
    
    // Update Tier Modal State
    public $showUpdateTierModal = false;
    public $membershipIdToUpdate = null;
    public $membershipToUpdate = null; // Stored for display
    public $new_tier_id = '';
    public $apply_immediately = true;
    public $currentTierToCheck = null;

    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTierFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function view($id)
    {
        $this->selectedMembership = Membership::with(['user', 'membershipTier', 'sourceApplication'])->findOrFail($id);
        $this->dispatch('open-view-modal');
    }

    public function confirmDeactivate($id)
    {
        $this->membershipIdToToggle = $id;
        $this->confirmingDeactivation = true;
        $this->dispatch('open-deactivate-modal');
    }

    public function deactivate()
    {
        if ($this->membershipIdToToggle) {
            $membership = Membership::findOrFail($this->membershipIdToToggle);
            
            // Guard rails
            if ($membership->user && $membership->user->hasRole('super_admin')) {
                session()->flash('error', 'Cannot deactivate a Super Admin membership.');
                $this->dispatch('close-modals');
                return;
            }
            if ($membership->user_id === Auth::id()) {
                session()->flash('error', 'You cannot deactivate your own membership.');
                $this->dispatch('close-modals');
                return;
            }

            $membership->update(['status' => 'cancelled']);
            $this->successMessage = 'Member deactivated successfully.';
            \App\Events\MembershipUpdated::dispatch();
        }
        
        $this->resetToggleState();
        $this->dispatch('close-modals');
    }

    public function confirmActivate($id)
    {
        $this->membershipIdToToggle = $id;
        $this->confirmingActivation = true;
        $this->dispatch('open-activate-modal');
    }

    public function activate()
    {
        if ($this->membershipIdToToggle) {
            $membership = Membership::findOrFail($this->membershipIdToToggle);
            $membership->update(['status' => 'active']);
            $this->successMessage = 'Member activated successfully.';
            \App\Events\MembershipUpdated::dispatch();
        }

        $this->resetToggleState();
        $this->dispatch('close-modals');
    }

    private function resetToggleState()
    {
        $this->membershipIdToToggle = null;
        $this->confirmingDeactivation = false;
        $this->confirmingActivation = false;
    }
    
    // --- Update Tier Logic (Ported from Modal) ---
    
    public function openUpdateTierModal($id)
    {
        $this->reset(['new_tier_id', 'apply_immediately', 'membershipIdToUpdate', 'membershipToUpdate', 'currentTierToCheck']);
        $this->resetValidation();

        $this->membershipIdToUpdate = $id;
        $this->membershipToUpdate = Membership::with(['user', 'membershipTier'])->find($id);
        
        if (!$this->membershipToUpdate) {
            session()->flash('error', 'Membership not found.');
            return;
        }

        $this->currentTierToCheck = $this->membershipToUpdate->membershipTier;
        $this->new_tier_id = ''; 
        $this->apply_immediately = true;
        
        $this->dispatch('show-update-tier-modal-script'); // We will update script to listen to this
    }
    
    public function closeUpdateTierModal()
    {
        $this->dispatch('hide-update-tier-modal-script');
        $this->reset(['membershipIdToUpdate', 'membershipToUpdate', 'new_tier_id', 'currentTierToCheck', 'apply_immediately']);
    }

    public function updateTier()
    {
        $this->validate([
            'new_tier_id' => 'required|exists:membership_tiers,id', // Removed 'different' rule to simplify, or keep if strict
        ]);

        if ($this->currentTierToCheck && $this->new_tier_id == $this->currentTierToCheck->id) {
             $this->addError('new_tier_id', 'Please select a different tier.');
             return;
        }

        $newTier = MembershipTier::find($this->new_tier_id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($newTier) {
            $data = ['membership_tier_id' => $newTier->id];
            // Apply Immediately logic is implied for now as we don't handle scheduling in this simplified version
            $this->membershipToUpdate->update($data);
        });

        // Success
        $message = "Membership updated to {$newTier->name} successfully.";
        
        // 1. Local Property (For immediate feedback safety)
        $this->successMessage = $message;

        // 2. Broadcast
        broadcast(new \App\Events\MembershipUpdated())->toOthers();
        
        $this->closeUpdateTierModal();
    }
    
    public function getDowngradeWarningProperty()
    {
        // We need access to all tiers to compare sort_order. 
        // Since we don't store all Tiers in public property (they are in render), we might need to query or use what we have.
        // For efficiency, we can query just the new tier or if we had them loaded. 
        // Let's query the new tier's sort order.
        if (!$this->new_tier_id || !$this->currentTierToCheck) return false;
        
        $newTier = MembershipTier::find($this->new_tier_id);
        if (!$newTier) return false;
        
        return $newTier->sort_order < $this->currentTierToCheck->sort_order;
    }
    
    public function getUpgradeInfoProperty()
    {
        if (!$this->new_tier_id || !$this->currentTierToCheck) return false;
        
        $newTier = MembershipTier::find($this->new_tier_id);
        if (!$newTier) return false;
        
        return $newTier->sort_order > $this->currentTierToCheck->sort_order;
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $query = Membership::with(['user', 'membershipTier']);

        // Search by User Name, Email or Phone
        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%');
            });
        }

        // Tier Filter
        if ($this->tierFilter) {
            $query->where('membership_tier_id', $this->tierFilter);
        }

        // Status Filter
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        } else {
             // If no filter, show active, cancelled, expired (exclude pending/rejected typically)
             // But prompt says "manages ONLY approved members".
             // So we default to 'active', but if they clear filter, maybe show all valid memberships.
             $query->whereIn('status', ['active', 'cancelled', 'expired']);
        }

        $members = $query->latest('started_at')->paginate(10);
        $tiers = MembershipTier::where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.admin.members.index', [
            'members' => $members,
            'tiers' => $tiers
        ]);
    }

    public function getListeners()
    {
        return [
             "echo-private:admin.members,.updated" => 'refresh',
        ];
    }
}
