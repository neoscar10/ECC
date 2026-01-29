<!-- Init Modal (Create/Edit) -->
<div wire:ignore.self class="modal fade" id="initModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $isEditMode ? 'Edit Membership Tier' : 'Add Membership Tier' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="{{ $isEditMode ? 'update' : 'store' }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="E.g. Gold Tier">
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                            <div class="col-md-6">
                            <label class="form-label">Code (Unique Slug)</label>
                            <input type="text" class="form-control" wire:model="code" placeholder="E.g. gold_tier">
                            @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Price ({{ $currency }})</label>
                            <input type="number" step="0.01" class="form-control" wire:model="price">
                            @error('price') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duration</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group">
                                        <input type="number" class="form-control" wire:model="durationValue" placeholder="Val" required>
                                    </div>
                                    @error('durationValue') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-6">
                                    <select class="form-select" wire:model="durationUnit">
                                        <option value="days">Days</option>
                                        <option value="weeks">Weeks</option>
                                        <option value="months">Months</option>
                                        <option value="years">Years</option>
                                    </select>
                                    @error('durationUnit') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" wire:model="sort_order">
                            @error('sort_order') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upgrade From (Optional)</label>
                            <select class="form-select" wire:model="upgrade_from_id">
                                <option value="">None / New Base Tier</option>
                                @foreach($tiers as $tierOption)
                                    @if(!$isEditMode || $tierOption->id != $tierId)
                                        <option value="{{ $tierOption->id }}">{{ $tierOption->name }} ({{ $tierOption->code }})</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('upgrade_from_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-4">
                            <div class="form-check form-switch form-switch-lg" dir="ltr">
                                <input type="checkbox" class="form-check-input" id="isActive" wire:model="is_active">
                                <label class="form-check-label" for="isActive">Active Status</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-switch-lg" dir="ltr">
                                <input type="checkbox" class="form-check-input" id="requiresApproval" wire:model="requires_approval">
                                <label class="form-check-label" for="requiresApproval">Require Approval</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-switch-lg" dir="ltr">
                                <input type="checkbox" class="form-check-input" id="hasEarlyAccess" wire:model="has_early_access">
                                <label class="form-check-label" for="hasEarlyAccess">Early Access Eligible</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-switch form-switch-lg" dir="ltr">
                                <input type="checkbox" class="form-check-input" id="isAutoBiddingEnabled" wire:model="is_auto_bidding_enabled">
                                <label class="form-check-label" for="isAutoBiddingEnabled">Auto-Bidding Eligible</label>
                                <div class="text-muted fs-11">Allow members to configure Auto-Bid</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Enter tier description..."></textarea>
                            @error('description') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-semibold">Privileges</h6>
                            <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                <div class="row">
                                    @forelse($allPrivileges as $privilege)
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $privilege->id }}" wire:model="selectedPrivileges" id="priv_{{ $privilege->id }}">
                                                <label class="form-check-label" for="priv_{{ $privilege->id }}">
                                                    {{ $privilege->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted">No privileges defined in system.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @include('livewire.admin.membership.tiers.partials._features-ui')          

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                @role('super_admin')
                <button type="button" class="btn btn-primary" wire:click="{{ $isEditMode ? 'update' : 'store' }}">
                    {{ $isEditMode ? 'Update Tier' : 'Create Tier' }}
                </button>
                @endrole
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-5">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#405189,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                <div class="mt-4">
                    <h4 class="mb-3">Are you sure?</h4>
                    <p class="text-muted mb-4">Are you sure you want to delete this tier? This action cannot be undone.</p>
                    <div class="hstack gap-2 justify-content-center">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" wire:click="delete">Yes, Delete It</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
