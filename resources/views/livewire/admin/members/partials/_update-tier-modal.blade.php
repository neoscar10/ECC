<!-- Modal -->
<div class="modal fade" id="updateTierModal" tabindex="-1" aria-labelledby="updateTierModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateTierModalLabel">Update Membership Tier</h5>
                <button type="button" class="btn-close" wire:click="closeUpdateTierModal" aria-label="Close"></button>
            </div>
            
            @if($membershipToUpdate)
            <form wire:submit.prevent="updateTier">
                <div class="modal-body">
                    <!-- Member Summary -->
                    <div class="card bg-light overflow-hidden mb-3 shadow-none border">
                        <div class="card-body">
                             <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0 me-3">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20 uppercase">
                                        {{ substr($membershipToUpdate->user->name ?? 'U', 0, 1) }}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14 mb-1">{{ $membershipToUpdate->user->name ?? 'Unknown Member' }}</h5>
                                    <p class="text-muted mb-0 fs-12">{{ $membershipToUpdate->user->email ?? $membershipToUpdate->user->phone ?? '' }}</p>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <span class="badge bg-secondary-subtle text-secondary fs-11">Current Tier</span>
                                    <h6 class="mb-0 mt-1 text-uppercase">{{ $membershipToUpdate->membershipTier->name ?? 'N/A' }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert Warnings -->
                    @if($this->downgradeWarning)
                        <div class="alert alert-warning alert-border-left fade show" role="alert">
                            <i class="ri-alert-line me-3 align-middle"></i> <strong>Downgrade Warning</strong> - You are moving this member to a lower tier.
                        </div>
                    @endif
                    
                    @if($this->upgradeInfo)
                        <div class="alert alert-info alert-border-left fade show" role="alert">
                            <i class="ri-information-line me-3 align-middle"></i> <strong>Upgrade</strong> - Member will receive new tier benefits immediately.
                        </div>
                    @endif

                    <!-- Form -->
                    <div class="mb-3">
                        <label for="new_tier_id" class="form-label">New Tier <span class="text-danger">*</span></label>
                        <select class="form-select @error('new_tier_id') is-invalid @enderror" id="new_tier_id" wire:model.live="new_tier_id">
                            <option value="">Select Tier...</option>
                            @foreach($tiers as $tier)
                                <option value="{{ $tier->id }}" @disabled($currentTierToCheck && $tier->id == $currentTierToCheck->id)>
                                    {{ $tier->name }} 
                                    @if(isset($tier->price_amount)) — {{ number_format($tier->price_amount) }} INR @endif
                                    @if(isset($tier->duration_days)) ({{ $tier->duration_days }} Days) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('new_tier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch form-switch-lg mb-3" dir="ltr">
                        <input type="checkbox" class="form-check-input" id="apply_immediately" wire:model="apply_immediately">
                        <label class="form-check-label" for="apply_immediately">Apply Changes Immediately</label>
                    </div>

                    @if(!$apply_immediately)
                        <p class="text-muted small">future scheduling is disabled for manual overrides.</p>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeUpdateTierModal">Cancel</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updateTier">
                        <span wire:loading.remove wire:target="updateTier">Update Tier</span>
                        <span wire:loading wire:target="updateTier"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...</span>
                    </button>
                </div>
            </form>
            @else
                <div class="modal-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        var updateTierModal = null;
        
        Livewire.on('show-update-tier-modal-script', () => {
            var el = document.getElementById('updateTierModal');
            if(el) {
                updateTierModal = new bootstrap.Modal(el);
                updateTierModal.show();
            }
        });

        Livewire.on('hide-update-tier-modal-script', () => {
             var el = document.getElementById('updateTierModal');
             if(el) {
                 var modal = bootstrap.Modal.getInstance(el);
                 if(modal) modal.hide();
             }
        });
    });
</script>
