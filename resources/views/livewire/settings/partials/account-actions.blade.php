<!-- Heading -->
<section>
    <h1 class="ecc-settings-title mb-2">Account</h1>
    <p class="ecc-settings-subtitle mb-0">
        Manage your personal credentials, digital heritage files, and membership status within the Gilded Archive.
    </p>

    @if (session()->has('settings_profile_success'))
        <div class="alert ecc-settings-alert-success mt-4 mb-0">
            {{ session('settings_profile_success') }}
        </div>
    @endif

    @if (session()->has('settings_password_success'))
        <div class="alert ecc-settings-alert-success mt-4 mb-0">
            {{ session('settings_password_success') }}
        </div>
    @endif
</section>

<!-- Action Cards -->
<section>
    <div class="row g-3 g-lg-4">
        <div class="col-12 col-md-6">
            <button type="button" 
                    class="ecc-settings-action-card"
                    wire:click="openEditProfileModal">
                <div class="d-flex align-items-center gap-4">
                    <div class="ecc-settings-action-icon">
                        <span class="material-symbols-outlined">person</span>
                    </div>

                    <div class="text-start">
                        <div class="ecc-settings-action-title">Edit Profile</div>
                        <div class="ecc-settings-action-text">Update personal details</div>
                    </div>
                </div>

                <span class="material-symbols-outlined ecc-settings-action-arrow">chevron_right</span>
            </button>
        </div>

        <div class="col-12 col-md-6">
            <button type="button" 
                    class="ecc-settings-action-card"
                    wire:click="openChangePasswordModal">
                <div class="d-flex align-items-center gap-4">
                    <div class="ecc-settings-action-icon">
                        <span class="material-symbols-outlined">lock</span>
                    </div>

                    <div class="text-start">
                        <div class="ecc-settings-action-title">Change Password</div>
                        <div class="ecc-settings-action-text">Secure your digital vault</div>
                    </div>
                </div>

                <span class="material-symbols-outlined ecc-settings-action-arrow">chevron_right</span>
            </button>
        </div>

        <div class="col-12 col-md-6">
            <button type="button" 
                    class="ecc-settings-action-card"
                    wire:click="openMembershipDetailsModal">
                <div class="d-flex align-items-center gap-4">
                    <div class="ecc-settings-action-icon">
                        <span class="material-symbols-outlined">stars</span>
                    </div>

                    <div class="text-start">
                        <div class="ecc-settings-action-title">Membership Details</div>
                        <div class="ecc-settings-action-text">
                            {{ $membershipSummaryText }}
                        </div>
                    </div>
                </div>

                <span class="material-symbols-outlined ecc-settings-action-arrow">chevron_right</span>
            </button>
        </div>

        <div class="col-12 col-md-6">
            @if($hasVaultAccess)
                <a href="{{ url('/vault') }}" class="ecc-settings-action-card text-decoration-none">
            @else
                <button type="button" class="ecc-settings-action-card" @click.prevent="$dispatch('open-vault-modal')">
            @endif
                <div class="d-flex align-items-center gap-4">
                    <div class="ecc-settings-action-icon">
                        <span class="material-symbols-outlined">security</span>
                    </div>

                    <div class="text-start">
                        <div class="ecc-settings-action-title">Digital Vault</div>
                        <div class="ecc-settings-action-text">Manage your authenticated assets</div>
                    </div>
                </div>

                <span class="material-symbols-outlined ecc-settings-action-arrow">chevron_right</span>
            @if($hasVaultAccess)
                </a>
            @else
                </button>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-4">
        <button type="button" 
                class="btn ecc-btn-logout px-4 px-lg-5 py-3"
                wire:click="logout">
            LOG OUT
        </button>

        <button type="button" 
                class="btn btn-outline-danger px-4 py-3 rounded-3 fw-bold"
                style="border-color: rgba(220, 53, 69, 0.4); color: #dc3545;"
                wire:click="openDeleteAccountModal">
            <i class="mdi mdi-delete-outline me-1"></i> DELETE ACCOUNT
        </button>
    </div>
</section>
