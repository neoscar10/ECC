<!-- Profile Card -->
<aside class="ecc-settings-profile-card">
    <div class="text-center">
        <div class="ecc-settings-avatar-wrap mx-auto mb-3 position-relative">
            <!-- Overlay loading indicator -->
            <div wire:loading wire:target="avatarUpload" class="ecc-avatar-loading-overlay rounded-circle">
                <span class="material-symbols-outlined ecc-spin">sync</span>
            </div>

            @if($avatarUpload)
                <img src="{{ $avatarUpload->temporaryUrl() }}" 
                     alt="Preview" 
                     class="w-100 h-100 object-fit-cover rounded-circle">
            @elseif($profileAvatarUrl)
                <img src="{{ $profileAvatarUrl }}" 
                     alt="{{ $profileName }}" 
                     class="w-100 h-100 object-fit-cover rounded-circle">
            @else
                <div class="ecc-settings-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center">
                    <span class="material-symbols-outlined" style="font-size: 40px;">person</span>
                </div>
            @endif

            <label class="ecc-settings-avatar-upload-btn" for="settingsAvatarUpload" wire:loading.class="d-none" wire:target="avatarUpload">
                <span class="material-symbols-outlined" style="font-size: 18px;">photo_camera</span>
            </label>

            <input id="settingsAvatarUpload" 
                   type="file" 
                   class="d-none" 
                   wire:model.live="avatarUpload" 
                   accept="image/jpeg,image/png">

            <span class="ecc-settings-avatar-badge" wire:loading.class="d-none" wire:target="avatarUpload">
                <span class="material-symbols-outlined" style="font-size: 18px;">verified</span>
            </span>
        </div>

        @error('avatarUpload')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror

        @if (session()->has('settings_avatar_success'))
            <div class="alert ecc-settings-alert-success mt-3 mb-0 text-start small p-2">
                {{ session('settings_avatar_success') }}
            </div>
        @endif

        <h3 class="ecc-settings-profile-name mb-1">{{ $profileName }}</h3>

        @if($memberIdLabel)
            <div class="ecc-settings-profile-meta">MEMBER ID: {{ $memberIdLabel }}</div>
        @endif

        @if($membershipBadgeLabel)
            <div class="mt-3">
                <span class="ecc-membership-badge">{{ $membershipBadgeLabel }}</span>
            </div>
        @endif
    </div>
</aside>
