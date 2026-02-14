<?php

namespace App\Livewire\Admin\Vault;

use App\Models\User;
use App\Models\MembershipTier;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $tierFilter = '';

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTierFilter()
    {
        $this->resetPage();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $tiersWithAccess = MembershipTier::where('has_vault_access', true)->pluck('id');

        $query = User::query()
            ->whereHas('currentMembership', function ($q) use ($tiersWithAccess) {
                $q->whereIn('membership_tier_id', $tiersWithAccess);
            })
            ->with(['currentMembership.membershipTier'])
            ->withCount(['vaultItems' => function ($q) {
                $q->where('status', 'locked');
            }]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tierFilter) {
            $query->whereHas('currentMembership', function ($q) {
                $q->where('membership_tier_id', $this->tierFilter);
            });
        }

        $users = $query->paginate(10);
        $tiers = MembershipTier::where('has_vault_access', true)->get();

        return view('livewire.admin.vault.index', [
            'users' => $users,
            'tiers' => $tiers
        ]);
    }
}
