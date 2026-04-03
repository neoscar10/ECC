@push('styles')
<style>
    @media (min-width: 992px) {
        .cms-preview-sticky {
            position: sticky;
            top: 20px; /* Adjust as needed, user suggested 1rem or 90px. 20px matches existing behavior */
            z-index: 9;
        }
    }
    @media (max-width: 991.98px) {
        .cms-preview-sticky {
            position: static;
        }
    }
</style>
@endpush

<div class="row g-4">
    <!-- Builder Form -->
    <div class="col-lg-7">
        <div class="card border shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold">Content Builder</h6>
            </div>
            
            <!-- Summary Panel -->
            @if($builderSummary)
            <div class="px-3 pt-3">
                <div class="card bg-light border-0 mb-0">
                    <div class="card-body p-2">
                         <div class="row g-2">
                             @foreach($builderSummary as $label => $val)
                                 <div class="col-6 col-sm-4">
                                     <small class="text-uppercase text-muted fs-10 d-block">{{ $label }}</small>
                                     <span class="fw-medium fs-12 text-dark">{{ $val }}</span>
                                 </div>
                             @endforeach
                         </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="card-body">
                <!-- Common Fields -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                         <label class="form-label">Display Title <span class="text-danger">*</span></label>
                         <input type="text" class="form-control" wire:model.live="contentTitle" placeholder="Public title">
                         @error('contentTitle') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    @if(!($type === 'slider' && $sliderMode === 'category'))
                    <div class="col-md-6">
                         <label class="form-label">Subtitle</label>
                         <input type="text" class="form-control" wire:model.live="contentSubtitle">
                    </div>
                    <div class="col-md-6">
                         <label class="form-label">Badge Text</label>
                         <input type="text" class="form-control" wire:model.live="contentBadge" placeholder="e.g. New">
                    </div>
                    @endif


                    
                    @if($type !== 'slider' && $type !== 'banner')
                    <div class="col-12">
                         <label class="form-label">Body Text</label>
                         <textarea class="form-control" rows="2" wire:model.live="contentBody"></textarea>
                    </div>
                    @endif
                </div>

                <!-- Specific Fields -->
                @if($type === 'banner' || $type === 'card' || ($type === 'slider' && $sliderMode === 'images'))
                     <!-- Image Upload Logic -->
                     @if($type !== 'slider')
                         <div class="mb-4">
                             <label class="form-label">Main Image</label>
                             <input type="file" class="form-control mb-1" wire:model.live="contentImage" accept="image/png,image/jpeg">
                             @if($type === 'banner')
                                 <div class="form-text text-muted mb-2">Recommended: 1080 &times; 1350</div>
                             @else
                                 <div class="form-text text-muted mb-2">Recommended: 1080 &times; 1080</div>
                             @endif
                             @if($existingContentImage)
                                 <div class="d-flex align-items-center gap-2">
                                     <img src="{{ $existingContentImage }}" height="40" class="rounded">
                                     <span class="text-muted fs-12">Current Image</span>
                                 </div>
                             @endif
                             @error('contentImage') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                     @endif

                     <div class="row mt-3">
                        <div class="col-md-6">
                             <label class="form-label">Text Position</label>
                             <div class="d-flex gap-3">
                                 <div class="form-check">
                                     <input class="form-check-input" type="radio" name="textPos" id="tpBelow" value="below" wire:model.live="textPosition">
                                     <label class="form-check-label" for="tpBelow">Below Image</label>
                                 </div>
                                 <div class="form-check">
                                     <input class="form-check-input" type="radio" name="textPos" id="tpAbove" value="above" wire:model.live="textPosition">
                                     <label class="form-check-label" for="tpAbove">Overlay Image</label>
                                 </div>
                             </div>
                        </div>
                     </div>
                @endif

                <!-- Slider Specifics -->
                @if($type === 'slider')
                    <hr class="border-dashed my-4">
                    <h6 class="mb-3 text-primary">Slider Configuration</h6>
                    
                    <div class="row g-3">
                         <div class="col-md-6">
                             <label class="form-label">Source System</label>
                             <select class="form-select" wire:model.live="sliderSource">
                                 <option value="">Select Source...</option>
                                 <option value="shop">Shop Products</option>
                                 <option value="archive">Archive Products</option>
                                 <option value="auctions">Auctions</option>
                             </select>
                             @error('sliderSource') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                    </div>

                    <!-- Category Mode -->
                    @if($sliderMode === 'category')
                        @if($sliderSource === 'auctions')
                             <div class="mt-3" x-data="{ 
                                open: @entangle('lotsDropdownOpen'),
                                closeDropdown() { this.open = false; } 
                             }" @click.outside="closeDropdown()">
                                 <label class="form-label">Select Auction Lots <span class="text-danger">*</span></label>
                                 <p class="text-muted text-xs">Search and select one or more lots to display.</p>
                                 
                                 <div class="position-relative">
                                     <div class="input-group">
                                         <span class="input-group-text"><i class="ri-search-line"></i></span>
                                         <input type="text" class="form-control" 
                                            placeholder="Search lots by title or number..." 
                                            wire:model.live.debounce.300ms="lotSearch"
                                            wire:focus="focusLotsSearch"
                                            @keydown.escape="closeDropdown()"
                                         >
                                     </div>

                                     <!-- Dropdown Results -->
                                     <div x-show="open" x-transition 
                                          class="position-absolute w-100 bg-white border shadow rounded mt-1 z-3" 
                                          style="max-height: 260px; overflow-y: auto; display: none;">
                                          
                                          @if(count($lotSearchResults) > 0)
                                             <div class="list-group list-group-flush">
                                                 @foreach($lotSearchResults as $item)
                                                     @php
                                                         $isSelected = collect($selectedSliderItems)->contains('id', $item['id']);
                                                     @endphp
                                                     <button type="button" 
                                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $isSelected ? 'bg-light text-muted' : '' }}" 
                                                        wire:click="addSliderItem({{ $item['id'] }})"
                                                        @if($isSelected) disabled @endif
                                                     >
                                                         <div>
                                                             <div class="fw-semibold text-truncate" style="max-width: 250px;">{{ $item['title'] }}</div>
                                                             <div class="small text-muted d-flex gap-2">
                                                                 <span class="badge bg-light text-dark border">{{ ucfirst($item['status']) }}</span>
                                                                 <span>{{ $item['price'] }}</span>
                                                             </div>
                                                         </div>
                                                         @if(!$isSelected)
                                                            <span class="badge bg-primary-subtle text-primary">Add</span>
                                                         @else
                                                            <span class="badge bg-light text-muted"><i class="ri-check-line"></i></span>
                                                         @endif
                                                     </button>
                                                 @endforeach
                                             </div>
                                          @else
                                             <div class="p-3 text-center text-muted small">No lots found matching "{{ $lotSearch }}"</div>
                                          @endif
                                     </div>
                                 </div>

                                 <label class="form-label mt-3">Selected Lots </label>
                                 @if(count($selectedSliderItems) > 0)
                                     <ul class="list-group" wire:sortable="updateSliderItemOrder">
                                         @foreach($selectedSliderItems as $index => $item)
                                             <li class="list-group-item d-flex justify-content-between align-items-center" wire:sortable.item="{{ $item['id'] }}" draggable="true">
                                                 <div class="d-flex align-items-center gap-2">
                                                     <i class="ri-drag-move-2-line text-muted handle" wire:sortable.handle style="cursor: grab;"></i>
                                                     <span>{{ $item['title'] ?? ($item['name'] ?? 'Item') }}</span>
                                                 </div>
                                                 <button type="button" class="btn btn-sm btn-icon btn-ghost-danger" wire:click="removeSliderItem({{ $index }})"><i class="ri-delete-bin-line"></i></button>
                                             </li>
                                         @endforeach
                                     </ul>
                                 @else
                                     <div class="alert alert-light border border-dashed text-center text-muted mb-0">No lots selected yet.</div>
                                 @endif
                                 @error('selectedSliderItems') <span class="text-danger small">{{ $message }}</span> @enderror
                             </div>
                        
                        @else
                             <!-- Shop / Archive Categories -->
                             <div class="mt-3">
                                 <label class="form-label">Select Category <span class="text-danger">*</span></label>
                                 <select class="form-select" wire:model.live="sliderCategoryId">
                                      <option value="">Select Category...</option>
                                      @foreach($sourceCategories as $cat)
                                         <option value="{{ $cat['id'] }}">{!! $cat['name'] !!}</option>
                                      @endforeach
                                 </select>
                                 @error('sliderCategoryId') <span class="text-danger small">{{ $message }}</span> @enderror
                                 
                                 <div class="mt-3">
                                     <label class="form-label">Item Limit</label>
                                     <input type="number" class="form-control" wire:model.live="sliderLimit" min="1" max="50">
                                 </div>
                             </div>
                        @endif
                    
                    <!-- Manual Mode -->
                    @elseif($sliderMode === 'manual')
                        <div class="mt-3" x-data="{ 
                            open: @entangle('lotsDropdownOpen'), 
                            closeDropdown() { this.open = false; } 
                        }" @click.outside="closeDropdown()">
                            <label class="form-label">Select Items <span class="text-danger">*</span></label>
                            <p class="text-muted text-xs">Search and select items from the current source ({{ ucfirst($sliderSource ?: 'none') }}).</p>
                             
                            <div class="position-relative">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                                    <input type="text" class="form-control" 
                                        placeholder="Search {{ $sliderSource ?: 'items' }}..." 
                                        wire:model.live.debounce.300ms="itemSearchQuery"
                                        wire:focus="focusItemSearch"
                                        @keydown.escape="closeDropdown()"
                                    >
                                </div>
                                
                                <!-- Search Result Dropdown -->
                                <div x-show="open" x-transition 
                                     class="position-absolute w-100 bg-white border shadow rounded mt-1 z-3" 
                                     style="max-height: 260px; overflow-y: auto; display: none;">
                                     
                                     @if(count($searchResults) > 0)
                                        <div class="list-group list-group-flush">
                                            @foreach($searchResults as $item)
                                                @php
                                                    $isSelected = collect($selectedSliderItems)->contains('id', $item['id']);
                                                @endphp
                                                <button type="button" 
                                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $isSelected ? 'bg-light text-muted' : '' }}" 
                                                   wire:click="addSliderItem({{ $item['id'] }})"
                                                   @if($isSelected) disabled @endif
                                                >
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ $item['image'] ?? 'https://placehold.co/40x40/f3f3f9/adb5bd?text=img' }}" 
                                                             class="rounded" style="width:32px;height:32px;object-fit:cover;">
                                                        <div style="min-width: 0;">
                                                            <div class="fw-semibold text-truncate" style="max-width: 250px;">{{ $item['title'] }}</div>
                                                            <div class="small text-muted">{{ $item['price'] }}</div>
                                                        </div>
                                                    </div>
                                                    @if(!$isSelected)
                                                       <span class="badge bg-primary-subtle text-primary">Add</span>
                                                    @else
                                                       <span class="badge bg-light text-muted"><i class="ri-check-line"></i></span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                     @elseif(strlen($itemSearchQuery) > 1)
                                        <div class="p-3 text-center text-muted small">No items found matching "{{ $itemSearchQuery }}"</div>
                                     @else
                                        <div class="p-3 text-center text-muted small">Type to search for items...</div>
                                     @endif
                                </div>
                            </div>

                            <label class="form-label mt-3">Selected Items (Drag to reorder)</label>
                            @if(count($selectedSliderItems) > 0)
                                <ul class="list-group mb-0" wire:sortable="updateSliderItemOrder">
                                    @foreach($selectedSliderItems as $index => $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-center" wire:sortable.item="{{ $item['id'] }}" draggable="true">
                                            <div class="d-flex align-items-center gap-2 w-100 overflow-hidden">
                                                <i class="ri-drag-move-2-line text-muted handle" wire:sortable.handle style="cursor: grab;"></i>
                                                <img src="{{ $item['image'] ?? 'https://placehold.co/40x40/f3f3f9/adb5bd?text=img' }}" 
                                                     class="rounded" style="width:28px;height:28px;object-fit:cover;">
                                                <div class="text-truncate">
                                                    <span class="fw-medium">{{ $item['title'] ?? ($item['name'] ?? 'Item') }}</span>
                                                    <small class="text-muted d-block fs-10">{{ $item['price'] ?? ($item['meta'] ?? '') }}</small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-icon btn-ghost-danger flex-shrink-0" wire:click="removeSliderItem({{ $index }})"><i class="ri-delete-bin-line"></i></button>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="alert alert-light border border-dashed text-center text-muted mb-0">No items selected yet.</div>
                            @endif
                            @error('selectedSliderItems') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                        </div>

                    <!-- Images Mode -->
                    @elseif($sliderMode === 'images')
                        <div class="mt-3">
                             <div class="mb-3">
                                 <label class="form-label">Add Slide</label>
                                 <div class="d-flex gap-2">
                                     <input type="file" class="form-control" wire:model="newSlideImage" accept="image/png,image/jpeg">
                                     <button type="button" class="btn btn-secondary" wire:click="addSlide">Add</button>
                                 </div>
                                 <div class="form-text text-muted">Recommended: 1080 &times; 1080</div>
                                 <div wire:loading wire:target="newSlideImage" class="text-muted fs-12 mt-1">Uploading...</div>
                             </div>

                             <label class="form-label">Slides</label>
                             <div class="row g-2">
                                  @foreach($sliderImages as $index => $slide)
                                      <div class="col-12">
                                          <div class="card mb-0 shadow-none border">
                                              <div class="card-body p-2 d-flex gap-3 align-items-center">
                                                  <div style="width: 60px; height: 40px; background: #f0f0f0;">
                                                      <img src="{{ $slide['image_url'] }}" class="w-100 h-100 object-fit-cover rounded">
                                                  </div>
                                                  <div class="flex-grow-1">
                                                      <input type="text" class="form-control form-control-sm mb-1" placeholder="Title" wire:model.lazy="sliderImages.{{ $index }}.title">
                                                      <input type="text" class="form-control form-control-sm" placeholder="Subtitle" wire:model.lazy="sliderImages.{{ $index }}.subtitle">
                                                  </div>
                                                  <button type="button" class="btn btn-sm btn-icon btn-ghost-danger" wire:click="removeSlide({{ $index }})"><i class="ri-delete-bin-line"></i></button>
                                              </div>
                                          </div>
                                      </div>
                                  @endforeach
                             </div>
                             @error('sliderImages') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    @endif
                @endif
                
                <!-- Connectivity Toggles -->
                @if(!($type === 'slider' && $sliderMode === 'category'))
                <hr class="border-dashed my-4">
                
                <div class="row g-3 align-items-center mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Has Detail Page?</label>
                    <div class="form-check form-switch form-switch-lg">
                      <input class="form-check-input" type="checkbox" wire:model.live="hasDetailPage" id="hasDetailPage">
                      <label class="form-check-label" for="hasDetailPage">Enable a CMS detail page</label>
                    </div>
                  </div>

                  @if($type === 'banner' || $type === 'card')
                  <div class="col-md-6">
                    <label class="form-label">Has Target?</label>
                    <div class="form-check form-switch form-switch-lg">
                      <input class="form-check-input" type="checkbox" wire:model.live="hasTarget" id="hasTarget">
                      <label class="form-check-label" for="hasTarget">Link to a category or item</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <small class="text-muted">Choose either Detail Page or Target — not both.</small>
                    @error('target_conflict') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                  </div>
                  @endif
                </div>

                @if($hasTarget && ($type === 'banner' || $type === 'card'))
                  <div class="card border mt-3 bg-light">
                    <div class="card-body">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Target Type</label>
                          <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" id="targetKindCategory" value="category" wire:model.live="targetKind">
                            <label class="btn btn-outline-primary" for="targetKindCategory">Category</label>

                            <input type="radio" class="btn-check" id="targetKindItem" value="item" wire:model.live="targetKind">
                            <label class="btn btn-outline-primary" for="targetKindItem">Item</label>
                          </div>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Source</label>
                          <select class="form-select" wire:model.live="targetSource">
                            <option value="">Select source...</option>
                            @if($targetKind === 'category')
                              <option value="shop">Shop</option>
                              <option value="archive">Archive</option>
                            @else
                              <option value="shop">Shop</option>
                              <option value="archive">Archive</option>
                              <option value="auctions">Auctions</option>
                            @endif
                          </select>
                        </div>

                        <div class="col-12">
                          <label class="form-label">Select Target</label>
                          <style>
                          .target-card:hover {
                              border-color: var(--vz-primary) !important;
                              background-color: rgba(0,0,0,0.02);
                              transition: all 0.2s ease;
                          }
                          </style>
                          
                          <div class="input-group">
                            <span class="input-group-text"><i class="ri-search-line"></i></span>
                            <input type="text" class="form-control" placeholder="Search..." wire:model.live.debounce.350ms="targetSearch">
                          </div>

                          @if(empty($targetSearch) && !empty($browseResults) && empty($targetId))
                            <div class="browse-section mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Browse {{ ucfirst($targetKind) }}s</span>
                                    <small class="text-muted">Showing latest</small>
                                </div>
                          
                                <div class="row g-2" style="max-height: 280px; overflow-y: auto; overflow-x: hidden;">
                                    @foreach($browseResults as $row)
                                        <div class="col-6 col-md-4">
                                            <div class="target-card border rounded p-2 cursor-pointer h-100 hover-shadow"
                                                 wire:click="selectTarget({{ $row['id'] }}, '{{ addslashes($row['label']) }}')">
                                                <div class="d-flex gap-2 align-items-center h-100">
                                                    <img src="{{ $row['image'] ?? 'https://placehold.co/40x40/f3f3f9/adb5bd?text=img' }}" 
                                                         class="rounded"
                                                         style="width:40px;height:40px;object-fit:cover;">
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <div class="fw-medium text-truncate" style="font-size: 0.85rem;" title="{{ $row['label'] }}">{{ $row['label'] }}</div>
                                                        <small class="text-muted d-block text-truncate" style="font-size: 0.75rem;">{{ $row['meta'] }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                          @endif

                          @if(!empty($targetSearch) && empty($targetId))
                            <div class="search-results mt-3">
                                @if(empty($targetSearchResults))
                                    <div class="text-muted small">No results found for "{{ $targetSearch }}".</div>
                                @else
                                    <div class="row g-2" style="max-height: 280px; overflow-y: auto; overflow-x: hidden;">
                                        @foreach($targetSearchResults as $row)
                                            <div class="col-6 col-md-4">
                                                <div class="target-card border rounded p-2 cursor-pointer h-100 hover-shadow"
                                                     wire:click="selectTarget({{ $row['id'] }}, '{{ addslashes($row['label']) }}')">
                                                    <div class="d-flex gap-2 align-items-center h-100">
                                                        <img src="{{ $row['image'] ?? 'https://placehold.co/40x40/f3f3f9/adb5bd?text=img' }}" 
                                                             class="rounded"
                                                             style="width:40px;height:40px;object-fit:cover;">
                                                        <div class="flex-grow-1" style="min-width: 0;">
                                                            <div class="fw-medium text-truncate" style="font-size: 0.85rem;" title="{{ $row['label'] }}">{{ $row['label'] }}</div>
                                                            <small class="text-muted d-block text-truncate" style="font-size: 0.75rem;">{{ $row['meta'] }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                          @endif

                          @if($targetId)
                            <div class="d-flex align-items-center justify-content-between border rounded p-2 mt-2 bg-white">
                              <div>
                                <div class="fw-semibold">{{ $targetLabel }}</div>
                                <small class="text-muted">Selected target</small>
                              </div>
                              <button type="button" class="btn btn-sm btn-ghost-danger" wire:click="clearTargetState">
                                <i class="ri-close-line"></i>
                              </button>
                            </div>
                          @endif

                          @error('targetId') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                      </div>
                    </div>
                  </div>
                @endif

                @if($hasDetailPage || $hasTarget)
                    <div class="row g-3 mt-1 mb-3">
                         <div class="col-12">
                              <label class="form-label">Button Text <span class="text-danger">*</span></label>
                              <input type="text" class="form-control" wire:model.live="contentCtaText" placeholder="e.g. Read More, Shop Now">
                              @error('contentCtaText') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                    </div>
                @endif

                @if($hasDetailPage)
                    <div class="row g-3 mt-2">
                         <div class="col-12">
                             <label class="form-label">Detail Content (Markdown) <span class="text-danger">*</span></label>
                             <x-ui.markdown-editor wire:model.lazy="contentMarkdown" :value="$contentMarkdown" id="blockMarkdown" height="300" />
                             @error('contentMarkdown') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                    </div>
                @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="col-12 col-lg-5">
        <div class="cms-preview-sticky">
            <div class="card border shadow-sm">
                <div class="card-header bg-light d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold text-uppercase fs-12 text-muted">Rough Live Mobile Preview</h6>
                    <span class="badge bg-white text-dark border">Phone</span>
                </div>
                <div class="card-body bg-light d-flex justify-content-center p-3">
                    @include('livewire.admin.cms.blocks.partials._phone_preview', [
                        'previewMode' => 'builder',
                        'previewScopeId' => $previewScopeId
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
