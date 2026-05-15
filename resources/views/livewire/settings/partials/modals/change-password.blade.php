<!-- Change Password Modal -->
<div class="modal fade {{ $showChangePasswordModal ? 'show d-block' : '' }}" 
     tabindex="-1"
     @if($showChangePasswordModal) style="background: rgba(0,0,0,.85);" @else style="display:none;" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ecc-settings-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ecc-settings-modal-kicker mb-2">SECURITY</div>
                    <h5 class="ecc-settings-modal-title mb-1">Change Password</h5>
                    <p class="ecc-settings-modal-subtitle mb-0">Update your security credentials here.</p>
                </div>

                <button type="button" 
                        class="btn-close btn-close-white" 
                        wire:click="closeChangePasswordModal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label ecc-settings-form-label">Current Password</label>
                        <input type="password" 
                               class="form-control ecc-settings-form-control @error('passwordForm.current_password') is-invalid @enderror"
                               wire:model.defer="passwordForm.current_password">
                        @error('passwordForm.current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label ecc-settings-form-label">New Password</label>
                        <input type="password" 
                               class="form-control ecc-settings-form-control @error('passwordForm.password') is-invalid @enderror"
                               wire:model.defer="passwordForm.password">
                        @error('passwordForm.password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label ecc-settings-form-label">Confirm New Password</label>
                        <input type="password" 
                               class="form-control ecc-settings-form-control @error('passwordForm.password_confirmation') is-invalid @enderror"
                               wire:model.defer="passwordForm.password_confirmation">
                        @error('passwordForm.password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" 
                        class="btn ecc-btn-outline-light px-4"
                        wire:click="closeChangePasswordModal">
                    Cancel
                </button>

                <button type="button" 
                        class="btn ecc-btn-primary px-4"
                        wire:click="changePassword"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="changePassword">Update Password</span>
                    <span wire:loading wire:target="changePassword">Updating...</span>
                </button>
            </div>
        </div>
    </div>
</div>
