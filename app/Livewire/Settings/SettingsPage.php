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
    public bool $showUpgradeModal = false;
    public bool $hasUpgradeAvailable = false;
    public array $upgradeTiers = [];
    public ?int $selectedUpgradeTierId = null;
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

        $currentSortOrder = $membership['tier']['sort_order'] ?? 0;
        $this->hasUpgradeAvailable = \App\Models\MembershipTier::where('is_active', true)
            ->where('sort_order', '>', $currentSortOrder)
            ->exists();
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

    public function openUpgradeModal()
    {
        $this->showMembershipDetailsModal = false;
        
        $user = Auth::user();
        $currentTier = $user?->currentMembership?->membershipTier;
        $currentSortOrder = $currentTier?->sort_order ?? 0;

        $tiersQuery = \App\Models\MembershipTier::where('is_active', true)
            ->where('sort_order', '>', $currentSortOrder)
            ->with(['privileges' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->with('features')
            ->orderBy('sort_order', 'asc')
            ->get();

        $this->upgradeTiers = $tiersQuery->map(function ($t) {
            $allPrivileges = $t->privileges->map(fn($p) => $p->label ?: $p->name)->all();
            return [
                'id' => $t->id,
                'name' => $t->name,
                'price' => (float)$t->price,
                'price_formatted' => $t->price > 0 ? 'INR ' . number_format($t->price) : 'Free',
                'duration_label' => 'Year',
                'short_desc' => $t->description ?: 'Premium membership benefits.',
                'perks' => array_slice($allPrivileges, 0, 3),
                'benefits_list' => array_slice($allPrivileges, 0, 4),
                'benefits_count' => count($allPrivileges)
            ];
        })->all();

        if (count($this->upgradeTiers) > 0) {
            $this->selectedUpgradeTierId = $this->upgradeTiers[0]['id'];
        } else {
            $this->selectedUpgradeTierId = null;
        }

        $this->showUpgradeModal = true;
    }

    public function closeUpgradeModal()
    {
        $this->showUpgradeModal = false;
        $this->selectedUpgradeTierId = null;
        $this->upgradeTiers = [];
        $this->resetErrorBag('selectedUpgradeTierId');
    }

    public function selectUpgradeTier($tierId)
    {
        $this->selectedUpgradeTierId = $tierId;
    }

    public function confirmUpgrade(\App\Services\Membership\ApplicationWizardService $wiz)
    {
        if (!$this->selectedUpgradeTierId) {
            $this->addError('selectedUpgradeTierId', 'Please select a membership tier to upgrade.');
            return;
        }

        $user = Auth::user();
        $currentTier = $user?->currentMembership?->membershipTier;
        $currentSortOrder = $currentTier?->sort_order ?? 0;

        $targetTier = \App\Models\MembershipTier::where('id', $this->selectedUpgradeTierId)
            ->where('is_active', true)
            ->first();

        if (!$targetTier || $targetTier->sort_order <= $currentSortOrder) {
            $this->addError('selectedUpgradeTierId', 'Invalid tier selected.');
            return;
        }

        $draft = $wiz->getOrCreateDraft();
        if ($draft instanceof \App\Models\MembershipApplication) {
            $draft->update([
                'selected_tier_id' => $targetTier->id
            ]);
        }

        return redirect()->route('membership.upgrade.payment');
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
