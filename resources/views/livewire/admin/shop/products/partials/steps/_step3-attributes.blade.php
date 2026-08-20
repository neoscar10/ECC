<div class="row g-3 h-100">
    <!-- Categories Column -->
    <div class="col-md-6 border-end">
        <div class="d-flex flex-column h-100">
            <div class="mb-3">
                <label class="form-label fw-bold">Categories <span class="text-white">*</span></label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text border-end-0 bg-light"><i class="ri-search-line"></i></span>
                    <input type="text" class="form-control border-start-0 bg-light" placeholder="Search categories..." wire:model.live="categorySearch">
                </div>
            </div>

            <div class="flex-grow-1 border rounded p-2 bg-white overflow-auto" style="min-height: 300px; max-height: 400px;">
                @if(count($this->filteredSortedCategories) > 0)
                    <div class="vstack gap-1">
                        @foreach($this->filteredSortedCategories as $cat)
                            <div class="form-check ps-3" wire:key="cat_{{ $cat->id }}">
                                <input class="form-check-input mt-1" type="checkbox" value="{{ $cat->id }}" wire:model.live="selectedCategories" id="cat_{{ $cat->id }}">
                                <label class="form-check-label d-block text-break" for="cat_{{ $cat->id }}" style="cursor: pointer;">
                                    <span class="fs-13">{{ $cat->display_name }}</span>
                                    @if($cat->display_path)
                                        <div class="text-muted" style="font-size: 0.7rem; line-height: 1.1;">
                                            <i class="ri-corner-down-right-line me-1"></i>{{ rtrim($cat->display_path, ' > ') }}
                                        </div>
                                    @endif
                                </label>
                            </div>
                            <hr>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted mt-5">
                        <small>No categories found.</small>
                    </div>
                @endif
            </div>
            <div class="mt-2 text-muted fs-11">
                Selected: {{ count($selectedCategories) }}
            </div>
        </div>
    </div>
    
    <!-- Tags & Size Guide Column -->
    <div class="col-md-6">
        <div class="d-flex flex-column h-100">
            <!-- Size Guide Section -->
            <div class="mb-4 pb-3 border-bottom">
                <label class="form-label fw-bold">Size Guide</label>
                <div class="text-muted fs-11 mb-2">Attach a size guide to display on the product detail page. If none is selected, the category's size guide will be used (if any).</div>
                <select class="form-select form-select-sm bg-light" wire:model="size_guide_id">
                    <option value="">None (Use Category Default)</option>
                    @foreach($sizeGuides as $guide)
                        <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Tags / Attributes</label>
                <div class="text-muted fs-11">Select one tag per group (Optional/Searchable).</div>
            </div>
            
            <div class="flex-grow-1 overflow-auto pe-2" style="max-height: 450px;">
                @php $tagGroups = $this->filteredTagGroups; @endphp
                
                @if(count($tagGroups) > 0)
                    <div class="accordion accordion-flush" id="tagGroupsAccordion">
                        @foreach($tagGroups as $group)
                            <div class="accordion-item mb-2 border rounded shadow-none" wire:key="group_{{ $group->id }}">
                                <h2 class="accordion-header" id="flush-heading-{{ $group->id }}">
                                    <button class="accordion-button collapsed py-2 {{ isset($selectedTagValueByGroup[$group->id]) && $selectedTagValueByGroup[$group->id] !== 'none' ? 'bg-success-subtle' : 'bg-light' }}" 
                                            type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#flush-collapse-{{ $group->id }}" aria-expanded="false">
                                        <div class="d-flex align-items-center w-100 me-2">
                                            <span class="fw-semibold text-uppercase fs-11 {{ isset($selectedTagValueByGroup[$group->id]) && $selectedTagValueByGroup[$group->id] !== 'none' ? 'text-success' : 'text-muted' }}">
                                                {{ $group->name }}
                                            </span>
                                            @if(isset($selectedTagValueByGroup[$group->id]) && $selectedTagValueByGroup[$group->id] !== 'none')
                                                <i class="ri-check-line text-success ms-auto fs-14"></i>
                                            @endif
                                        </div>
                                    </button>
                                </h2>
                                <div id="flush-collapse-{{ $group->id }}" class="accordion-collapse collapse" 
                                    data-bs-parent="#tagGroupsAccordion" wire:ignore.self>
                                    <div class="accordion-body p-2 bg-white border-top">
                                        <!-- Per-Group Search -->
                                        <div class="mb-2 position-relative">
                                            <input type="text" class="form-control form-control-sm ps-4" 
                                                wire:model.live.debounce.300ms="tagGroupSearches.{{ $group->id }}" 
                                                placeholder="Search {{ $group->name }}...">
                                            <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-11"></i>
                                        </div>
                                            
                                        <div style="max-height: 150px; overflow-y: auto;" class="ps-1">
                                            <div class="form-check mb-1">
                                                <input class="form-check-input" type="radio" 
                                                    name="tag_group_{{ $group->id }}" 
                                                    id="tag_group_{{ $group->id }}_none" 
                                                    wire:model="selectedTagValueByGroup.{{ $group->id }}" 
                                                    value="none">
                                                <label class="form-check-label text-muted small" for="tag_group_{{ $group->id }}_none">
                                                    None / Clear
                                                </label>
                                            </div>

                                            @foreach($group->tags as $tag)
                                                <div class="form-check mb-1" wire:key="tag_{{ $tag->id }}">
                                                    <input class="form-check-input" type="radio" 
                                                        name="tag_group_{{ $group->id }}" 
                                                        id="tag_{{ $tag->id }}"
                                                        wire:model.live="selectedTagValueByGroup.{{ $group->id }}" 
                                                        value="{{ $tag->id }}">
                                                    <label class="form-check-label small" for="tag_{{ $tag->id }}">
                                                        {{ $tag->name }}
                                                    </label>
                                                </div>
                                            @endforeach

                                            @if(count($group->tags) === 0)
                                                <div class="text-muted small text-center py-2">No tags found.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted mt-5">
                       <p>No tag groups configured.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
