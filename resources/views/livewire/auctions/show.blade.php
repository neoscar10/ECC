<div class="auction-detail-page">
    @push('styles')
    <style>
        .auction-detail-page {
            --auction-panel-bg: rgba(10, 18, 38, 0.88);
            --auction-panel-border: rgba(212, 175, 55, 0.14);
        }

        .auction-breadcrumb {
            color: var(--luxe-text-soft);
            font-size: .9rem;
        }

        .auction-breadcrumb a {
            color: var(--luxe-text-soft);
            text-decoration: none;
            transition: .2s ease;
        }

        .auction-breadcrumb a:hover {
            color: var(--luxe-gold);
        }

        .auction-breadcrumb-sep {
            opacity: .55;
            margin-inline: .4rem;
        }

        .auction-detail-title {
            font-size: clamp(2rem, 4vw, 3.5rem);
            line-height: 1.02;
            font-weight: 900;
            color: #fff;
            letter-spacing: -.04em;
            margin-bottom: .9rem;
        }

        .auction-meta-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .75rem 1rem;
        }

        .auction-lot-pill {
            display: inline-flex;
            align-items: center;
            padding: .45rem .85rem;
            border-radius: 999px;
            background: rgba(212,175,55,.14);
            color: var(--luxe-gold);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .auction-cert-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: var(--luxe-text-soft);
            font-size: .92rem;
            font-weight: 600;
        }

        .auction-gallery-card,
        .auction-info-card,
        .auction-bid-card,
        .auction-history-card,
        .auction-seller-card,
        .auction-related-card {
            border-radius: 24px;
            border: 1px solid rgba(212,175,55,.12);
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                var(--luxe-surface);
            box-shadow: 0 18px 40px rgba(0,0,0,.28);
        }

        .auction-gallery-stage {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            background: #0f0d09;
            border: 1px solid rgba(212,175,55,.1);
            aspect-ratio: 4 / 3;
        }

        .auction-gallery-stage img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .auction-stage-overlay-actions {
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            display: flex;
            gap: .75rem;
            z-index: 2;
        }

        .auction-stage-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .auction-stage-arrow.is-left {
            left: 1rem;
        }

        .auction-stage-arrow.is-right {
            right: 1rem;
        }

        .auction-stage-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.45);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            transition: .2s ease;
        }

        .auction-stage-btn:hover {
            background: var(--luxe-gold);
            color: #111;
            border-color: var(--luxe-gold);
        }

        .auction-thumb-strip {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: .25rem;
            scrollbar-width: thin;
        }

        .auction-thumb-btn {
            width: 112px;
            min-width: 112px;
            aspect-ratio: 1 / 1;
            border-radius: 18px;
            overflow: hidden;
            background: #14110b;
            border: 1px solid rgba(212,175,55,.14);
            padding: 0;
            transition: .2s ease;
        }

        .auction-thumb-btn.active,
        .auction-thumb-btn:hover {
            border-color: var(--luxe-gold);
            box-shadow: 0 0 0 2px rgba(212,175,55,.18);
        }

        .auction-thumb-btn img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: .72;
            transition: .2s ease;
        }

        .auction-thumb-btn.active img,
        .auction-thumb-btn:hover img {
            opacity: 1;
        }

        .auction-sticky-col {
            position: sticky;
            top: 98px;
        }

        .auction-bid-head {
            background: rgba(255,255,255,.03);
            border-bottom: 1px solid rgba(212,175,55,.12);
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }

        .auction-kicker {
            color: var(--luxe-gold);
            text-transform: uppercase;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .12em;
        }

        .auction-highest-bid {
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1;
            font-weight: 900;
            color: #fff;
            letter-spacing: -.04em;
        }

        .auction-live-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .35rem .6rem;
            border-radius: 999px;
            background: rgba(34,197,94,.12);
            color: #4ade80;
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .auction-live-chip-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
        }

        .auction-bid-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            color: var(--luxe-text-soft);
            font-size: .92rem;
            font-weight: 600;
        }

        .auction-bid-meta strong {
            color: #fff;
        }

        .auction-user-bid-box,
        .auction-inline-box {
            border-radius: 18px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
        }

        .auction-section-label {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: .88rem;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .auction-quick-bids {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .auction-quick-bid-btn {
            height: 42px;
            border-radius: 14px;
            border: 1px solid rgba(212,175,55,.28);
            background: transparent;
            color: #fff;
            font-size: .88rem;
            font-weight: 800;
            transition: .2s ease;
        }

        .auction-quick-bid-btn:hover,
        .auction-quick-bid-btn.active {
            background: rgba(212,175,55,.12);
            border-color: var(--luxe-gold);
            color: var(--luxe-gold);
        }

        .auction-bid-input-wrap {
            position: relative;
        }

        .auction-bid-input-wrap .form-control {
            height: 72px;
            border-radius: 18px;
            background: rgba(255,255,255,.04);
            border: 2px solid rgba(212,175,55,.28);
            color: #fff;
            font-size: 1.55rem;
            font-weight: 900;
            padding-inline: 1.15rem 4.5rem;
            box-shadow: none;
        }

        .auction-bid-input-wrap .form-control::placeholder {
            color: rgba(255,255,255,.42);
        }

        .auction-bid-input-wrap .form-control:focus {
            border-color: var(--luxe-gold);
            box-shadow: 0 0 0 .2rem rgba(212,175,55,.08);
            background: rgba(255,255,255,.05);
            color: #fff;
        }

        .auction-bid-currency {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: var(--luxe-text-soft);
            font-size: .92rem;
            font-weight: 800;
        }

        .auction-place-bid-btn {
            width: 100%;
            min-height: 60px;
            border-radius: 18px;
            border: 0;
            background: var(--luxe-gold);
            color: #111;
            font-size: .98rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            box-shadow: 0 14px 28px rgba(212,175,55,.18);
            transition: .2s ease;
        }

        .auction-place-bid-btn:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
        }

        .auction-place-bid-btn:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.3);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
            filter: none;
        }

        .auction-micro-copy {
            font-size: .68rem;
            color: var(--luxe-muted);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .auction-history-head {
            border-bottom: 1px solid rgba(212,175,55,.1);
            background: rgba(255,255,255,.03);
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }

        .auction-bid-row + .auction-bid-row {
            border-top: 1px solid rgba(212,175,55,.06);
        }

        .auction-bid-row-index {
            min-width: 40px;
            font-size: .8rem;
            font-weight: 900;
            color: var(--luxe-gold);
        }

        .auction-bid-row-muted {
            opacity: .68;
        }

        .auction-description-card h3,
        .auction-related-title {
            color: #fff;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .auction-description-card .auction-desc-divider {
            height: 1px;
            background: rgba(212,175,55,.16);
            margin: 1rem 0 1.25rem;
        }

        .auction-rich-text,
        .auction-rich-text p,
        .auction-rich-text li,
        .auction-rich-text span {
            color: var(--luxe-text-soft);
            line-height: 1.9;
        }

        .auction-feature-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem 1.25rem;
            margin-top: 1.5rem;
        }

        .auction-feature-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #fff;
            min-width: 0;
        }

        .auction-feature-item i {
            color: var(--luxe-gold);
            flex: 0 0 auto;
        }

        .auction-house-badge {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #15120c;
            color: var(--luxe-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            letter-spacing: .04em;
            border: 1px solid rgba(212,175,55,.18);
        }

        .auction-related-nav-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.04);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        .auction-related-nav-btn:hover {
            border-color: var(--luxe-gold);
            color: var(--luxe-gold);
        }

        .auction-related-item {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(212,175,55,.12);
            background: rgba(255,255,255,.03);
            box-shadow: 0 14px 28px rgba(0,0,0,.22);
            transition: .2s ease;
            height: 100%;
        }

        .auction-related-item:hover {
            transform: translateY(-4px);
            border-color: rgba(212,175,55,.24);
        }

        .auction-related-media {
            position: relative;
            aspect-ratio: 1 / 1;
            background: #0f0d09;
        }

        .auction-related-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .auction-related-lot-badge {
            position: absolute;
            top: .6rem;
            left: .6rem;
            padding: .28rem .5rem;
            border-radius: 999px;
            background: rgba(0,0,0,.55);
            color: #fff;
            font-size: .6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        @media (max-width: 991.98px) {
            .auction-sticky-col {
                position: static;
            }

            .auction-feature-list {
                grid-template-columns: 1fr;
            }

            .auction-quick-bids {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @endpush

    @php
        $auctionTitle = $lotPrepared->title ?? 'Untitled Lot';
        $lotNumber = $lotPrepared->lot_number ?? null;
        $highestBid = $lotPrepared->current_bid_display ?? '₹0';
        $timeLeft = $lotPrepared->time_remaining_display ?? '--';
        $biddersCount = (isset($lotPrepared->bid_history) && is_array($lotPrepared->bid_history)) ? count(array_unique(array_column($lotPrepared->bid_history, 'user_id'))) : null;
        $isLive = ($lotPrepared->status_label ?? '') === 'Live Auction';
        $currency = '₹';

        // Extract gallery items correctly mapping the available images relation sent by the controller
        $mainImage = $lotPrepared->hero_image_url ?? 'https://placehold.co/1200x900/17130b/d4af37?text=No+Image';
        
        $galleryImages = $lotPrepared->gallery_images ?? [];
        $galleryItems = count($galleryImages) > 0 ? collect($galleryImages) : collect([$mainImage]);

        $bidHistory = collect($lotPrepared->bid_history ?? []);
        $relatedLots = collect(); // If related lots are added in the future

        $userCurrentBid = null; // No direct mapping found, leaving it visually handled by input later
        
        $metaBadges = $lotPrepared->provenance_badges ?? [];
        $lotAttachments = $lotPrepared->attachments ?? [];
        $increments = $lotPrepared->suggested_increments ?? [];
        
        // Use existing state to map lock logic
        $canAutoBid = $canAutoBid ?? false;
        $hasAutoBidConfigured = $hasAutoBidConfigured ?? false;
    @endphp

    {{-- BREADCRUMB --}}
    <nav class="auction-breadcrumb mb-4 mb-lg-5" aria-label="breadcrumb">
        <div class="d-flex flex-wrap align-items-center">
            <a href="{{ url('/') }}">Home</a>
            <span class="auction-breadcrumb-sep">
                <i class="mdi mdi-chevron-right"></i>
            </span>
            
            <a href="{{ route('auctions.index') }}">Auctions</a>
            <span class="auction-breadcrumb-sep">
                <i class="mdi mdi-chevron-right"></i>
            </span>

            <span class="text-white">{{ $auctionTitle }}</span>
        </div>
    </nav>

    <div class="row g-4 g-xl-5">
        {{-- LEFT MAIN COLUMN --}}
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

        {{-- RIGHT SIDEBAR --}}
        <div class="col-lg-4">
            <div class="auction-sticky-col">
                {{-- MAIN BIDDING CARD --}}
                <div class="auction-bid-card overflow-hidden mb-4">
                    <div class="auction-bid-head p-4 p-lg-5">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                            <div>
                                <div class="auction-kicker mb-2">Current Bid</div>
                                <div class="auction-highest-bid">{{ $highestBid }}</div>
                            </div>

                            @if($isLive)
                                <div class="auction-live-chip">
                                    <span class="auction-live-chip-dot"></span>
                                    <span>Live</span>
                                </div>
                            @endif
                        </div>

                        <div class="auction-bid-meta">
                            <div class="d-inline-flex align-items-center gap-2">
                                <i class="mdi mdi-timer-outline"></i>
                                <span><strong id="ecc-countdown-display" data-ends-at="{{ !empty($lotPrepared->ends_at_iso) ? $lotPrepared->ends_at_iso : '' }}">{{ $lotPrepared->time_remaining_display ?? '' }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 p-lg-5">
                        {{-- AUTO BID --}}
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div class="auction-section-label">
                                    <i class="mdi mdi-robot-outline" style="color: var(--luxe-gold);"></i>
                                    <span>Auto-Bid Settings</span>
                                </div>
                                <div class="form-check form-switch m-0">
                                    <div style="font-size: .8rem; font-weight: 800; color: {{ $hasAutoBidConfigured ? 'var(--luxe-gold)' : 'var(--luxe-text-soft)'}};">
                                        {{ $hasAutoBidConfigured ? 'ON' : 'OFF' }}
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="auction-place-bid-btn w-100" style="min-height: 48px; font-size: 0.82rem; background: rgba(212,175,55,.12); border: 1px solid rgba(212,175,55,.2); color: var(--luxe-gold); box-shadow: none;" wire:click="openAutoBidModal" @if(empty($canAutoBid)) disabled @endif>
                                <i class="mdi mdi-flash me-2"></i>
                                {{ $hasAutoBidConfigured ? 'Update Auto Bid' : 'Set Auto Bid Limit' }}
                            </button>
                        </div>

                        {{-- PLACE BID --}}
                        <div>
                            <div class="auction-section-label mb-3">
                                <i class="mdi mdi-cash-fast" style="color: var(--luxe-gold);"></i>
                                <span>Place New Bid</span>
                            </div>

                            {{-- QUICK INCREMENT BUTTONS --}}
                            @if(!empty($increments) && count($increments))
                                <div class="auction-quick-bids mb-4">
                                    @foreach($increments as $increment)
                                        @php
                                            $label = is_array($increment) ? ($increment['label'] ?? '') : ($increment->label ?? '');
                                            $isRecommended = (bool) (is_array($increment) ? ($increment['recommended'] ?? false) : ($increment->recommended ?? false));
                                        @endphp
                                        <button
                                            type="button"
                                            class="auction-quick-bid-btn {{ $isRecommended ? 'active' : '' }}"
                                            @if(!empty($label)) wire:click="applyIncrement('{{ $label }}')" @endif
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            {{-- BID FORM --}}
                            <div class="auction-bid-input-wrap mb-3">
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="0"
                                    wire:model.defer="bidAmount"
                                >
                                <span class="auction-bid-currency">{{ $currency }}</span>
                            </div>
                            
                            @error('bidAmount')
                                <div class="text-danger small mt-2 fw-medium mb-3 px-2">{{ $message }}</div>
                            @enderror

                            <button type="button" class="auction-place-bid-btn mb-3" wire:click="reviewBid" @if(empty($lotPrepared->can_bid)) disabled @endif>
                                <i class="mdi mdi-gavel me-2"></i>
                                {{ empty($lotPrepared->can_bid) ? 'Bidding Closed' : 'Review Bid' }}
                            </button>

                            <div class="auction-micro-copy text-center">
                                Highest bidder must pay within 24h.
                                <a href="" class="text-decoration-underline" style="color: var(--luxe-gold);">Terms apply</a>.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BID HISTORY --}}
                <div class="auction-history-card overflow-hidden mb-4" x-data="{ showAllBids: false }">
                    <div class="auction-history-head px-4 px-lg-5 py-4 d-flex align-items-center justify-content-between gap-3">
                        <div class="fw-black text-uppercase text-white" style="letter-spacing: .08em; font-size: .84rem;">Bid History</div>
                        <div class="small fw-bold text-uppercase" style="color: var(--luxe-muted); letter-spacing: .06em;">
                            {{ $bidHistory->count() }} Total Bids
                        </div>
                    </div>

                    <div>
                        @forelse($bidHistory as $index => $entry)
                            @php
                                $bidderLabel = $entry['bidder_label'] ?? $entry->bidder_label ?? 'User';
                                $amount = $entry['amount_display'] ?? $entry->amount_display ?? (isset($entry['amount']) ? '₹' . number_format((float) $entry['amount']) : '₹0');
                                $timeAgo = $entry['time_human'] ?? $entry->time_human ?? '--';
                                $isHighest = $entry['is_highest_bid'] ?? false;
                            @endphp

                            @if($index === 6)
                                <div x-show="showAllBids" style="display: none;">
                            @endif

                            <div class="auction-bid-row px-4 px-lg-5 py-3 d-flex align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <div class="auction-bid-row-index {{ $isHighest ? 'text-success' : '' }}">#{{ $bidHistory->count() - $index }}</div>
                                    <div class="text-truncate {{ $index > 0 ? 'auction-bid-row-muted' : '' }}">
                                        {{ $bidderLabel }}
                                    </div>
                                </div>

                                <div class="text-end {{ $index > 0 ? 'auction-bid-row-muted' : '' }}">
                                    <div class="fw-bold {{ $isHighest ? 'text-success' : 'text-white' }}">{{ $amount }}</div>
                                    <div class="small" style="color: var(--luxe-muted);">{{ $timeAgo }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 px-lg-5" style="color: var(--luxe-text-soft);">
                                No bids yet.
                            </div>
                        @endforelse

                        @if($bidHistory->count() > 6)
                            </div>
                        @endif
                    </div>

                    @if($bidHistory->count() > 6)
                        <div class="px-4 px-lg-5 py-3 text-center" style="background: rgba(255,255,255,.03); border-top: 1px solid rgba(212,175,55,.06);">
                            <a href="javascript:void(0)" @click.prevent="showAllBids = !showAllBids" class="btn btn-link p-0 text-decoration-none fw-black text-uppercase" style="letter-spacing: .08em; color: var(--luxe-gold); font-size: .72rem;">
                                <span x-text="showAllBids ? 'Show Less' : 'See All Bids'"></span>
                                <i class="mdi ms-1" :class="showAllBids ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
</div>

    <!-- Bid Confirmation Modal -->
    <div
        class="modal fade @if($showBidConfirmModal) show @endif"
        tabindex="-1"
        @if($showBidConfirmModal)
            style="display:block; background: rgba(0,0,0,0.85);"
            aria-modal="true"
            role="dialog"
        @endif
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light" style="background: var(--luxe-surface); border: 1px solid rgba(212,175,55,.14); border-radius: 20px; box-shadow: 0 24px 60px rgba(0,0,0,.6);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--luxe-gold);">Confirm Bid</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeBidConfirmModal"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-white mb-4">
                        Please confirm your bid before submitting.
                    </p>

                    <div class="p-3 mb-3" style="background: rgba(212,175,55,.04); border: 1px solid rgba(212,175,55,.2); border-radius: 16px;">
                        <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Current Highest Bid</div>
                        <div class="fs-4 fw-bold text-white">{{ $currentHighestBidDisplay }}</div>
                    </div>

                    <div class="p-3 bg-black" style="background: #000; border: 1px solid rgba(255,255,255,.12); border-radius: 16px;">
                        <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Your Bid</div>
                        <div class="fs-3 fw-black" style="color: var(--luxe-gold);">{{ $userBidDisplay }}</div>
                    </div>

                    <div class="small text-white mt-4 text-center opacity-75">
                        <i class="mdi mdi-information-outline me-1"></i> Bids are binding once submitted.
                    </div>

                    @error('bidAmount')
                        <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220,53,69,.1); border-color: rgba(220,53,69,.2); color: #ff6b6b; border-radius: 12px;">
                            <i class="mdi mdi-alert-circle-outline me-2"></i> {{ $message }}
                        </div>
                    @enderror

                    @if(!empty($bidErrorMessage))
                        <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220,53,69,.1); border-color: rgba(220,53,69,.2); color: #ff6b6b; border-radius: 12px;">
                            <i class="mdi mdi-alert-circle-outline me-2"></i> {{ $bidErrorMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 p-4 pt-0 gap-2 flex-nowrap">
                    <button type="button" class="btn w-50" style="border-radius: 14px; background: rgba(255,255,255,.05); color: #fff; font-weight: 700; min-height: 52px;" wire:click="closeBidConfirmModal">
                        Cancel
                    </button>
                    <button type="button" class="btn w-50" style="border-radius: 14px; background: var(--luxe-gold); color: #111; font-weight: 800; min-height: 52px;" wire:click="confirmBidSubmission" wire:loading.attr="disabled" wire:target="confirmBidSubmission">
                        <span wire:loading.remove wire:target="confirmBidSubmission">Place Bid</span>
                        <span wire:loading wire:target="confirmBidSubmission">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="border-color: #111; border-right-color: transparent;"></span>
                            Placing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto Bid Configuration Modal -->
    <div
        class="modal fade @if($showAutoBidModal) show @endif"
        tabindex="-1"
        @if($showAutoBidModal)
            style="display: block; background: rgba(0,0,0,0.85);"
            aria-modal="true"
            role="dialog"
        @endif
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light" style="background: var(--luxe-surface); border: 1px solid rgba(212,175,55,.14); border-radius: 20px; box-shadow: 0 24px 60px rgba(0,0,0,.6);">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: var(--luxe-gold);">
                        {{ $hasAutoBidConfigured ? 'Update Auto Bid' : 'Configure Auto Bid' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeAutoBidModal"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-white mb-4">
                        Set your maximum bid limit and increment amount.
                    </p>

                    <div class="p-3 mb-3" style="background: rgba(212,175,55,.03); border: 1px solid rgba(212,175,55,.1); border-radius: 16px;">
                        <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Current Highest Bid</div>
                        <div class="fs-4 fw-bold text-white">{{ $currentHighestBidDisplay }}</div>
                    </div>

                    <div class="p-3 mb-4" style="background: rgba(212,175,55,.03); border: 1px solid rgba(212,175,55,.1); border-radius: 16px;">
                        <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Minimum Increment</div>
                        <div class="fs-4 fw-bold text-white">{{ $minIncrementDisplay }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Increment Amount (₹)</label>
                        <input type="text" class="form-control" style="background: #000; border: 1px solid rgba(255,255,255,.15); color: #fff; padding: .8rem 1rem; border-radius: 12px;" wire:model.defer="autoBidIncrementAmount" placeholder="e.g. 10000">
                        @error('autoBidIncrementAmount') <div class="text-danger small mt-2"><i class="mdi mdi-alert-circle-outline"></i> {{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white small fw-bold">Maximum Bid Limit (₹)</label>
                        <input type="text" class="form-control" style="background: #000; border: 1px solid var(--luxe-gold); color: #fff; padding: .8rem 1rem; border-radius: 12px; box-shadow: 0 0 0 1px rgba(212,175,55,.2);" wire:model.defer="autoBidMaxAmount" placeholder="e.g. 500000">
                        @error('autoBidMaxAmount') <div class="text-danger small mt-2"><i class="mdi mdi-alert-circle-outline"></i> {{ $message }}</div> @enderror
                    </div>

                    @if(!empty($autoBidErrorMessage))
                        <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220,53,69,.1); border-color: rgba(220,53,69,.2); color: #ff6b6b; border-radius: 12px;">
                            {{ $autoBidErrorMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between align-items-center">
                    <div>
                        @if($hasAutoBidConfigured)
                            <button type="button" class="btn btn-link text-danger text-decoration-none px-0 fw-bold" wire:click="confirmCancelAutoBidModal">
                                Cancel Auto Bid
                            </button>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn px-4" style="border-radius: 12px; background: rgba(255,255,255,.05); color: #fff; font-weight: 700;" wire:click="closeAutoBidModal">
                            Close
                        </button>
                        <button type="button" class="btn px-4" style="border-radius: 12px; background: var(--luxe-gold); color: #111; font-weight: 800;" wire:click="saveAutoBid" wire:loading.attr="disabled" wire:target="saveAutoBid">
                            <span wire:loading.remove wire:target="saveAutoBid">Save Auto Bid</span>
                            <span wire:loading wire:target="saveAutoBid">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="border-color: #111; border-right-color: transparent;"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Auto Bid Confirm Modal -->
    <div
        class="modal fade @if($showCancelAutoBidModal) show @endif"
        tabindex="-1"
        @if($showCancelAutoBidModal)
            style="display: block; background: rgba(0,0,0,0.85);"
            aria-modal="true"
            role="dialog"
        @endif
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-light border-0 shadow-lg" style="background: var(--luxe-surface); border-radius: 20px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger">Cancel Auto Bid</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeCancelAutoBidModal"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-white mb-0" style="font-size: 1.05rem;">
                        Are you sure you want to cancel your auto bid for this lot? This action cannot be undone.
                    </p>
                    
                    @if(!empty($autoBidErrorMessage))
                        <div class="alert alert-danger mt-4 mb-0" style="border-radius: 12px;">
                            {{ $autoBidErrorMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 p-4 pt-0 gap-2 flex-nowrap">
                    <button type="button" class="btn w-50" style="border-radius: 14px; background: rgba(255,255,255,.05); color: #fff; font-weight: 700; min-height: 52px;" wire:click="closeCancelAutoBidModal">
                        Keep Auto Bid
                    </button>
                    <button type="button" class="btn w-50 btn-danger text-white fw-bold" style="border-radius: 14px; min-height: 52px;" wire:click="cancelAutoBid" wire:loading.attr="disabled" wire:target="cancelAutoBid">
                        <span wire:loading.remove wire:target="cancelAutoBid">Cancel Auto Bid</span>
                        <span wire:loading wire:target="cancelAutoBid">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Cancelling...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            setInterval(() => {
                const display = document.getElementById('ecc-countdown-display');
                if (!display) return;

                const endsAtRaw = display.getAttribute('data-ends-at');
                if (!endsAtRaw) return;
                
                const now = new Date().getTime();
                const end = new Date(endsAtRaw).getTime();
                const distance = end - now;

                if (distance < 0) {
                    if (display.innerText !== 'Ended') display.innerText = 'Ended';
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                let text = '';
                if (days > 0) text = `${days}d ${hours}h`;
                else if (hours > 0) text = `${hours}h ${minutes}m`;
                else text = `${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
                
                if (display.innerText !== text) display.innerText = text;
            }, 1000);
        });
    </script>
    <script>
        (function () {
            function initAuctionDetailGallery() {
                const mainImage = document.getElementById('auctionMainImage');
                const thumbButtons = Array.from(document.querySelectorAll('.auction-thumb-btn'));
                const prevBtn = document.getElementById('auctionGalleryPrev');
                const nextBtn = document.getElementById('auctionGalleryNext');

                if (!mainImage || !thumbButtons.length) return;

                let currentIndex = thumbButtons.findIndex(btn => btn.classList.contains('active'));
                if (currentIndex < 0) currentIndex = 0;

                function activate(index) {
                    const safeIndex = (index + thumbButtons.length) % thumbButtons.length;
                    currentIndex = safeIndex;

                    thumbButtons.forEach((btn, idx) => {
                        btn.classList.toggle('active', idx === safeIndex);
                    });

                    const src = thumbButtons[safeIndex].getAttribute('data-full-src');
                    if (src) mainImage.setAttribute('src', src);
                }

                thumbButtons.forEach((btn, idx) => {
                    btn.addEventListener('click', function () {
                        activate(idx);
                    });
                });

                if (prevBtn) {
                    prevBtn.addEventListener('click', function () {
                        activate(currentIndex - 1);
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', function () {
                        activate(currentIndex + 1);
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', initAuctionDetailGallery);
            document.addEventListener('livewire:navigated', initAuctionDetailGallery);
            
            // Re-init on Livewire update in case images change
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('morph.updated', ({ el, component }) => {
                    initAuctionDetailGallery();
                });
            }
        })();
    </script>
    @endpush
</div>
