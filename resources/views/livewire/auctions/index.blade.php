@php
  $liveAuctionItems = collect($lots ?? [])->filter(fn($l) => $l['is_star_lot'] ?? false);
  $listingItems = collect($lots ?? []);
  $isLiveTab = ($activeTab ?? 'live') === 'live';
  $isUpcomingTab = ($activeTab ?? 'live') === 'upcoming';
@endphp

<div class="ecc-auctions-page">
    {{-- SECTION 1: LIVE AUCTIONS (Using Star Lots for Rail) --}}
    @if($liveAuctionItems->count() > 0)
    <section class="mb-5 mb-lg-6 pt-2">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <h1 class="luxe-section-title">
                <span class="luxe-section-title-bar"></span>
                <span>{{ $isLiveTab ? 'Live Bidding Highlights' : ($isUpcomingTab ? 'Upcoming Highlights' : 'Featured Lots') }}</span>
            </h1>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="luxe-round-control" id="liveAuctionPrev" aria-label="Previous live auctions">
                    <i class="mdi mdi-chevron-left fs-5"></i>
                </button>
                <button type="button" class="luxe-round-control" id="liveAuctionNext" aria-label="Next live auctions">
                    <i class="mdi mdi-chevron-right fs-5"></i>
                </button>
            </div>
        </div>

        <div class="luxe-scroll-rail" id="liveAuctionRail">
            @foreach($liveAuctionItems as $lot)
                @php
                    $canView = $lot['can_view'] ?? true;
                    $isBlurred = $lot['is_blurred'] ?? false;
                    $lockType = $lot['lock_type'] ?? 'lock';
                    $lockTitle = $lot['lock_title'] ?? 'Restricted View';
                    $lockHint = $lot['lock_hint'] ?? 'Membership Required';

                    $lotNo = $lot['lot_number'] ?? $lot['lot_no'] ?? null;
                    $title = $lot['title'] ?? 'Untitled Lot';
                    $image = $lot['image_url'] ?? null;
                    $currentBid = $lot['current_bid'] ?? null;
                    $startingBid = $lot['starting_price'] ?? null;
                    $closesIn = $lot['closes_in_human'] ?? null;
                    $opensIn = $lot['opens_in_human'] ?? null;
                    $detailsUrl = $lot['details_url'] ?? '#';
                    $bidUrl = $lot['bid_url'] ?? $detailsUrl;

                    $formattedCurrentBid = isset($currentBid) ? '₹' . number_format((float) $currentBid) : '—';
                    $formattedStartingBid = isset($startingBid) ? '₹' . number_format((float) $startingBid) : '—';
                @endphp

                <div class="luxe-hero-card">
                    <div class="luxe-hero-media @if(!$canView || $isBlurred) cursor-pointer @endif" @if(!$canView || $isBlurred) wire:click.prevent="openAccessModal({{ $lot['id'] }})" @endif>
                        @if($image)
                            <img src="{{ $image }}" alt="{{ $title }}">
                        @else
                            <div class="d-flex w-100 h-100 align-items-center justify-content-center bg-dark text-muted">
                                No image
                            </div>
                        @endif

                        @if($isLiveTab)
                            <div class="luxe-live-pill">
                                <span class="luxe-live-pill-dot"></span>
                                <span>LIVE</span>
                            </div>
                        @endif

                        @if($lotNo)
                            <div class="luxe-lot-badge">
                                LOT #{{ $lotNo }}
                            </div>
                        @endif

                        {{-- Blurred State Elements --}}
                        @if(!$canView || $isBlurred)
                            <div class="ecc-lock-overlay">
                                <div class="ecc-lock-icon">
                                    <span class="material-symbols-outlined">
                                        @if($lockType === 'time-lock') lock_clock
                                        @elseif($lockType === 'diamond') diamond
                                        @else lock
                                        @endif
                                    </span>
                                </div>
                                <div class="ecc-lock-title text-uppercase">{{ $lockTitle }}</div>
                                <div class="ecc-lock-hint">{{ $lockHint }}</div>
                            </div>
                        @endif

                        @if($isBlurred || !$canView)
                            <div class="ecc-blur"></div>
                        @endif
                    </div>

                    <div class="luxe-hero-body">
                        <h3 class="luxe-hero-title text-truncate">{{ $title }}</h3>

                        <div class="row align-items-end g-3">
                            <div class="col">
                                <div class="luxe-label">{{ $isUpcomingTab ? 'Starting' : 'Current Bid' }}</div>
                                <div class="luxe-price">{{ $isUpcomingTab ? $formattedStartingBid : $formattedCurrentBid }}</div>
                            </div>

                            <div class="col-auto text-end">
                                <div class="luxe-label">{{ $isUpcomingTab ? 'Opens in' : 'Closes in' }}</div>
                                <div class="luxe-time">{{ $isUpcomingTab ? ($opensIn ?? '--') : ($closesIn ?? '--') }}</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            @if(!$canView || $isBlurred)
                                <button type="button" class="luxe-gold-btn w-100 border-0" wire:click.prevent="openAccessModal({{ $lot['id'] }})">
                                    <i class="mdi mdi-lock-open-variant-outline fs-5"></i>
                                    <span>UNLOCK ACCESS</span>
                                </button>
                            @elseif($isLiveTab)
                                <a href="{{ $bidUrl }}" class="luxe-gold-btn w-100">
                                    <i class="mdi mdi-gavel fs-5"></i>
                                    <span>PLACE BID</span>
                                </a>
                            @else
                                <a href="{{ $detailsUrl }}" class="luxe-gold-outline-btn w-100">
                                    <i class="mdi mdi-magnify fs-5"></i>
                                    <span>VIEW DETAILS</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- SECTION 2: EXPLORE COLLECTIONS --}}
    <section>
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 gap-lg-4 mb-4 mb-lg-5 pt-3">
            <h2 class="luxe-subsection-title">
                <span class="luxe-subsection-title-bar"></span>
                <span>Explore Catalog</span>
            </h2>

            <div class="luxe-chip-row align-items-center">
                <span class="text-secondary small text-uppercase fw-bold me-2 tracking-widest">Filter by:</span>
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
                                    <div class="d-flex w-100 h-100 align-items-center justify-content-center bg-dark text-muted">
                                        No image
                                    </div>
                                @endif

                                @if($lotNo)
                                    <div class="luxe-lot-badge" style="left: 12px; right: auto; bottom: auto; top: 12px; padding: .32rem .55rem; font-size: .62rem;">
                                        LOT #{{ $lotNo }}
                                    </div>
                                @endif
                                
                                @if($hasPremiumBadge)
                                    <span class="luxe-live-pill" style="left: 12px; right: auto; bottom: 12px; top: auto; background: rgba(0,0,0,0.7); font-size: 0.65rem; border: 1px solid rgba(212,175,55,0.4); color: #f2b90d; backdrop-filter: blur(8px);">
                                        <i class="mdi mdi-diamond-stone"></i> {{ $lot['access_badge_label'] ?? 'Platinum Access' }}
                                    </span>
                                @endif

                                <button type="button" class="luxe-fav-btn" aria-label="Favorite">
                                    <i class="mdi mdi-heart-outline"></i>
                                </button>
                                
                                {{-- Blurred State Elements --}}
                                @if(!$canView || $isBlurred)
                                    <div class="ecc-lock-overlay">
                                        <div class="ecc-lock-icon" style="width:36px; height:36px;">
                                            <span class="material-symbols-outlined" style="font-size: 16px;">
                                                @if($lockType === 'time-lock') lock_clock
                                                @elseif($lockType === 'diamond') diamond
                                                @else lock
                                                @endif
                                            </span>
                                        </div>
                                        <div class="ecc-lock-title text-uppercase" style="font-size: 9px;">{{ $lockTitle }}</div>
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
                                        <div class="fw-bold" style="color: var(--luxe-gold);">
                                            {{ $isUpcomingTab ? $formattedStartingBid : $formattedCurrentBid }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="luxe-label">{{ $isUpcomingTab ? 'Opens in' : 'Time Left' }}</div>
                                        <div class="small fw-semibold text-white">
                                            {{ $isUpcomingTab ? ($opensIn ?? '--') : ($closesIn ?? '--') }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    @if(!$canView || $isBlurred)
                                        <button type="button" class="luxe-gold-outline-btn w-100 py-2 fs-6" style="height: 42px;" wire:click.prevent="openAccessModal({{ $lot['id'] }})">
                                            UNLOCK
                                        </button>
                                    @elseif($isLiveTab)
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
                <i class="mdi mdi-gavel fs-1 mb-3" style="color: rgba(212,175,55,0.4)"></i>
                <div class="fs-5 fw-semibold mb-2">No items found</div>
                <div class="text-light-emphasis small">There are no lots available under this section right now.</div>
            </div>
        @endif
    </section>

    {{-- Premium Access Upgrade Modal --}}
    @include('components.shared.premium-access-modal')
</div>

@push('scripts')
<script>
    (function () {
        function initLuxeRail() {
            const rail = document.getElementById('liveAuctionRail');
            const prev = document.getElementById('liveAuctionPrev');
            const next = document.getElementById('liveAuctionNext');

            if (!rail || !prev || !next) return;
            
            // Remove old listeners to avoid duplicates on Livewire refresh
            const newPrev = prev.cloneNode(true);
            const newNext = next.cloneNode(true);
            prev.parentNode.replaceChild(newPrev, prev);
            next.parentNode.replaceChild(newNext, next);

            const scrollAmount = 420;

            newPrev.addEventListener('click', function () {
                rail.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            });

            newNext.addEventListener('click', function () {
                rail.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            });
        }

        document.addEventListener('DOMContentLoaded', initLuxeRail);
        document.addEventListener('livewire:navigated', initLuxeRail);
        document.addEventListener('livewire:navigating', () => { /* optional cleanup */ });
        
        // Handle Livewire V3 DOM updates
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('morph.updated', ({ el, component }) => {
                initLuxeRail();
            });
        }
    })();
</script>
@endpush
