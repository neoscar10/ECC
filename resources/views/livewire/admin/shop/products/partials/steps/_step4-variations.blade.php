<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Variations & Stock</h5>
    @if($has_variants)
    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addVariationGroup">
        <i class="ri-add-line align-middle me-1"></i> Add Variation Group
    </button>
    @endif
</div>

<div class="card border border-dashed shadow-none mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            {{-- Toggle Column --}}
            <div class="col-lg-6">
                <div class="form-check form-switch form-switch-lg mb-0" dir="ltr">
                    <input class="form-check-input" type="checkbox" id="hasVariantsToggle" wire:model.live="has_variants" @if($variantsLocked) disabled @endif>
                    <label class="form-check-label ms-2 align-middle" for="hasVariantsToggle">This product has variations?</label>
                </div>
                @if($variantsLocked)
                    <div class="text-warning small mt-2">
                        <i class="ri-lock-line align-middle me-1"></i> This product already has variations. To make it a simple product, remove all variation groups first. (Requires save)
                    </div>
                @elseif(!$has_variants)
                     <div class="text-muted small mt-2">Used for products without variations.</div>
                @endif
            </div>

            {{-- Stock Input Column (Only if Simple) --}}
            @if(!$has_variants)
            <div class="col-lg-6">
                <label class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" class="form-control" wire:model.live="stock_qty" placeholder="0">
                    <span class="input-group-text">units</span>
                </div>
                @error('stock_qty') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
            @endif
        </div>
        
        @if(!$has_variants)
            <div class="alert alert-info bg-soft-info border-0 mt-3 mb-0" role="alert">
                <i class="ri-information-line me-1 align-middle"></i> If you need Size/Color options, turn on variations above.
            </div>
        @endif
    </div>
</div>

@if($has_variants)
    {{-- Variation Builder --}}
    @if(empty($variationGroups))
        <div class="text-center p-5 border-dashed rounded bg-light">
            <i class="ri-shirt-line fs-22 text-muted"></i>
            <p class="text-muted mt-2">No variations added yet.</p>
            <button type="button" class="btn btn-sm btn-primary" wire:click="addVariationGroup">Start Adding Variations</button>
        </div>
    @else
        <div class="accordion" id="accordionVariations">
            @foreach($variationGroups as $gIndex => $group)
                <div class="accordion-item border mb-3" wire:key="group-{{ $gIndex }}">
                    <div class="accordion-header d-flex align-items-center bg-light px-3 py-2 rounded-top" id="heading{{ $gIndex }}">
                        <button class="accordion-button bg-transparent shadow-none p-0 flex-grow-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $gIndex }}" aria-expanded="true">
                            <span class="fw-semibold">{{ $group['name'] ?: 'New Variation Group' }}</span>
                            <span class="badge bg-soft-info text-info ms-2">{{ count($group['values']) }} values</span>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-ghost-danger ms-2" wire:click="removeVariationGroup({{ $gIndex }})">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    <div id="collapse{{ $gIndex }}" class="accordion-collapse collapse show" aria-labelledby="heading{{ $gIndex }}">
                        <div class="accordion-body border-top">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Group Name</label>
                                    <input type="text" class="form-control" wire:model.live="variationGroups.{{ $gIndex }}.name" placeholder="Size, Color...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" wire:model.live="variationGroups.{{ $gIndex }}.presentation_type">
                                        <option value="text">Text (Label)</option>
                                        <option value="color">Color Swatch</option>
                                        <option value="image">Image Thumbnail</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gallery Control?</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" wire:model.live="variationGroups.{{ $gIndex }}.has_images" wire:change="handleVariationImageToggle({{ $gIndex }})">
                                        <label class="form-check-label text-muted text-sm">Has Images</label>
                                    </div>
                                </div>
                            </div>
    
                            {{-- Values Table --}}
                            <div class="table-responsive border rounded">
                                <table class="table table-sm align-middle mb-0 table-nowrap">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Value</th>
                                            <th style="width: 120px;">Price</th>
                                            <th style="width: 100px;">Stock</th>
                                            @if($group['presentation_type'] == 'color') <th style="width: 80px;">Color</th> @endif
                                            @if($group['has_images']) <th>Gallery</th> @endif
                                            <th style="width: 80px;">Default</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group['values'] as $vIndex => $value)
                                            <tr wire:key="val-{{ $gIndex }}-{{ $vIndex }}">
                                                <td>
                                                    <input type="text" class="form-control form-control-sm" 
                                                        wire:model="variationGroups.{{ $gIndex }}.values.{{ $vIndex }}.caption" placeholder="Label">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" class="form-control form-control-sm" 
                                                        wire:model="variationGroups.{{ $gIndex }}.values.{{ $vIndex }}.price" placeholder="Override">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm" 
                                                        wire:model="variationGroups.{{ $gIndex }}.values.{{ $vIndex }}.stock_qty" placeholder="Qty">
                                                </td>
                                                @if($group['presentation_type'] == 'color')
                                                    <td>
                                                        <input type="color" class="form-control form-control-sm p-1" style="height: 30px;"
                                                            wire:model="variationGroups.{{ $gIndex }}.values.{{ $vIndex }}.color_hex">
                                                    </td>
                                                @endif
                                                
                                                @if($group['has_images'])
                                                    <td>
                                                         <button type="button" class="btn btn-xs btn-soft-secondary" 
                                                            wire:click="openVariationGallery({{ $gIndex }}, {{ $vIndex }})">
                                                            <i class="ri-image-add-line align-middle"></i> Manage
                                                            @php
                                                                $newCount = count($value['new_gallery_images'] ?? []);
                                                                // For edit mode, we'd also count existing. For now just new.
                                                                // If we had existing logic: $existingCount = count($value['existing_gallery_images'] ?? []);
                                                                $total = $newCount; 
                                                                if(isset($value['existing_gallery_images'])) {
                                                                    $total += count($value['existing_gallery_images']);
                                                                }
                                                            @endphp
                                                            @if($total > 0)
                                                                <span class="badge bg-secondary ms-1">{{ $total }}</span>
                                                            @endif
                                                         </button>
                                                    </td>
                                                @endif
    
                                                <td class="text-center">
                                                    <input class="form-check-input" type="radio" name="default_val_{{ $gIndex }}" 
                                                        wire:click="setVariationDefault({{ $gIndex }}, {{ $vIndex }})"
                                                        {{ $value['is_default'] ? 'checked' : '' }}>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-ghost-danger" wire:click="removeVariationValue({{ $gIndex }}, {{ $vIndex }})">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="p-2 border-top bg-light">
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none" wire:click="addVariationValue({{ $gIndex }})">
                                        <i class="ri-add-circle-line align-middle"></i> Add Value
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endif
