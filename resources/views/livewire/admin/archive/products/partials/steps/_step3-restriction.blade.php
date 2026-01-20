<div class="row g-4">
    <!-- Visibility Settings -->
    <div class="col-lg-6">
        <h6 class="fw-semibold mb-3">Visibility</h6>
        <div class="card bg-light border p-3 h-100">
            <div class="mb-3">
                <p class="text-muted small">Visibility controls who can access this product at all. Users who do not meet these criteria will not see the item.</p>
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
                        @php
                            $selectedCat = $categories->firstWhere('id', $categoryId);
                            $catTitle = $selectedCat ? $selectedCat->title : 'Category';
                        @endphp
                        @foreach($membershipTiers as $tier)
                            @php
                                $isAllowed = in_array($tier->id, $categoryAllowedTierIds);
                            @endphp
                            <div class="col-6">
                                <div class="form-check card-radio position-relative {{ !$isAllowed ? 'opacity-50' : '' }}" 
                                     @if(!$isAllowed) 
                                         data-bs-toggle="tooltip" 
                                         title="Not allowed by category: {{ $catTitle }}" 
                                         style="cursor: not-allowed;"
                                     @endif>
                                    <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" 
                                           wire:model.live="selectedVisibilityTiers" 
                                           id="visTier_{{ $tier->id }}"
                                           @disabled(!$isAllowed)>
                                    <label class="form-check-label" for="visTier_{{ $tier->id }}" style="{{ !$isAllowed ? 'pointer-events: none;' : '' }}">
                                        <span class="fs-14 mb-1 d-block">{{ $tier->name }}</span>
                                        <span class="text-muted text-xs">Level {{ $tier->level }}</span>
                                    </label>
                                    @if(!$isAllowed)
                                        <div class="position-absolute top-50 start-50 translate-middle">
                                            <i class="ri-lock-2-fill fs-24 text-dark"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('selectedVisibilityTiers') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    
                    <script>
                        // Re-init tooltips on Livewire update
                        document.addEventListener('livewire:initialized', () => {
                             var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                             var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                               return new bootstrap.Tooltip(tooltipTriggerEl)
                             })
                        });
                        document.addEventListener('livewire:updated', () => {
                             var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                             var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                               return new bootstrap.Tooltip(tooltipTriggerEl)
                             })
                        });
                    </script>
                 </div>
            @else
            
                 <div class="alert alert-info border-0 mb-3">
                    <i class="ri-information-line me-1"></i> Public: Product follows category visibility.
                 </div>
                 
                 <div class="mb-3">
                     <label class="form-label text-muted fs-12 text-uppercase fw-bold">Allowed by Category</label>
                     <div class="d-flex flex-wrap gap-2">
                         @forelse($membershipTiers->whereIn('id', $categoryAllowedTierIds) as $tier)
                             <span class="badge bg-light text-body border">{{ $tier->name }}</span>
                         @empty
                             <span class="badge bg-danger-subtle text-danger border border-danger-subtle">No Tiers Allowed (Category Hidden)</span>
                         @endforelse
                     </div>
                 </div>
            @endif
        </div>
    </div>

    <!-- Blur / Clear View -->
    <div class="col-lg-6">
        <h6 class="fw-semibold mb-3">Blur / Clear View</h6>
        <div class="card bg-light border p-3 h-100">
             <div class="mb-3">
                <p class="text-muted small">Among users who can access the product, select which tiers see it clearly. Others will see a blurred preview.</p>
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
                            @foreach($this->visibilityAllowedTiers as $tier)
                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Visible users with this tier OR HIGHER will see clear content.</div>
                        @error('restrictedMinTierId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                @elseif($restrictionType === 'random')
                    <div class="col-md-12">
                        <label class="form-label">Select Clear View Tiers</label>
                        <div class="row g-2" wire:key="clear-tiers-{{ $categoryId }}-{{ $restrictionMode }}-{{ md5(json_encode($computedVisibilityTierIds)) }}">
                            @foreach($this->visibilityAllowedTiers as $tier)
                                <div class="col-6">
                                    <div class="form-check card-radio">
                                        <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" wire:model.live="selectedRandomTiers" id="randTier_{{ $tier->id }}">
                                        <label class="form-check-label" for="randTier_{{ $tier->id }}">
                                            <span class="fs-14 mb-1 d-block">{{ $tier->name }}</span>
                                            <span class="text-muted text-xs">Level {{ $tier->level }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                         @if(empty($computedVisibilityTierIds))
                            <p class="text-muted small">No tiers visible.</p>
                        @endif
                        @error('selectedRandomTiers') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                @elseif($restrictionType === 'private')
                    <div class="col-md-12">
                        <label class="form-label">Private Clear View Tier</label>
                        <select class="form-select" wire:model.live="restrictedPrivateTierId">
                            <option value="">Select Tier</option>
                            @foreach($this->visibilityAllowedTiers as $tier)
                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
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
