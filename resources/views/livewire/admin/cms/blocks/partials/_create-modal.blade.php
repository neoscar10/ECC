<!-- Create/Edit Modal -->
<div class="modal fade" id="createBlockModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $isEditMode ? 'Edit Block' : 'Create Block' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeModal"></button>
            </div>
            <div class="modal-body">
                <!-- Wizard Progress -->
                <div class="progress nav-progress nav-progress-custom mb-4">
                    <div class="progress-bar" role="progressbar" style="width: {{ ($createStep / 2) * 100 }}%" aria-valuenow="{{ ($createStep / 2) * 100 }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                
                <div class="d-flex justify-content-between mb-4">
                    <div class="{{ $createStep >= 1 ? 'text-primary' : 'text-muted' }}">1. Content</div>
                    <div class="{{ $createStep >= 2 ? 'text-primary' : 'text-muted' }}">2. Access & Restrictions.</div>
                </div>

                <!-- Step 1: Content -->
                @if($createStep === 1)
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Internal Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" wire:model="title" placeholder="e.g. Homepage Hero">
                                @error('title') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                             <div class="mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model="type">
                                    <option value="card">Card</option>
                                    <option value="banner">Banner</option>
                                </select>
                                @error('type') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <hr>
                            <h6 class="fw-semibold">Content Fields</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Display Title</label>
                                <input type="text" class="form-control" wire:model="contentTitle" placeholder="The Sovereign Collection">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subtitle</label>
                                <input type="text" class="form-control" wire:model="contentSubtitle" placeholder="Exclusive access">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Body Text</label>
                                <textarea class="form-control" wire:model="contentBody" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CTA Text</label>
                                    <input type="text" class="form-control" wire:model="contentCtaText" placeholder="View Collection">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CTA URL</label>
                                    <input type="text" class="form-control" wire:model="contentCtaUrl" placeholder="/collection/sovereign">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" wire:model="sortOrder">
                            </div>
                            <div class="form-check form-switch form-switch-lg mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" wire:model="isActive">
                                <label class="form-check-label" for="isActiveSwitch">Active</label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control mb-2" wire:model="contentImage" accept="image/png,image/jpeg">
                                @if($contentImage)
                                     <img src="{{ $contentImage->temporaryUrl() }}" class="img-fluid rounded border" style="max-height: 200px;">
                                @elseif($existingContentImage)
                                     <img src="{{ $existingContentImage }}" class="img-fluid rounded border" style="max-height: 200px;">
                                @endif
                                @error('contentImage') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Step 2: Access & Restrictions -->
                @if($createStep === 2)
                    <div class="row g-4">
                        <!-- Visibility Settings -->
                        <div class="col-lg-6">
                            <h6 class="fw-semibold mb-3">Visibility</h6>
                            <div class="card bg-light border p-3 h-100">
                                <div class="mb-3">
                                    <p class="text-muted small">Visibility controls who can access this block at all.</p>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Visibility Mode</label>
                                    <select class="form-select" wire:model.live="restrictionMode">
                                         <option value="public">Public (Everyone)</option>
                                         <option value="restricted">Restricted (Members Only)</option>
                                    </select>
                                    @error('restrictionMode') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                </div>

                                @if($restrictionMode === 'restricted')
                                     <div class="col-md-12">
                                        <label class="form-label">Select Tiers with Access <span class="text-danger">*</span></label>
                                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                                            @foreach($membershipTiers as $tier)
                                                <div class="col-6">
                                                    <div class="form-check card-radio">
                                                        <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" 
                                                               wire:model.live="selectedVisibilityTiers" 
                                                               id="visTier_{{ $tier->id }}">
                                                        <label class="form-check-label" for="visTier_{{ $tier->id }}">
                                                            <span class="fs-14 mb-1 d-block">{{ $tier->name }}</span>
                                                            <span class="text-muted text-xs">Level {{ $tier->level }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('selectedVisibilityTiers') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                     </div>
                                @else
                                     <div class="alert alert-info border-0 mb-3">
                                        <i class="ri-information-line me-1"></i> Public: Visible to everyone.
                                     </div>
                                @endif
                            </div>
                        </div>

                        <!-- Blur / Clear View -->
                        <div class="col-lg-6">
                            <h6 class="fw-semibold mb-3">Blur / Clear View</h6>
                            <div class="card bg-light border p-3 h-100">
                                 <div class="mb-3">
                                    <p class="text-muted small">Among users who can access the block, select which tiers see it clearly. Others will see a blurred preview.</p>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Enable Blur</label>
                                    <div class="form-check form-switch form-switch-lg">
                                        <input class="form-check-input" type="checkbox" role="switch" id="blurEnabledSwitch" wire:model.live="blurEnabled">
                                        <label class="form-check-label" for="blurEnabledSwitch">Blur content for lower allowed tiers?</label>
                                    </div>
                                </div>
                                
                                @if($blurEnabled)
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Blur Restriction Strategy</label>
                                        <select class="form-select" wire:model.live="restrictionType">
                                            <option value="">Select Type...</option>
                                            <option value="hierarchical">Hierarchical (Minimum Tier)</option>
                                            <option value="random">Random (Specific Tiers)</option>
                                            <option value="private">Private (Single Tier)</option>
                                        </select>
                                        @error('restrictionType') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    @if($restrictionType === 'hierarchical')
                                        <div class="col-md-12">
                                            <label class="form-label">Minimum Clear View Tier</label>
                                            <select class="form-select" wire:model.live="restrictedMinTierId">
                                                <option value="">Select Minimum Tier</option>
                                                @foreach($membershipTiers as $tier)
                                                     @if(in_array($tier->id, $computedVisibilityTierIds))
                                                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                                     @endif
                                                @endforeach
                                            </select>
                                            <div class="form-text">Visible users with this tier OR HIGHER will see clear content.</div>
                                            @error('restrictedMinTierId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif($restrictionType === 'random')
                                        {{-- Random types logic --}}
                                        <div class="alert alert-warning border-0 mb-3">
                                            <i class="ri-alert-line me-1"></i> Random (Allowlist) Blur not explicitly supported in UI yet. All visible tiers will see clear content.
                                        </div>
                                    @elseif($restrictionType === 'private')
                                        <div class="col-md-12">
                                            <label class="form-label">Private Clear View Tier</label>
                                            <select class="form-select" wire:model.live="restrictedPrivateTierId">
                                                <option value="">Select Tier</option>
                                                @foreach($membershipTiers as $tier)
                                                     @if(in_array($tier->id, $computedVisibilityTierIds))
                                                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                                     @endif
                                                @endforeach
                                            </select>
                                            <div class="form-text">ONLY users with exactly this tier will see clear content.</div>
                                            @error('restrictedPrivateTierId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            
            <div class="modal-footer">
                @if($createStep > 1)
                    <button type="button" class="btn btn-light" wire:click="prevStep">Previous</button>
                @endif
                
                @if($createStep < 2)
                    <button type="button" class="btn btn-primary" wire:click="nextStep">Next</button>
                @else
                    <button type="button" class="btn btn-success" wire:click="store">
                        @if($isEditMode) Update Block @else Create Block @endif
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
