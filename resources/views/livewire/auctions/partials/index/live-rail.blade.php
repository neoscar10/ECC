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
