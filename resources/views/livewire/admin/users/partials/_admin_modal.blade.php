<!-- Admin Modal -->
<div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="adminModalLabel">{{ $isAdminEditMode ? 'Edit Admin' : 'Add Admin' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="$dispatch('close-modal')"></button>
            </div>
            <form wire:submit.prevent="{{ $isAdminEditMode ? 'updateAdmin' : 'storeAdmin' }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="admin-email-field" class="form-label">Email</label>
                        <input type="email" id="admin-email-field" class="form-control" placeholder="Enter email" wire:model="adminEmail" required />
                        @error('adminEmail') <span class="text-danger error">{{ $message }}</span> @enderror
                    </div>

                    @if(!$isAdminEditMode)
                    <div class="mb-3">
                        <label for="admin-role-field" class="form-label">Role</label>
                        <select class="form-select" id="admin-role-field" wire:model="adminRole" required>
                            <option value="ecc_admin">ECC Admin</option>
                            @if(auth()->user()->hasRole('super_admin'))
                                <option value="super_admin">Super Admin</option>
                            @endif
                        </select>
                        @error('adminRole') <span class="text-danger error">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="autoGenerateSwitch" wire:model.live="autoGeneratePassword">
                        <label class="form-check-label" for="autoGenerateSwitch">Auto-generate Password</label>
                    </div>

                    @if(!$autoGeneratePassword)
                    <div class="mb-3">
                        <label class="form-label" for="password-input">Password</label>
                        <div class="position-relative auth-pass-inputgroup mb-3">
                            <input type="password" class="form-control pe-5 password-input" placeholder="Enter password" id="password-input" wire:model="adminPassword" required>
                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                        </div>
                        @error('adminPassword') <span class="text-danger error">{{ $message }}</span> @enderror
                    </div>
                    @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <div class="hstack gap-2 justify-content-end">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="$dispatch('close-modal')">Close</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="storeAdmin, updateAdmin">
                            <span wire:loading.remove wire:target="storeAdmin, updateAdmin">{{ $isAdminEditMode ? 'Update Admin' : 'Create Admin' }}</span>
                            <span wire:loading wire:target="storeAdmin, updateAdmin">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                {{ $isAdminEditMode ? 'Updating...' : 'Creating...' }}
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
