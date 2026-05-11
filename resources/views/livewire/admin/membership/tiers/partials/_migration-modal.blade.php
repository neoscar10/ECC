<!-- Migration Wizard Modal -->
<div wire:ignore.self class="modal fade" id="migrationModal" tabindex="-1" aria-labelledby="migrationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="migrationModalLabel">Migration Wizard</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <!-- Wizard Navigation -->
                <div class="archive-product-stepper mb-5">
                    <div class="ap-stepper d-flex justify-content-between position-relative">
                        <!-- Step 1 -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $migrationStep === 1 ? 'active' : '' }} {{ $migrationStep > 1 ? 'done' : '' }}" type="button">
                                <span class="step-icon">
                                    @if($migrationStep > 1) <i class="ri-check-line"></i> @else 1 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Members</span>
                            </button>
                        </div>
                        <!-- Step 2 -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $migrationStep === 2 ? 'active' : '' }} {{ $migrationStep > 2 ? 'done' : '' }}" type="button">
                                <span class="step-icon">
                                    @if($migrationStep > 2) <i class="ri-check-line"></i> @else 2 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Restrictions</span>
                            </button>
                        </div>
                        <!-- Step 3 -->
                        <div class="ap-step">
                            <button class="ap-pill btn {{ $migrationStep === 3 ? 'active' : '' }} {{ $migrationStep > 3 ? 'done' : '' }}" type="button">
                                <span class="step-icon">
                                    @if($migrationStep > 3) <i class="ri-check-line"></i> @else 3 @endif
                                </span>
                                <span class="step-label d-none d-sm-block">Finalize</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-0">
                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="ri-error-warning-line me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Wizard Content -->
                    <div class="wizard-content min-vh-25">
                        @if($migrationStep == 1)
                            <!-- STEP 1: MEMBER SELECTION -->
                            <div class="mb-4">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-7">
                                        <h6 class="fw-bold text-dark mb-1">Who should be moved?</h6>
                                        <p class="text-muted small mb-0">Select the members currently on this tier that you wish to migrate.</p>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-muted fs-11 fw-bold">MOVE TO:</span>
                                            <select class="form-select border-start-0 fw-medium @error('migrationTargetTierId') is-invalid @enderror" wire:model="migrationTargetTierId">
                                                <option value="">Select Destination...</option>
                                                @foreach($tiers as $tierOption)
                                                    @if($tierOption->id != $tierToDeleteId)
                                                        <option value="{{ $tierOption->id }}">{{ $tierOption->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('migrationTargetTierId') <div class="text-danger fs-11 mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded-4 overflow-hidden" style="max-height: 280px;">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top" style="z-index: 5;">
                                        <tr>
                                            <th style="width: 50px;" class="ps-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" wire:model.live="selectAll">
                                                </div>
                                            </th>
                                            <th class="fs-12 text-muted text-uppercase fw-bold">Member Profile</th>
                                            <th class="fs-12 text-muted text-uppercase fw-bold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($migrationMembers as $member)
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="{{ $member['id'] }}" wire:model.live="selectedMembershipIds">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-3">
                                                            <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-11 fw-bold">
                                                                {{ strtoupper(substr($member['name'], 0, 1)) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold text-dark">{{ $member['name'] }}</div>
                                                            <div class="text-muted fs-12">{{ $member['email'] }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-soft-warning text-warning text-uppercase" style="font-size: 10px;">Awaiting Move</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        @elseif($migrationStep == 2)
                            <!-- STEP 2: ITEM RESTRICTIONS -->
                            <div class="mb-4">
                                <div class="card border-0 bg-soft-info rounded-4">
                                    <div class="card-body p-4 d-flex align-items-center">
                                        <div class="avatar-sm bg-info text-white rounded-3 d-flex align-items-center justify-content-center me-4 flex-shrink-0">
                                            <i class="ri-shield-keyhole-line fs-20"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold text-info-emphasis mb-1">Restriction Migration</h6>
                                            <p class="text-info text-opacity-75 small mb-0">Re-assign items (Auctions, Archive, CMS) that are currently restricted to this tier.</p>
                                        </div>
                                        <div style="min-width: 250px;">
                                            <label class="fs-11 fw-bold text-uppercase text-info mb-1">New Access Tier</label>
                                            <select class="form-select border-0 shadow-sm" wire:model="restrictionTargetTierId">
                                                <option value="">Do Not Re-assign (Leave Broken)</option>
                                                @foreach($tiers as $tierOption)
                                                    @if($tierOption->id != $tierToDeleteId)
                                                        <option value="{{ $tierOption->id }}">{{ $tierOption->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-1 border-light bg-white rounded-4 overflow-hidden shadow-sm">
                                <div class="card-header bg-light bg-opacity-50 border-bottom-0 pt-3 pb-3 px-4 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                                        <i class="ri-list-check-2 text-muted me-2"></i> Affected Items <span class="badge bg-secondary ms-2">{{ $brokenRestrictions['total'] }}</span>
                                    </h6>
                                    <div>
                                        <select class="form-select form-select-sm border-0 shadow-sm bg-white" wire:model.live="affectedItemsFilter" style="width: 160px;">
                                            <option value="all">All Sources</option>
                                            <option value="auctions">Auctions</option>
                                            <option value="archive_products">Archive Products</option>
                                            <option value="archive_categories">Archive Categories</option>
                                            <option value="cms_blocks">CMS Blocks</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 250px;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light sticky-top" style="z-index: 5;">
                                                <tr>
                                                    <th class="ps-4 fs-12 text-muted text-uppercase fw-bold border-bottom-0 py-2">Item Name</th>
                                                    <th class="fs-12 text-muted text-uppercase fw-bold border-bottom-0 py-2">Source</th>
                                                    <th class="fs-12 text-muted text-uppercase fw-bold border-bottom-0 py-2">Restriction Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($this->getAffectedItemsList() as $item)
                                                    <tr>
                                                        <td class="ps-4 py-2 fw-medium text-dark">
                                                            <div class="d-flex align-items-center">
                                                                <i class="ri-file-list-3-line text-muted me-2 fs-16"></i>
                                                                {{ $item['title'] }}
                                                            </div>
                                                        </td>
                                                        <td class="py-2">
                                                            <span class="badge bg-soft-secondary text-secondary">{{ $item['source_label'] }}</span>
                                                        </td>
                                                        <td class="py-2">
                                                            <span class="badge bg-soft-info text-info">{{ $item['type'] }}</span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center py-5 text-muted small">
                                                            <i class="ri-checkbox-circle-line fs-32 mb-2 d-block opacity-25"></i>
                                                            No item dependencies found for this filter.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        @elseif($migrationStep == 3)
                            <!-- STEP 3: FINAL SUMMARY -->
                            <div class="text-center py-3">
                                <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#0ab39c,secondary:#405189" style="width:100px;height:100px"></lord-icon>
                                <h4 class="fw-bold mt-3">Final Confirmation</h4>
                                <p class="text-muted mx-auto" style="max-width: 500px;">Review the migration summary below. Clicking confirm will permanently re-assign all selected members and items.</p>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="p-4 border border-dashed rounded-4 bg-soft-primary text-center">
                                        <div class="fs-12 text-uppercase fw-bold text-primary mb-1">Members to Move</div>
                                        <div class="fs-28 fw-bold text-dark">{{ count($selectedMembershipIds) }}</div>
                                        <div class="mt-2 text-muted small">Destination: <span class="fw-bold text-dark">{{ $migrationTargetTierId ? App\Models\MembershipTier::find($migrationTargetTierId)?->name : 'N/A' }}</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 border border-dashed rounded-4 bg-soft-info text-center">
                                        <div class="fs-12 text-uppercase fw-bold text-info mb-1">Items to Move</div>
                                        <div class="fs-28 fw-bold text-dark">{{ $brokenRestrictions['total'] }}</div>
                                        <div class="mt-2 text-muted small">Destination: <span class="fw-bold text-dark">{{ $restrictionTargetTierId ? App\Models\MembershipTier::find($restrictionTargetTierId)?->name : 'Manual/Orphaned' }}</span></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                
                <div class="d-flex gap-2">
                    @if($migrationStep > 1)
                        <button type="button" class="btn btn-light" wire:click="previousStep">Back</button>
                    @endif
                    
                    @if($migrationStep < 3)
                        <button type="button" class="btn btn-primary" wire:click="nextStep" wire:loading.attr="disabled">
                            Next Step <i class="ri-arrow-right-line align-middle ms-1"></i>
                        </button>
                    @else
                        <button type="button" class="btn btn-success" wire:click="executeMigration" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="executeMigration">Confirm & Execute <i class="ri-check-line ms-1"></i></span>
                            <span wire:loading wire:target="executeMigration">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Executing...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Scoped Stepper Styles */
.archive-product-stepper .ap-stepper { 
    gap: 24px; 
}
.archive-product-stepper .ap-step { 
    flex: 1 1 0; 
    position: relative; 
    display: flex; 
    justify-content: center; 
}
/* Connector Line - Only visible on non-last steps */
.archive-product-stepper .ap-step:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 50%;
    /* Start from center + approx half pill width + spacing */
    left: calc(50% + 80px); 
    right: -80px; /* Extend into next item's space */
    height: 3px;
    background: var(--vz-light);
    transform: translateY(-50%);
    z-index: 1;
}

/* Adjust connector length for responsive if needed */
@media (max-width: 576px) {
    .archive-product-stepper .ap-step:not(:last-child)::after {
        left: calc(50% + 40px);
        right: -40px;
    }
}

.archive-product-stepper .ap-pill { 
    position: relative; 
    z-index: 2; 
    background: var(--vz-card-bg-custom, #fff); 
    border: 2px solid var(--vz-light); 
    border-radius: 999px; 
    padding: 10px 22px; 
    display: flex; 
    align-items: center; 
    gap: 10px;
    transition: all 0.3s ease;
    color: var(--vz-body-color);
}

.archive-product-stepper .ap-pill:hover {
    border-color: var(--vz-primary);
}

.archive-product-stepper .ap-pill.active {
    border-color: var(--vz-success);
    color: var(--vz-success);
    background-color: var(--vz-card-bg-custom, #fff);
}

.archive-product-stepper .ap-pill.done {
    border-color: var(--vz-success);
    color: var(--vz-success);
    background-color: rgba(10, 187, 135, 0.1);
}

.archive-product-stepper .step-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: var(--vz-light);
    color: var(--vz-muted);
    font-size: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.archive-product-stepper .ap-pill.active .step-icon,
.archive-product-stepper .ap-pill.done .step-icon {
    background-color: var(--vz-success);
    color: #fff;
}
</style>
