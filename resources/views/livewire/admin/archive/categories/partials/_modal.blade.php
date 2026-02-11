<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true" wire:ignore.self data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">
                    {{ $isEditMode ? 'Edit Category' : 'Add Category' }}
                </h5>
                <button type="button" class="btn-close" wire:click="closeModal" aria-label="Close"></button>
            </div>
            <form wire:submit.prevent="{{ $isEditMode ? 'update' : 'store' }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Category Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" wire:model="title" placeholder="Enter title">
                        @error('title') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" wire:model="description" rows="3" placeholder="Enter description"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" wire:model="image" accept="image/png,image/jpeg">
                        @error('image') <span class="text-danger">{{ $message }}</span> @enderror

                        @if ($image)
                            <div class="mt-2 text-center">
                                <img src="{{ $image->temporaryUrl() }}" class="img-fluid rounded" style="max-height: 150px;">
                            </div>
                        @else
                            @php
                                $p = $existingImage ? preg_replace('#^public/#', '', str_replace('\\','/',$existingImage)) : null;
                            @endphp
                            @if ($p)
                                <div class="mt-2 text-center">
                                    <img src="{{ Storage::url($p) }}" class="img-fluid rounded" style="max-height: 150px;">
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visibility <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model.live="visibility">
                                <option value="public">Public (All Users)</option>
                                <option value="restricted">Restricted (Specific Tiers)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch form-switch-lg">
                                <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" wire:model="is_active">
                                <label class="form-check-label" for="isActiveSwitch">Active</label>
                            </div>
                        </div>
                    </div>

                    @if ($visibility === 'restricted')
                        <div class="mb-3 border rounded p-3 bg-light">
                            <label class="form-label fw-bold">Select Allowed Tiers <span class="text-danger">*</span></label>
                            <p class="text-muted fs-12">Only members with these tiers can view this category.</p>
                            
                            <div style="max-height: 200px; overflow-y: auto;">
                                @foreach ($membershipTiers as $tier)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" value="{{ $tier->id }}" id="tier_{{ $tier->id }}" wire:model="selectedTiers">
                                        <label class="form-check-label" for="tier_{{ $tier->id }}">
                                            {{ $tier->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedTiers') <span class="text-danger d-block mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeModal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        {{ $isEditMode ? 'Update Category' : 'Create Category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
