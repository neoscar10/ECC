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

    // --- Create User Wizard Properties ---
    public $createStep = 1;
    
    // Step 1: Essentials
    public $create_name;
    public $create_email;
    public $create_phone;
    public $create_tier_id;
    public $password_option = 'auto'; // auto, manual
    public $create_password;
    public $create_password_confirmation;

    // Step 2: Application Data (Optional)
    // Personal Detail
    public $app_full_name;
    public $app_dob;
    public $app_country;
    public $app_city;
    // Cricket Profile
    public $app_preferred_formats = [];
    public $app_eras = [];
    // Collector Intent
    public $app_has_acquired_before = 'no';
    public $app_focus = 'legacy';
    public $app_investment_horizon = 5;
    public $app_interests = [];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'close-modal' => 'closeModal',
        'deleteUserConfirmed' => 'deleteUser',
    ];

    #[\Livewire\Attributes\On('operation-success')]
    public function showSuccessAlert($message)
    {
        session()->flash('success', $message);
    }

    public function render()
    {
        $usersQuery = User::query()->whereDoesntHave('roles', function($q) {
            $q->whereIn('name', ['super_admin', 'ecc_admin']);
        })->with('currentMembership.membershipTier');

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

    // --- Create User Wizard Methods ---

    public function openCreateUserModal()
    {
        $this->resetWizard();
        $this->dispatch('show-modal', id: 'createUserModal');
    }

    public function nextStep()
    {
        if ($this->createStep === 1) {
            $this->validateStep1();
            $this->createStep = 2;
        }
    }

    public function prevStep()
    {
        if ($this->createStep > 1) {
            $this->createStep--;
        }
    }

    public function storeUser(\App\Services\Admin\AdminUserCreationService $service)
    {
        if ($this->createStep === 1) {
            $this->validateStep1();
        } else {
            $this->validateStep2();
        }

        try {
            $userData = [
                'name' => $this->create_name,
                'email' => $this->create_email,
                'phone' => $this->create_phone,
            ];

            $applicationData = [
                'personal' => array_filter([
                    'full_name' => $this->app_full_name,
                    'dob' => $this->app_dob,
                    'country' => $this->app_country,
                    'city' => $this->app_city,
                ]),
                'cricket' => array_filter([
                    'preferred_formats' => $this->app_preferred_formats,
                    'eras' => $this->app_eras,
                ]),
                'collector' => array_filter([
                    'has_acquired_memorabilia_before' => $this->app_has_acquired_before,
                    'focus' => $this->app_focus,
                    'investment_horizon' => $this->app_investment_horizon,
                    'interests' => $this->app_interests,
                ]),
            ];

            $manualPassword = $this->password_option === 'manual' ? $this->create_password : null;

            $service->createAdminUser($userData, $this->create_tier_id, $applicationData, $manualPassword);

            session()->flash('success', 'User created successfully and notification sent.');
            $this->dispatch('close-modal');
            $this->resetWizard();
            $this->resetPage();

        } catch (\Exception $e) {
            $this->addError('create_user_error', 'Error creating user: ' . $e->getMessage());
        }
    }

    protected function validateStep1()
    {
        $rules = [
            'create_name' => 'required|string|min:2|max:120',
            'create_email' => 'required|email|unique:users,email',
            'create_phone' => 'required|string|unique:users,phone',
            'create_tier_id' => 'required|exists:membership_tiers,id',
            'password_option' => 'required|in:auto,manual',
        ];

        if ($this->password_option === 'manual') {
            $rules['create_password'] = 'required|string|min:6|confirmed';
        }

        $this->validate($rules, [], [
            'create_name' => 'name',
            'create_email' => 'email',
            'create_phone' => 'phone',
            'create_tier_id' => 'membership tier',
            'create_password' => 'password',
        ]);
    }

    protected function validateStep2()
    {
        // Step 2 is optional, but if fields are filled, we validate them partially
        // Using existing rules as reference but making them optional
        $rules = [];
        
        if ($this->app_full_name) $rules['app_full_name'] = 'string|min:3|max:120';
        if ($this->app_dob) $rules['app_dob'] = 'date|before:today';
        if ($this->app_country) $rules['app_country'] = 'string|max:80';
        if ($this->app_city) $rules['app_city'] = 'string|max:80';
        
        if (!empty($this->app_preferred_formats)) {
            $rules['app_preferred_formats'] = 'array';
            $rules['app_preferred_formats.*'] = 'in:test,odi,t20,leagues';
        }

        if (!empty($this->app_eras)) {
            $rules['app_eras'] = 'array';
            $rules['app_eras.*'] = 'in:golden_age,post_war_50s,west_indies,odi_90s,modern,womens';
        }

        // etc.
        $this->validate($rules);
    }

    protected function resetWizard()
    {
        $this->reset([
            'createStep', 'create_name', 'create_email', 'create_phone', 'create_tier_id',
            'password_option', 'create_password', 'create_password_confirmation',
            'app_full_name', 'app_dob', 'app_country', 'app_city',
            'app_preferred_formats', 'app_eras',
            'app_has_acquired_before', 'app_focus', 'app_investment_horizon', 'app_interests'
        ]);
        $this->resetValidation();
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
