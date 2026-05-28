<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Services\Profile\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

#[Layout('layouts.web-app')]
class SettingsPage extends Component
{
    use WithFileUploads;

    public bool $showEditProfileModal = false;
    public bool $showChangePasswordModal = false;
    public bool $showMembershipDetailsModal = false;
    public bool $hasVaultAccess = false;

    public $avatarUpload;

    public array $profileForm = [];
    public array $passwordForm = [];

    public string $profileName = '';
    public ?string $profileAvatarUrl = null;
    public ?string $memberIdLabel = null;
    public ?string $membershipBadgeLabel = null;
    public ?string $membershipSummaryText = null;
    
    public ?string $membershipTierLabel = null;
    public ?string $membershipStatusLabel = null;
    public ?string $membershipRenewalLabel = null;
    public ?string $membershipDetailsText = null;

    public ?string $aboutUsUrl = null;
    public ?string $termsUrl = null;
    public ?string $membershipDetailsUrl = null;
    public ?string $supportUrl = null;
    public ?string $appVersionLabel = 'v1.2.4-STITCH';

    public function mount(ProfileService $profileService)
    {
        $this->hasVaultAccess = Auth::user()->has_vault_access ?? false;
        $this->hydratePageData($profileService);
        
        // Mocking some URLs as requested "wire only to the closest valid existing destination already in the project"
        // Since no specific routes for About/Terms were found, I'll use placeholders or existing pavilion routes if applicable
        $this->aboutUsUrl = url('/club'); // Best fit for "About Us"
        $this->termsUrl = '#'; // Placeholder if none exists
        $this->supportUrl = '#'; // Placeholder
    }

    public function hydratePageData(ProfileService $profileService)
    {
        $user = Auth::user();
        $profile = $profileService->getProfile($user);

        $this->profileName = $user->full_name ?? $user->name;
        $this->profileAvatarUrl = $profile['avatar_url'];
        $this->memberIdLabel = $user->member_code; // Accessing the accessor

        $membership = $profile['membership'];
        if ($membership) {
            $this->membershipBadgeLabel = $membership['tier']['name'] ?? 'MEMBER';
            $this->membershipTierLabel = $membership['tier']['name'] ?? 'Standard';
            $this->membershipStatusLabel = strtoupper($membership['status'] ?? 'ACTIVE');
            $this->membershipRenewalLabel = $membership['expires_at'] ? \Carbon\Carbon::parse($membership['expires_at'])->format('M d, Y') : 'Life-time';
            $this->membershipSummaryText = "{$this->membershipTierLabel} • {$this->membershipStatusLabel}";
        } else {
            $this->membershipBadgeLabel = 'GUEST';
            $this->membershipSummaryText = 'No active membership';
        }

        $this->profileForm = [
            'name' => $user->name,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
        ];
    }

    public function openEditProfileModal()
    {
        $this->showEditProfileModal = true;
    }

    public function closeEditProfileModal()
    {
        $this->showEditProfileModal = false;
        $this->resetErrorBag('profileForm');
    }

    public function saveProfile(ProfileService $profileService)
    {
        if (!empty($this->profileForm['phone'])) {
            try {
                $this->profileForm['phone'] = app(\App\Services\Otp\PhoneNormalizer::class)->normalize($this->profileForm['phone']);
            } catch (\Exception $e) {
                $this->addError('profileForm.phone', $e->getMessage() ?: 'The phone number format is invalid.');
                return;
            }
        }

        $this->validate([
            'profileForm.name' => 'required|string|max:255',
            'profileForm.full_name' => 'nullable|string|max:100',
            'profileForm.phone' => 'nullable|string|max:20|unique:users,phone,' . Auth::id(),
        ]);

        $profileService->updateProfile(Auth::user(), $this->profileForm);

        $this->hydratePageData($profileService);
        session()->flash('settings_profile_success', 'Profile updated successfully.');
        $this->dispatch('profile-updated');
        $this->closeEditProfileModal();
    }

    public function updatedAvatarUpload()
    {
        $this->validate([
            'avatarUpload' => 'required|image|mimes:jpeg,png|max:20480',
        ]);

        $profileService = app(\App\Services\Profile\ProfileService::class);
        $profileService->updateAvatar(Auth::user(), $this->avatarUpload);

        $this->hydratePageData($profileService);
        $this->reset('avatarUpload');
        
        session()->flash('settings_avatar_success', 'Avatar updated successfully.');
        $this->dispatch('profile-updated');
    }

    public function openChangePasswordModal()
    {
        $this->showChangePasswordModal = true;
        $this->passwordForm = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function closeChangePasswordModal()
    {
        $this->showChangePasswordModal = false;
        $this->resetErrorBag('passwordForm');
    }

    public function changePassword()
    {
        $this->validate([
            'passwordForm.current_password' => 'required',
            'passwordForm.password' => 'required|string|min:8|confirmed',
            'passwordForm.password_confirmation' => 'required',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->passwordForm['current_password'], $user->password)) {
            $this->addError('passwordForm.current_password', 'The provided current password does not match your actual password.');
            return;
        }

        $user->forceFill([
            'password' => Hash::make($this->passwordForm['password'])
        ])->setRememberToken(\Illuminate\Support\Str::random(60));

        $user->save();

        session()->flash('settings_password_success', 'Password changed successfully.');
        $this->passwordForm = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->closeChangePasswordModal();
    }

    public function openMembershipDetailsModal()
    {
        $this->showMembershipDetailsModal = true;
    }

    public function closeMembershipDetailsModal()
    {
        $this->showMembershipDetailsModal = false;
    }

    public function logout(Request $request)
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.settings.settings-page')->layout('layouts.web-app', [
            'title' => 'Settings',
            'activeNav' => 'settings',
        ]);
    }
}
