<div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createProductModalLabel">{{ $isEditMode ? 'Edit Product' : 'Add Product' }}</h5>
                <button type="button" class="btn-close" wire:click="closeModal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form wire:submit.prevent="{{ $isEditMode ? 'updateProduct' : 'storeProduct' }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Title & Category -->
                        <div class="col-md-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="title" placeholder="Enter product title">
                            @error('title') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="categoryId">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                @endforeach
                            </select>
                            @error('categoryId') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Price Range -->
                        <div class="col-md-6">
                            <label class="form-label">Min Price (Price from)</label>
                            <input type="number" class="form-control" wire:model="priceMin" placeholder="e.g. 1000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Max Price (Price to)</label>
                            <input type="number" class="form-control" wire:model="priceMax" placeholder="e.g. 5000">
                        </div>
                        
                        <!-- Descriptions -->
                        <div class="col-12">
                            <label class="form-label">Unlocked Description</label>
                            <textarea class="form-control" wire:model="descriptionUnlocked" rows="3" placeholder="Visible to everyone"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Locked Description <span class="text-muted">(Optional)</span></label>
                            <textarea class="form-control" wire:model="descriptionLocked" rows="3" placeholder="Visible only after access granted"></textarea>
                        </div>

                        <!-- Scheduling -->
                        <div class="col-12">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="goLiveNow" wire:model.live="goLiveNow">
                                <label class="form-check-label" for="goLiveNow">Go Live Now</label>
                            </div>
                        </div>
                        @if(!$goLiveNow)
                            <div class="col-md-6">
                                <label class="form-label">Go Live At</label>
                                <input type="datetime-local" class="form-control" wire:model="goLiveAt">
                                @error('goLiveAt') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-6 pt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="allowsEarlyAccess" wire:model="allowsEarlyAccess">
                                    <label class="form-check-label" for="allowsEarlyAccess">Enable Early Access</label>
                                </div>
                            </div>
                        @endif

                        <!-- Restriction Configuration -->
                        <div class="col-12 pt-3">
                            <h6 class="fw-semibold">Restriction Settings</h6>
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
                                    <div class="col-md-6">
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
                                        <div class="col-md-6">
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
                                        <div class="col-md-6">
                                            <label class="form-label">Select Allowed Tiers</label>
                                            <div class="row">
                                                @foreach($membershipTiers as $tier)
                                                    <div class="col-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" wire:model="selectedRandomTiers" id="randTier{{ $tier->id }}">
                                                            <label class="form-check-label" for="randTier{{ $tier->id }}">
                                                                {{ $tier->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @error('selectedRandomTiers') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    @elseif($restrictionType === 'private')
                                        <div class="col-md-6">
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

                        <!-- Images -->
                         <div class="col-12">
                            <label class="form-label">Product Images</label>
                            
                            <!-- Existing Images -->
                            @if(count($existingImages) > 0)
                                <div class="d-flex gap-2 flex-wrap mb-2">
                                    @foreach($existingImages as $img)
                                        <div class="position-relative" style="width: 80px; height: 80px;">
                                            <img src="{{ Storage::url(str_replace('\\', '/', $img->image_path)) }}" class="img-fluid rounded w-100 h-100 object-cover">
                                            <button type="button" class="btn btn-icon btn-sm btn-danger position-absolute top-0 end-0 rounded-circle" 
                                                    style="width: 20px; height: 20px; transform: translate(30%, -30%);"
                                                    wire:click="deleteImage({{ $img->id }})">
                                                <i class="ri-close-line" style="font-size: 10px;"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="dropzone p-4 border-dashed text-center rounded">
                                <i class="ri-upload-cloud-2-line display-4 text-muted mb-3 d-block"></i>
                                <input type="file" multiple wire:model="newImages">
                                <p class="mb-0 text-muted">Click or Drag images here to upload.</p>
                                @error('newImages') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                             @if ($newImages)
                                <div class="d-flex gap-2 mt-2">
                                    @foreach ($newImages as $tempImg)
                                         <div class="avatar-sm bg-light rounded">
                                             <img src="{{ $tempImg->temporaryUrl() }}" class="img-fluid rounded h-100">
                                         </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeModal" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">{{ $isEditMode ? 'Update Product' : 'Create Product' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // re-init plugins if needed (flatpickr for datetime) when modal opens?
    // Livewire updates DOM, so JS init might be lost. 
    // Using standard input type='datetime-local' is safer for now.
</script>
