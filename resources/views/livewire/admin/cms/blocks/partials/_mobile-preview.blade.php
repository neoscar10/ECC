<div class="mobile-preview-device border rounded-4 bg-white shadow-sm overflow-hidden" style="width: 320px; height: 600px; margin: 0 auto; position: sticky; top: 20px;">
    <!-- Status Bar -->
    <div class="status-bar bg-dark text-white px-3 py-1 d-flex justify-content-between align-items-center fs-10">
        <span>9:41</span>
        <div class="d-flex gap-1">
            <i class="ri-signal-wifi-fill"></i>
            <i class="ri-battery-fill"></i>
        </div>
    </div>

    <!-- App Header -->
    <div class="app-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <i class="ri-menu-2-line"></i>
        <span class="fw-bold">Executive Cricket Club</span>
        <i class="ri-notification-3-line"></i>
    </div>

    <!-- Content Area -->
    <div class="preview-content p-3 bg-light h-100 overflow-auto">
        <!-- Placement Context Label -->
        <small class="text-uppercase text-muted fs-10 mb-2 d-block text-center">{{ $placement ?? 'Placement' }}</small>

        @if($type === 'banner')
            <!-- Banner Preview -->
            <div class="card border-0 shadow-sm overflow-hidden mb-3">
                <div class="position-relative" style="height: 160px; background-color: #f0f0f0;">
                    @if($contentImage || $existingContentImage)
                        <img src="{{ $contentImage ? $contentImage->temporaryUrl() : $existingContentImage }}" class="w-100 h-100 object-fit-cover">
                    @endif
                    
                    @if(($textPosition ?? 'below') === 'above')
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.3;"></div>
                        <div class="position-absolute bottom-0 start-0 p-3 text-white">
                            @if($contentBadge) <span class="badge bg-primary mb-1">{{ $contentBadge }}</span> @endif
                            <h6 class="fw-bold mb-0 text-white">{{ $contentTitle ?: 'Banner Title' }}</h6>
                            @if($contentSubtitle) <small class="d-block text-white-50">{{ $contentSubtitle }}</small> @endif
                        </div>
                    @endif
                </div>
                
                @if(($textPosition ?? 'below') === 'below')
                    <div class="p-3 bg-white">
                        @if($contentBadge) <span class="badge bg-primary mb-1">{{ $contentBadge }}</span> @endif
                        <h6 class="fw-bold mb-0 text-dark">{{ $contentTitle ?: 'Banner Title' }}</h6>
                        @if($contentSubtitle) <small class="d-block text-muted">{{ $contentSubtitle }}</small> @endif
                    </div>
                @endif
                @if($hasDetailPage)
                    <div class="p-2 bg-white">
                        <button class="btn btn-sm btn-dark w-100">{{ $contentCtaText ?: 'View Details' }}</button>
                    </div>
                @endif
            </div>

        @elseif($type === 'card')
              <!-- Card Preview -->
              <div class="card border-0 shadow-sm mb-3 position-relative overflow-hidden">
                 @if($contentImage || $existingContentImage)
                     <div style="height: 140px; background-color: #f0f0f0;" class="position-relative">
                          <img src="{{ $contentImage ? $contentImage->temporaryUrl() : $existingContentImage }}" class="w-100 h-100 object-fit-cover rounded-top">
                          
                          @if(($textPosition ?? 'below') === 'above')
                             <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="opacity: 0.3;"></div>
                             <div class="position-absolute bottom-0 start-0 p-3 text-white">
                                 @if($contentBadge) <span class="badge bg-light text-primary mb-1">{{ $contentBadge }}</span> @endif
                                 <h6 class="card-title fw-bold mb-0">{{ $contentTitle ?: 'Card Title' }}</h6>
                                 @if($contentSubtitle) <small class="d-block text-white-50">{{ $contentSubtitle }}</small> @endif
                             </div>
                          @endif
                     </div>
                 @endif

                 @if(($textPosition ?? 'below') === 'below')
                 <div class="card-body p-3">
                     @if($contentBadge) <span class="badge bg-light text-primary mb-2">{{ $contentBadge }}</span> @endif
                     <h6 class="card-title fw-bold mb-1">{{ $contentTitle ?: 'Card Title' }}</h6>
                     @if($contentSubtitle) <h6 class="card-subtitle text-muted fs-12 mb-2">{{ $contentSubtitle }}</h6> @endif
                     <p class="card-text fs-12 text-muted mb-3">{{ Str::limit($contentBody, 80) }}</p>
                     
                     @if($hasDetailPage)
                         <button class="btn btn-sm btn-outline-dark w-100">{{ $contentCtaText ?: 'Learn More' }}</button>
                     @endif
                 </div>
                 @elseif($hasDetailPage)
                     <div class="p-2">
                          <button class="btn btn-sm btn-outline-dark w-100">{{ $contentCtaText ?: 'Learn More' }}</button>
                     </div>
                 @endif
              </div>

        @elseif($type === 'slider')
            <!-- Slider Preview -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <h6 class="fw-bold mb-0 text-dark">{{ $contentTitle ?: 'Slider Section' }}</h6>
                    @if($contentCtaText) <small class="text-primary fw-medium">{{ $contentCtaText }}</small> @endif
                </div>
                
                @if($sliderMode === 'images')
                     <!-- Image Slider Preview -->
                    <div class="d-flex gap-2 overflow-hidden" style="margin-right: -16px;">
                        @forelse($sliderImages as $slide)
                            <div class="flex-shrink-0 rounded-3 overflow-hidden position-relative" style="width: 240px; height: 140px; background: #eee;">
                                @if(isset($slide['image_path']))
                                     <img src="{{ $slide['image_url'] ?? asset('storage/'.$slide['image_path']) }}" class="w-100 h-100 object-fit-cover">
                                @elseif(isset($slide['file']))
                                     {{-- Temp file preview not easy here without dedicated property. Assuming we handle upload preview differently --}}
                                     <div class="d-flex align-items-center justify-content-center h-100 text-muted">Image</div>
                                @endif
                                <div class="position-absolute bottom-0 start-0 p-2 w-100 bg-gradient-to-t from-black-50">
                                    <h6 class="text-white fs-12 mb-0 truncate">{{ $slide['title'] ?? '' }}</h6>
                                </div>
                            </div>
                        @empty
                             <div class="flex-shrink-0 rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 240px; height: 140px;">
                                <small class="text-muted">No Slides</small>
                            </div>
                            <div class="flex-shrink-0 rounded-3 bg-secondary-subtle d-flex align-items-center justify-content-center" style="width: 20px; height: 140px;"></div>
                        @endforelse
                    </div>

                @else
                    <!-- Item Slider Preview (Category/Manual) -->
                    <div class="d-flex gap-2 overflow-hidden" style="margin-right: -16px;">
                        @if($sliderMode === 'category' && isset($previewData['items']) && !empty($previewData['items']))
                            @foreach($previewData['items'] as $item)
                                <div class="card border-0 shadow-sm flex-shrink-0" style="width: 140px;">
                                    <div class="bg-light rounded-top position-relative" style="height: 100px;">
                                        @if(!empty($item['image']))
                                            <img src="{{ $item['image'] }}" class="w-100 h-100 object-fit-cover rounded-top">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center h-100 text-muted fs-10">No Image</div>
                                        @endif
                                    </div>
                                    <div class="card-body p-2">
                                        <h6 class="fs-12 fw-bold mb-1 text-truncate" title="{{ $item['title'] }}">{{ $item['title'] }}</h6>
                                        <p class="fs-10 text-muted mb-0">{{ $item['price'] ?? '' }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                             <!-- Empty State -->
                            <div class="d-flex flex-column align-items-center justify-content-center w-100 text-muted py-4">
                                <i class="ri-inbox-line fs-24 mb-2"></i>
                                <span class="fs-12">No items found</span>
                            </div>
                        @endif
                         <div class="flex-shrink-0" style="width: 20px;"></div>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Mock Content Below -->
         <div class="card border-0 shadow-sm mb-3 opacity-50">
            <div class="card-body h-100">
                <div class="placeholder-glow">
                     <span class="placeholder col-7"></span>
                     <span class="placeholder col-4"></span>
                     <span class="placeholder col-4"></span>
                     <span class="placeholder col-6"></span>
                     <span class="placeholder col-8"></span>
                </div>
            </div>
        </div>
    </div>
</div>
