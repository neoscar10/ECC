<!-- Delete Account Modal -->
<div class="modal fade {{ $showDeleteAccountModal ? 'show d-block' : '' }}" 
     tabindex="-1"
     @if($showDeleteAccountModal) style="background: rgba(0,0,0,.85);" @else style="display:none;" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ecc-settings-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ecc-settings-modal-kicker mb-2 text-danger">DANGER ZONE</div>
                    <h5 class="ecc-settings-modal-title mb-1 text-danger">Delete Account</h5>
                    <p class="ecc-settings-modal-subtitle mb-0">Permanently delete your account and remove your access.</p>
                </div>

                <button type="button" 
                        class="btn-close" 
                        wire:click="closeDeleteAccountModal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="alert alert-danger mb-0" style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); color: var(--ecc-text-primary);">
                    <div class="d-flex align-items-center gap-2 mb-2 text-danger fw-bold">
                        <span class="material-symbols-outlined">warning</span>
                        <span>Warning: This action is irreversible</span>
                    </div>
                    <p class="small mb-0 opacity-90">
                        Are you sure you want to delete your account? Deleting your account will soft-delete your user profile, cancel your device subscriptions, and log you out.
                    </p>
                </div>
            </div>

            <div class="modal-footer border-0 pt-3 pb-4">
                <button type="button" 
                        class="btn ecc-btn-outline-light px-4 py-2"
                        wire:click="closeDeleteAccountModal">
                    Cancel
                </button>

                <button type="button" 
                        class="btn btn-danger px-4 py-2 fw-bold"
                        wire:click="deleteAccount"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deleteAccount">Yes, Delete Account</span>
                    <span wire:loading wire:target="deleteAccount">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
