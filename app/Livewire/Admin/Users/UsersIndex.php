<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\MembershipTier;
use Livewire\Attributes\Title;

#[Title('User Management')]
class UsersIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $membershipFilter = '';
    
    // User Modal properties
    public $isEditMode = false;
    public $userId;
    public $name;
    public $email;
    public $phone;
    public $role;
    
    // View Modal Enhancements
    public $tierInfo;
    public $applications;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'close-modal' => 'closeModal',
        'deleteUserConfirmed' => 'deleteUser',
    ];

    public function render()
    {
        $usersQuery = User::query()->whereDoesntHave('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'ecc_admin']);
        });

        if ($this->search) {
            $usersQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('full_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->membershipFilter) {
            $usersQuery->whereHas('currentMembership', function($q) {
                 $q->where('membership_tier_id', $this->membershipFilter);
            });
        }

        $users = $usersQuery->orderBy($this->sortField, $this->sortDirection)
                            ->paginate(10);

        return view('livewire.admin.users.users-index', [
            'users' => $users,
            'membershipTiers' => MembershipTier::all(),
        ])->layout('layouts.admin');
    }
    
    public function updatedMembershipFilter()
    {
        $this->resetPage();
    }

    public function confirmDeleteUser($id)
    {
        $this->dispatch('show-delete-confirmation', type: 'user', id: $id);
    }
    
    public function deleteUser($id)
    {
        $this->delete($id);
    }
    
    public function delete($id)
    {
        if ($id == auth()->id()) {
             session()->flash('error', 'You cannot delete yourself.');
             return;
        }
        User::find($id)->delete();
        session()->flash('success', 'User deleted successfully.');
    }
    
    public function viewUser($id)
    {
         $this->isEditMode = false;
         $this->loadUser($id);
    }
    
    private function loadUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        
        $this->tierInfo = \Illuminate\Support\Facades\DB::table('memberships')
            ->join('membership_tiers', 'memberships.membership_tier_id', '=', 'membership_tiers.id')
            ->where('memberships.user_id', $user->id)
            ->whereIn('memberships.status', ['active', 'pending', 'expired']) 
            ->orderBy('memberships.created_at', 'desc')
            ->select('membership_tiers.name as tier_name', 'memberships.status', 'memberships.expires_at', 'memberships.started_at')
            ->first();
            
        $this->applications = \Illuminate\Support\Facades\DB::table('membership_applications')
            ->leftJoin('membership_tiers', 'membership_applications.selected_tier_id', '=', 'membership_tiers.id')
            ->where('membership_applications.user_id', $user->id)
            ->orderBy('membership_applications.created_at', 'desc')
            ->limit(5)
            ->select(
                'membership_applications.id',
                'membership_applications.status', 
                'membership_applications.submitted_at', 
                'membership_applications.reviewed_at',
                'membership_tiers.name as tier_name'
            )
            ->get();
        
        $this->dispatch('show-modal', id: 'userModal');
    }

    public function closeModal()
    {
        $this->reset(['name', 'email', 'phone', 'userId', 'isEditMode', 'tierInfo', 'applications']);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }
}
