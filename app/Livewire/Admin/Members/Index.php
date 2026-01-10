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

    public $search = '';
    public $tierFilter = '';
    public $statusFilter = 'active'; // Default to active members

    // Modal States
    public $selectedMembership = null;
    public $confirmingDeactivation = false;
    public $confirmingActivation = false;
    public $membershipIdToToggle = null;

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
            session()->flash('success', 'Member deactivated successfully.');
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
            session()->flash('success', 'Member activated successfully.');
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
}
