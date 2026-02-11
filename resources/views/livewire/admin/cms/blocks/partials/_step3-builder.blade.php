<div class="row g-4">
    <!-- Builder Form -->
    <div class="col-lg-7">
        <div class="card border shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold">Content Builder</h6>
            </div>
            <div class="card-body">
                <!-- Common Fields -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                         <label class="form-label">Display Title <span class="text-danger">*</span></label>
                         <input type="text" class="form-control" wire:model.live="contentTitle" placeholder="Public title">
                         @error('contentTitle') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                         <label class="form-label">Subtitle</label>
                         <input type="text" class="form-control" wire:model.live="contentSubtitle">
                    </div>
                     <div class="col-md-6">
                         <label class="form-label">Badge Text</label>
                         <input type="text" class="form-control" wire:model.live="contentBadge" placeholder="e.g. New">
                    </div>
                    
                    @if($type !== 'slider')
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
                             <input type="file" class="form-control mb-2" wire:model.live="contentImage">
                             @if($existingContentImage)
                                 <div class="d-flex align-items-center gap-2">
                                     <img src="{{ $existingContentImage }}" height="40" class="rounded">
                                     <span class="text-muted fs-12">Current Image</span>
                                 </div>
                             @endif
                             @error('contentImage') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                     @endif
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
                         <div class="mt-3">
                             <label class="form-label">Select Category</label>
                             {{-- Dynamic dropdown based on source would go here. For now, simple input or mock --}}
                             <select class="form-select" wire:model.live="sliderCategoryId">
                                  <option value="">Select Category...</option>
                                  @foreach($sourceCategories as $cat)
                                     <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                  @endforeach
                             </select>
                             @error('sliderCategoryId') <span class="text-danger small">{{ $message }}</span> @enderror
                             
                             <div class="mt-3">
                                 <label class="form-label">Item Limit</label>
                                 <input type="number" class="form-control" wire:model.live="sliderLimit" min="1" max="50">
                             </div>
                         </div>
                    
                    <!-- Manual Mode -->
                    @elseif($sliderMode === 'manual')
                        <div class="mt-3">
                            <label class="form-label">Select Items</label>
                             {{-- Search/Select Component Mock --}}
                             <div class="input-group mb-2">
                                 <span class="input-group-text"><i class="ri-search-line"></i></span>
                                 <input type="text" class="form-control" placeholder="Search items..." wire:model.live.debounce.300ms="itemSearchQuery">
                             </div>
                             
                             <div class="list-group mb-3" style="max-height: 200px; overflow-y: auto;">
                                 @foreach($searchResults as $item)
                                     <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" wire:click="addSliderItem({{ $item['id'] }})">
                                         <span>{{ $item['name'] }}</span>
                                         <i class="ri-add-circle-line"></i>
                                     </button>
                                 @endforeach
                             </div>

                             <label class="form-label">Selected Items (Drag to reorder)</label>
                             <ul class="list-group" wire:sortable="updateSliderItemOrder">
                                 @foreach($selectedSliderItems as $index => $item)
                                     <li class="list-group-item d-flex justify-content-between align-items-center" wire:sortable.item="{{ $item['id'] }}" draggable="true">
                                         <div class="d-flex align-items-center gap-2">
                                             <i class="ri-drag-move-2-line text-muted handle" wire:sortable.handle></i>
                                             <span>{{ $item['name'] }}</span>
                                         </div>
                                         <button type="button" class="btn btn-sm btn-icon btn-ghost-danger" wire:click="removeSliderItem({{ $index }})"><i class="ri-delete-bin-line"></i></button>
                                     </li>
                                 @endforeach
                             </ul>
                             @error('selectedSliderItems') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                    <!-- Images Mode -->
                    @elseif($sliderMode === 'images')
                        <div class="mt-3">
                             <div class="mb-3">
                                 <label class="form-label">Add Slide</label>
                                 <div class="d-flex gap-2">
                                     <input type="file" class="form-control" wire:model="newSlideImage">
                                     <button type="button" class="btn btn-secondary" wire:click="addSlide">Add</button>
                                 </div>
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
                
                <!-- Detail Page Toggle -->
                <hr class="border-dashed my-4">
                <div class="form-check form-switch form-switch-md mb-3">
                     <input class="form-check-input" type="checkbox" role="switch" id="detailPageSwitch" wire:model.live="hasDetailPage">
                     <label class="form-check-label fw-bold" for="detailPageSwitch">Has Detail Page?</label>
                </div>

                @if($hasDetailPage)
                    <div class="row g-3">
                         <div class="col-md-6">
                             <label class="form-label">Button Text <span class="text-danger">*</span></label>
                             <input type="text" class="form-control" wire:model.live="contentCtaText" placeholder="e.g. Read More">
                             @error('contentCtaText') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                         <div class="col-12">
                             <label class="form-label">Detail Content (Markdown) <span class="text-danger">*</span></label>
                             <textarea class="form-control font-monospace" rows="6" wire:model.lazy="contentMarkdown"></textarea>
                             @error('contentMarkdown') <span class="text-danger small">{{ $message }}</span> @enderror
                         </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="col-lg-5">
        <div class="sticky-top" style="top: 20px; z-index: 1;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted text-uppercase fw-semibold fs-12 mb-0">Live Mobile Preview</h6>
                <span class="badge bg-light text-dark">iPhone 14</span>
            </div>
            @include('livewire.admin.cms.blocks.partials._mobile-preview')
        </div>
    </div>
</div>
