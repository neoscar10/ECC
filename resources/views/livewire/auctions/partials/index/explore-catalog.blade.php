{{-- SECTION 2: EXPLORE COLLECTIONS --}}
<section>
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 gap-lg-4 mb-4 mb-lg-5 pt-3">
        <h2 class="luxe-subsection-title">
            <span class="luxe-subsection-title-bar"></span>
            <span>Explore Catalog</span>
        </h2>

        <div class="luxe-chip-row align-items-center">
            <span class="ecc-text-secondary small text-uppercase fw-bold me-2 tracking-widest">Filter by:</span>
            <button type="button"
                class="luxe-chip {{ ($activeTab ?? 'live') === 'live' ? 'active' : '' }}"
                wire:click="setTab('live')"
            >
                Live Bidding
            </button>

            <button type="button"
                class="luxe-chip {{ ($activeTab ?? 'live') === 'upcoming' ? 'active' : '' }}"
                wire:click="setTab('upcoming')"
            >
                Upcoming
            </button>

            <button type="button"
                class="luxe-chip {{ ($activeTab ?? 'live') === 'past' ? 'active' : '' }}"
                wire:click="setTab('past')"
            >
                Past Results
            </button>
        </div>
    </div>

    @if($listingItems->count() > 0)
        <div class="row g-4">
            @foreach($listingItems as $lot)
                @php
                    $canView = $lot['can_view'] ?? true;
                    $isBlurred = $lot['is_blurred'] ?? false;
                    $lockType = $lot['lock_type'] ?? 'lock';
                    $lockTitle = $lot['lock_title'] ?? 'Restricted View';
                    $lockHint = $lot['lock_hint'] ?? 'Membership Required';

                    $lotNo = $lot['lot_number'] ?? $lot['lot_no'] ?? null;
                    $title = $lot['title'] ?? 'Untitled Lot';
                    
                    // To keep grid text concise, we'll try to guess subtitle or keep empty
                    $subtitle = 'Auction Item';
                    $image = $lot['image_url'] ?? null;
                    $currentBid = $lot['current_bid'] ?? null;
                    $startingBid = $lot['starting_price'] ?? null;
                    $closesIn = $lot['closes_in_human'] ?? null;
                    $opensIn = $lot['opens_in_human'] ?? null;
                    $detailsUrl = $lot['details_url'] ?? '#';
                    $bidUrl = $lot['bid_url'] ?? $detailsUrl;

                    $formattedCurrentBid = isset($currentBid) ? '₹' . number_format((float) $currentBid) : '—';
                    $formattedStartingBid = isset($startingBid) ? '₹' . number_format((float) $startingBid) : '—';
                    
                    $isFeatured = $lot['is_star_lot'] ?? false;
                    $showHot = $lot['is_hot'] ?? $isFeatured;
                    $hasPremiumBadge = $lot['requires_premium_access'] ?? false;
                @endphp

                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="luxe-grid-card h-100 d-flex flex-column">
                        <div class="luxe-grid-media @if(!$canView || $isBlurred) cursor-pointer @endif" @if(!$canView || $isBlurred) wire:click.prevent="openAccessModal({{ $lot['id'] }})" @endif @if($canView && !$isBlurred && $detailsUrl !== '#') onclick="window.location.href='{{ $detailsUrl }}'" @endif>
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $title }}">
                            @else
                                <div class="d-flex w-100 h-100 align-items-center justify-content-center ecc-bg-surface text-muted">
                                    No image
                                </div>
                            @endif

                            @if($lotNo)
                                <div class="luxe-lot-badge" style="left: 12px; right: auto; bottom: auto; top: 12px; padding: .32rem .55rem; font-size: .62rem;">
                                    LOT #{{ $lotNo }}
                                </div>
                            @endif
                            
                            @if($lot['is_early_access_active'] ?? false)
                                <span class="luxe-live-pill" style="left: 12px; right: auto; bottom: 12px; top: auto; background: #e31837; font-size: 0.65rem; border: 1px solid var(--ecc-text-primary); color: var(--ecc-text-primary); backdrop-filter: blur(8px); text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em;">
                                    <i class="mdi mdi-clock-fast"></i> Early Access
                                </span>
                            @elseif($hasPremiumBadge)
                                <span class="luxe-live-pill" style="left: 12px; right: auto; bottom: 12px; top: auto; background: rgba(0,0,0,0.7); font-size: 0.65rem; border: 1px solid rgba(199, 167, 90,0.4); color: #f2b90d; backdrop-filter: blur(8px);">
                                    <i class="mdi mdi-diamond-stone"></i> {{ $lot['access_badge_label'] ?? 'Platinum Access' }}
                                </span>
                            @endif

                            <button type="button" class="luxe-fav-btn" aria-label="Favorite">
                                <i class="mdi mdi-heart-outline"></i>
                            </button>
                            
                            {{-- Blurred State Elements --}}
                            @if(!$canView || $isBlurred)
                                <div class="ecc-lock-overlay">
                                    <div class="ecc-lock-content">
                                        <div class="ecc-lock-icon-circle">
                                            <span class="material-symbols-outlined fs-2">
                                                @if($lockType === 'time-lock') lock_clock
                                                @elseif($lockType === 'diamond') diamond
                                                @else lock
                                                @endif
                                            </span>
                                        </div>
                                        <div class="ecc-lock-title text-uppercase">{{ $lockTitle }}</div>
                                        @if(!empty($lockHint))
                                            <p class="ecc-lock-hint">{{ $lockHint }}</p>
                                        @endif
                                        <button type="button" class="ecc-unlock-btn" wire:click.prevent="openAccessModal({{ $lot['id'] }})">
                                            Unlock View
                                        </button>
                                    </div>
                                </div>
                            @endif

                            @if($isBlurred || !$canView)
                                <div class="ecc-blur"></div>
                            @endif
                        </div>

                        <div class="luxe-grid-body d-flex flex-column flex-grow-1">
                            <h3 class="luxe-grid-title text-truncate">{{ $title }}</h3>
                            
                            <div class="luxe-grid-meta mt-auto">
                                <div>
                                    <div class="luxe-label">{{ $isUpcomingTab ? 'Starting' : 'Current Bid' }}</div>
                                    <div class="fw-bold" style="color: var(--ecc-primary);">
                                        {{ $isUpcomingTab ? $formattedStartingBid : $formattedCurrentBid }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="luxe-label">{{ $isUpcomingTab ? 'Opens in' : 'Time Left' }}</div>
                                    <div class="small fw-semibold ecc-text-primary">
                                        {{ $isUpcomingTab ? ($opensIn ?? '--') : ($closesIn ?? '--') }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                @if(!$canView || $isBlurred)
                                    <button type="button" class="luxe-gold-outline-btn w-100 py-2 fs-6" style="height: 42px;" wire:click.prevent="openAccessModal({{ $lot['id'] }})">
                                        UNLOCK
                                    </button>
                                @elseif($isLiveTab || ($lot['is_effectively_live'] ?? false))
                                    <a href="{{ $bidUrl }}" class="luxe-gold-btn w-100 py-2 fs-6" style="height: 42px;">
                                        BID NOW
                                    </a>
                                @else
                                    <a href="{{ $detailsUrl }}" class="luxe-gold-outline-btn w-100 py-2 fs-6" style="height: 42px;">
                                        DETAILS
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-5 pt-2">
            <div class="mb-4 text-muted small text-uppercase tracking-widest fw-bold">
                Showing {{ $visibleLotsCount ?? (is_countable($lots ?? null) ? count($lots) : 0) }} of {{ $totalLots ?? (is_countable($lots ?? null) ? count($lots) : 0) }} lots
            </div>

            @if(($hasMoreLots ?? false) === true)
                <button type="button" class="luxe-gold-outline-btn" wire:click="loadMore">
                    <span>LOAD MORE AUCTIONS</span>
                    <i class="mdi mdi-chevron-down ms-2"></i>
                </button>
            @endif
        </div>
    @else
        <div class="luxe-empty-state">
            <i class="mdi mdi-gavel fs-1 mb-3" style="color: rgba(199, 167, 90,0.4)"></i>
            <div class="fs-5 fw-semibold mb-2">No items found</div>
            <div class="ecc-text-muted-emphasis small">There are no lots available under this section right now.</div>
        </div>
    @endif
</section>
