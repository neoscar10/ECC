<!-- Edit Profile Modal -->
<div class="modal fade {{ $showEditProfileModal ? 'show d-block' : '' }}" 
     tabindex="-1"
     @if($showEditProfileModal) style="background: rgba(0,0,0,.85);" @else style="display:none;" @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ecc-settings-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ecc-settings-modal-kicker mb-2">PROFILE MANAGEMENT</div>
                    <h5 class="ecc-settings-modal-title mb-1">Edit Profile</h5>
                    <p class="ecc-settings-modal-subtitle mb-0">Update your personal details using the existing profile service flow.</p>
                </div>

                <button type="button" 
                        class="btn-close btn-close-white" 
                        wire:click="closeEditProfileModal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label ecc-settings-form-label">Display Name</label>
                        <input type="text" 
                               class="form-control ecc-settings-form-control @error('profileForm.name') is-invalid @enderror"
                               wire:model.defer="profileForm.name">
                        @error('profileForm.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label ecc-settings-form-label">Full Name</label>
                        <input type="text" 
                               class="form-control ecc-settings-form-control @error('profileForm.full_name') is-invalid @enderror"
                               wire:model.defer="profileForm.full_name">
                        @error('profileForm.full_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="col-12 col-md-6">
                        <label class="form-label ecc-settings-form-label">Phone</label>
                        <input type="text" 
                               class="form-control ecc-settings-form-control @error('profileForm.phone') is-invalid @enderror"
                               wire:model.defer="profileForm.phone">
                        @error('profileForm.phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" 
                        class="btn ecc-btn-outline-light px-4"
                        wire:click="closeEditProfileModal">
                    Cancel
                </button>

                <button type="button" 
                        class="btn ecc-btn-primary px-4"
                        wire:click="saveProfile"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveProfile">Save Changes</span>
                    <span wire:loading wire:target="saveProfile">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>
