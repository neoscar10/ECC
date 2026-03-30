<div class="col-lg-8">
    <div class="mb-4 mb-lg-5">
        <h1 class="auction-detail-title">{{ $auctionTitle }}</h1>

        <div class="auction-meta-row">
            @if($lotNumber)
                <span class="auction-lot-pill">Lot #{{ $lotNumber }}</span>
            @endif

            @if(!empty($lotPrepared->subtitle))
                <span class="auction-cert-badge">
                    <i class="mdi mdi-fountain-pen-tip"></i>
                    <span>{{ $lotPrepared->subtitle }}</span>
                </span>
            @endif

            @if(!empty($lotPrepared->status_label ?? null))
                <span class="auction-cert-badge text-white">
                    <span>{{ $lotPrepared->status_label }}</span>
                </span>
            @endif

            @if(!empty($lotPrepared->rarity_label ?? null))
                 <span class="auction-cert-badge text-warning">
                    <i class="mdi mdi-star text-warning"></i>
                    <span>{{ $lotPrepared->rarity_label }}</span>
                </span>
            @endif
            
            @if(!empty($metaBadges))
                @foreach($metaBadges as $badge)
                     <span class="auction-cert-badge">
                        <i class="mdi {{ $badge['icon'] ?? 'mdi-check-decagram-outline' }}"></i>
                        <span>{{ $badge['label'] ?? '' }}</span>
                    </span>
                @endforeach
            @endif
        </div>
    </div>

    {{-- GALLERY --}}
    <div class="auction-gallery-card p-3 p-lg-4 mb-4">
        <div class="auction-gallery-stage mb-3">
            <img
                src="{{ $mainImage }}"
                alt="{{ $auctionTitle }}"
                id="auctionMainImage"
            >

            @if($galleryItems->count() > 1)
                <div class="auction-stage-arrow is-left">
                    <button type="button" class="auction-stage-btn" id="auctionGalleryPrev" aria-label="Previous image">
                        <i class="mdi mdi-chevron-left fs-4"></i>
                    </button>
                </div>

                <div class="auction-stage-arrow is-right">
                    <button type="button" class="auction-stage-btn" id="auctionGalleryNext" aria-label="Next image">
                        <i class="mdi mdi-chevron-right fs-4"></i>
                    </button>
                </div>
            @endif

            <div class="auction-stage-overlay-actions">
                @if(!empty($mainImage))
                    <button type="button" class="auction-stage-btn" id="auctionZoomBtn" aria-label="Zoom image">
                        <i class="mdi mdi-magnify-plus-outline"></i>
                    </button>
                @endif
            </div>
        </div>

        @if($galleryItems->count() > 1)
            <div class="auction-thumb-strip" id="auctionThumbStrip">
                @foreach($galleryItems as $index => $media)
                    @php
                        $thumbUrl = $media;
                        $fullUrl = $media;
                    @endphp

                    <button
                        type="button"
                        class="auction-thumb-btn {{ $index === 0 ? 'active' : '' }}"
                        data-index="{{ $index }}"
                        data-full-src="{{ $fullUrl }}"
                        aria-label="View image {{ $index + 1 }}"
                    >
                        <img src="{{ $thumbUrl ?: $fullUrl }}" alt="{{ $auctionTitle }} thumbnail {{ $index + 1 }}">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- DESCRIPTION / LOT DETAILS --}}
    <div class="auction-info-card auction-description-card p-4 p-lg-5 mb-4">
        <h3 class="mb-0">Description</h3>
        <div class="auction-desc-divider"></div>

        <div class="auction-rich-text">
            @if(!empty($lotPrepared->description ?? null))
                {!! nl2br(e($lotPrepared->description)) !!}
            @else
                <p>No description available.</p>
            @endif
        </div>
    </div>
    
    {{-- ATTACHMENTS SECTION --}}
    @if(!empty($lotAttachments) && count($lotAttachments))
     <div class="mt-5">
         <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
             <h3 class="auction-related-title fs-2 mb-0">Doc & Certs</h3>
         </div>
         
         <div class="row g-4">
             @foreach($lotAttachments as $attachment)
                 @php
                     $attachmentUrl = is_array($attachment) ? ($attachment['url'] ?? '#') : ($attachment->url ?? '#');
                     $attachmentName = is_array($attachment) ? ($attachment['name'] ?? 'Attachment') : ($attachment->name ?? 'Attachment');
                     $attachmentSize = is_array($attachment) ? ($attachment['size_label'] ?? null) : ($attachment->size_label ?? null);
                     $attachmentThumb = is_array($attachment) ? ($attachment['thumbnail_url'] ?? $attachment['preview_url'] ?? null) : ($attachment->thumbnail_url ?? $attachment->preview_url ?? null);
                     $attachmentType = is_array($attachment) ? ($attachment['type'] ?? null) : ($attachment->type ?? null);
                     $isImage = (bool) (is_array($attachment) ? ($attachment['is_image'] ?? false) : ($attachment->is_image ?? false));
                 @endphp

                 <div class="col-12 col-md-6">
                     <a href="{{ $attachmentUrl }}" target="_blank" class="text-decoration-none">
                        <div class="auction-related-item d-flex align-items-center p-3 gap-3 h-100">
                             <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.2); color: var(--luxe-gold);">
                                  <i class="mdi mdi-file-document-outline fs-4"></i>
                             </div>
                             <div class="min-w-0">
                                 <div class="fw-bold text-white mb-1 text-truncate">{{ $attachmentName }}</div>
                                 @if($attachmentSize || $attachmentType)
                                     <div class="small" style="color: var(--luxe-text-soft);">
                                         {{ $attachmentType ? strtoupper($attachmentType) : 'FILE' }}{{ ($attachmentType && $attachmentSize) ? ' • ' : '' }}{{ $attachmentSize ?? '' }}
                                     </div>
                                 @endif
                             </div>
                        </div>
                     </a>
                 </div>
             @endforeach
         </div>
     </div>
     @endif

</div>
