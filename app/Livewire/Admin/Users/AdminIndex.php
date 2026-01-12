<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminCredentialsMail;
use Livewire\Attributes\Title;

#[Title('Admin User Management')]
class AdminIndex extends Component
{
    // Admin Modal properties
    public $isAdminEditMode = false;
    public $adminId;
    public $adminEmail;
    public $adminRole = 'ecc_admin';
    public $adminPassword;
    public $autoGeneratePassword = false;

    protected $listeners = [
        'close-modal' => 'closeModal',
        'deleteAdminConfirmed' => 'deleteAdmin',
    ];

    public function render()
    {
        $adminUsers = User::role(['super_admin', 'ecc_admin'])->get();

        return view('livewire.admin.users.admin-index', [
            'adminUsers' => $adminUsers,
            'roles' => Role::all(),
        ])->layout('layouts.admin');
    }

    public function createAdmin()
    {
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
        $admin->name = explode('@', $this->adminEmail)[0]; 
        $admin->password = Hash::make($password);
        $admin->save();

        $admin->assignRole($this->adminRole);

        try {
            Mail::to($admin->email)->send(new AdminCredentialsMail($admin, $password));
        } catch (\Exception $e) {
            session()->flash('warning', 'Admin created, but email could not be sent. Please copy credentials manually.');
        }

        $this->dispatch('close-modal');
        
        if ($this->autoGeneratePassword) {
            $this->dispatch('show-password-alert', password: $password);
        } else {
            session()->flash('success', 'Admin created successfully. Email sent.');
        }
        
        $this->resetAdminFields();
    }
    
    public function confirmDeleteAdmin($id)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            session()->flash('error', 'Unauthorized action.');
            return;
        }
        $this->dispatch('show-delete-confirmation', type: 'admin', id: $id);
    }
    
    public function deleteAdmin($id)
    {
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

    public function closeModal()
    {
        $this->resetAdminFields();
    }

    private function resetAdminFields()
    {
        $this->reset(['adminEmail', 'adminRole', 'adminPassword', 'autoGeneratePassword', 'isAdminEditMode', 'adminId']);
    }
}
