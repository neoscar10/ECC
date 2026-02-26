<div wire:ignore.self class="modal fade" id="createModeModal" tabindex="-1" aria-labelledby="createModeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="createModeModalLabel">Create Users</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-4">How would you like to create new users?</p>
                
                <div class="row g-4">
                    <!-- Single User Card -->
                    <div class="col-sm-6">
                        <div class="card border border-primary h-100 shadow-none mb-0 text-center">
                            <div class="card-body">
                                <div class="avatar-md mx-auto mb-3">
                                    <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-24">
                                        <i class="ri-user-add-line"></i>
                                    </div>
                                </div>
                                <h5 class="fs-15 mb-2">Single User</h5>
                                <p class="text-muted mb-4 fs-13">Create one user using the step-by-step wizard with full controls.</p>
                                <button type="button" class="btn btn-primary w-100 mt-auto" wire:click="selectCreateMode('single')">
                                    Continue
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bulk Upload Card -->
                    <div class="col-sm-6">
                        <div class="card border h-100 shadow-none mb-0 text-center">
                            <div class="card-body">
                                <div class="avatar-md mx-auto mb-3">
                                    <div class="avatar-title bg-light text-body rounded-circle fs-24">
                                        <i class="ri-file-upload-line"></i>
                                    </div>
                                </div>
                                <h5 class="fs-15 mb-2">Bulk Upload (CSV)</h5>
                                <p class="text-muted mb-4 fs-13">Upload a template file to create many users at once automatically.</p>
                                <button type="button" class="btn btn-dark w-100 mt-auto" wire:click="selectCreateMode('bulk')">
                                    Upload File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
