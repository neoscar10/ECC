<div class="row g-4">
    <!-- Restriction Settings -->
    <div class="col-lg-7">
        <h6 class="fw-semibold mb-3">Restriction Settings</h6>
        <div class="row g-3 p-3 bg-light rounded border">
            <div class="col-md-12">
                <label class="form-label">Restriction Mode</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="restrictionMode" id="modePublic" value="public" wire:model.live="restrictionMode">
                    <label class="btn btn-outline-success" for="modePublic">Public</label>

                    <input type="radio" class="btn-check" name="restrictionMode" id="modeRestricted" value="restricted" wire:model.live="restrictionMode">
                    <label class="btn btn-outline-warning" for="modeRestricted">Restricted</label>
                </div>
            </div>

            @if($restrictionMode === 'restricted')
                <div class="col-md-12">
                    <label class="form-label">Restriction Type</label>
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
                        <label class="form-label">Minimum Required Tier</label>
                        <select class="form-select" wire:model="restrictedMinTierId">
                            <option value="">Select Minimum Tier</option>
                            @foreach($membershipTiers as $tier)
                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Users with this tier OR HIGHER will have access.</div>
                        @error('restrictedMinTierId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                @elseif($restrictionType === 'random')
                    <div class="col-md-12">
                        <label class="form-label">Select Allowed Tiers</label>
                        <div class="row g-2">
                            @foreach($membershipTiers as $tier)
                                <div class="col-6">
                                    <div class="form-check card-radio">
                                        <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" wire:model="selectedRandomTiers" id="randTier_{{ $tier->id }}">
                                        <label class="form-check-label" for="randTier_{{ $tier->id }}">
                                            <span class="fs-14 mb-1 d-block">{{ $tier->name }}</span>
                                            <span class="text-muted text-xs">Level {{ $tier->level }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('selectedRandomTiers') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                @elseif($restrictionType === 'private')
                    <div class="col-md-12">
                        <label class="form-label">Private Tier Access</label>
                        <select class="form-select" wire:model="restrictedPrivateTierId">
                            <option value="">Select Tier</option>
                            @foreach($membershipTiers as $tier)
                                <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">ONLY users with exactly this tier will have access.</div>
                        @error('restrictedPrivateTierId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Summary Panel -->
    <div class="col-lg-5">
        <h6 class="fw-semibold mb-3">Review Details</h6>
        <div class="card border mb-0">
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted text-uppercase fw-medium fs-11">Product</label>
                    <p class="fs-14 mb-0 fw-bold">{{ $title ?: 'Untitled Product' }}</p>
                    @php $catMatch = $categories->where('id', $categoryId)->first(); @endphp
                    <p class="text-muted mb-0">{{ $catMatch ? $catMatch->title : 'No Category' }}</p>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted text-uppercase fw-medium fs-11">Availability</label>
                    @if($goLiveNow)
                        <div class="badge bg-success-subtle text-success">Live Now</div>
                    @else
                        <div class="badge bg-warning-subtle text-warning">Scheduled: {{ $goLiveAt ? \Carbon\Carbon::parse($goLiveAt)->format('d M Y, h:i A') : 'TBD' }}</div>
                        @if($allowsEarlyAccess)
                            <div class="badge bg-info-subtle text-info mt-1">Early Access Enabled</div>
                        @endif
                    @endif
                </div>

                <div class="mb-3">
                    <label class="text-muted text-uppercase fw-medium fs-11">Images</label>
                    <div class="d-flex justify-content-between">
                        <span>Main Images:</span>
                        <span class="fw-bold">{{ count($existingImages) + count($newImages) }}</span>
                    </div>
                     <div class="d-flex justify-content-between">
                        <span>360° Images:</span>
                        <span class="fw-bold">{{ count($existing360Images) + count($new360Images) }}</span>
                    </div>
                </div>

                 <div class="mb-0">
                    <label class="text-muted text-uppercase fw-medium fs-11">Access</label>
                    <div class="d-flex align-items-center">
                        @if($restrictionMode === 'public')
                             <span class="badge badge-outline-success">Public</span>
                        @else
                             <span class="badge badge-outline-warning">Restricted</span>
                             <span class="ms-1 text-xs text-muted">({{ ucfirst($restrictionType) }})</span>
                        @endif
                    </div>
                 </div>
            </div>
        </div>
    </div>
</div>
