<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\MembershipTier;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail; // Import Mail
use App\Mail\AdminCredentialsMail; // Import Mailable
use Livewire\Attributes\Title;

#[Title('User Management')]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $membershipFilter = '';
    
    // Admin Modal properties
    public $isAdminEditMode = false;
    public $adminId;
    public $adminEmail;
    public $adminRole = 'ecc_admin';
    public $adminPassword;
    public $autoGeneratePassword = false;
    
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
        'deleteAdminConfirmed' => 'deleteAdmin', // Listener for JS
        'deleteUserConfirmed' => 'deleteUser',   // Listener for JS
    ];

    public function render()
    {
        // 1. Admin Users Query (List all admins)
        $adminUsers = User::role(['super_admin', 'ecc_admin'])->get();

        // 2. Normal Users Query (Paginated)
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

        return view('livewire.admin.users.index', [
            'adminUsers' => $adminUsers,
            'users' => $users,
            'membershipTiers' => MembershipTier::all(),
            'roles' => Role::all(),
        ])->layout('layouts.admin');
    }
    
    public function updatedMembershipFilter()
    {
        $this->resetPage();
    }
    
    // --- Admin Logic ---
    
    public function createAdmin()
    {
        // Auth Check
        if (!auth()->user()->hasRole('super_admin')) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->resetAdminFields();
        $this->isAdminEditMode = false;
        $this->dispatch('show-modal', id: 'adminModal');
    }

    public function storeAdmin()
    {
        // Auth Check
        if (!auth()->user()->hasRole('super_admin')) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }

        $this->validate([
            'adminEmail' => 'required|email|unique:users,email',
            'adminRole' => 'required|in:ecc_admin,super_admin',
            'adminPassword' => $this->autoGeneratePassword ? 'nullable' : 'required|min:8',
        ]);
        
        $password = $this->autoGeneratePassword ? Str::random(12) : $this->adminPassword;

        $admin = new User();
        $admin->email = $this->adminEmail;
        $admin->name = explode('@', $this->adminEmail)[0]; // Fallback name
        $admin->password = Hash::make($password);
        $admin->save();

        $admin->assignRole($this->adminRole);

        // Send Email
        try {
            Mail::to($admin->email)->send(new AdminCredentialsMail($admin, $password));
        } catch (\Exception $e) {
            session()->flash('warning', 'Admin created, but email could not be sent. Please copy credentials manually.');
        }

        $this->dispatch('close-modal');
        
        // Show password alert if auto-generated OR if email failed (could enhance logic, but showing for auto-gen is requirement)
        if ($this->autoGeneratePassword) {
            $this->dispatch('show-password-alert', password: $password);
        } else {
            session()->flash('success', 'Admin created successfully. Email sent.');
        }
        
        $this->resetAdminFields();
    }
    
    // editAdmin and updateAdmin removed as per requirements (No Edit)

    
    // PRE-CONFIRM: Dispatch event to show SweetAlert
    public function confirmDeleteAdmin($id)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }
        $this->dispatch('show-delete-confirmation', type: 'admin', id: $id);
    }
    
    // CONFIRMED ACTION
    public function deleteAdmin($id)
    {
        // Auth Check
        if (!auth()->user()->hasRole('super_admin')) {
             session()->flash('error', 'Unauthorized action.');
             return;
        }
        
        if ($id == auth()->id()) {
             session()->flash('error', 'You cannot delete yourself.');
             return;
        }
        
        $admin = User::findOrFail($id);
        
        if ($admin->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
             session()->flash('error', 'Cannot delete the last Super Admin.');
             return;
        }

        $admin->delete();
        session()->flash('success', 'Admin deleted successfully.');
    }

    // --- User Logic ---
    
    // PRE-CONFIRM: Dispatch event to show SweetAlert
    public function confirmDeleteUser($id)
    {
        $this->dispatch('show-delete-confirmation', type: 'user', id: $id);
    }
    
    // CONFIRMED ACTION
    public function deleteUser($id)
    {
        $this->delete($id); // Re-use old safe logic
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
    
    // editUser and updateUser removed as per requirements
    
    private function loadUser($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        
        // Load Membership Info using DB to avoid model changes
        $this->tierInfo = \Illuminate\Support\Facades\DB::table('memberships')
            ->join('membership_tiers', 'memberships.membership_tier_id', '=', 'membership_tiers.id')
            ->where('memberships.user_id', $user->id)
            ->whereIn('memberships.status', ['active', 'pending', 'expired']) 
            ->orderBy('memberships.created_at', 'desc')
            ->select('membership_tiers.name as tier_name', 'memberships.status', 'memberships.expires_at', 'memberships.started_at')
            ->first();
            
        // Load Applications
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
        $this->resetAdminFields();
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
    
    private function resetAdminFields()
    {
        $this->reset(['adminEmail', 'adminRole', 'adminPassword', 'autoGeneratePassword', 'isAdminEditMode', 'adminId']);
    }
}
