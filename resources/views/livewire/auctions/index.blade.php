<div class="ecc-auctions-page">
    @push('styles')
        <style>
            .ecc-auctions-page {
                background:
                    radial-gradient(circle at top, rgba(242, 185, 13, 0.10), transparent 28%),
                    linear-gradient(180deg, #231e10 0%, #1d180d 100%);
                min-height: 100vh;
                color: #cbbc90;
            }

            .ecc-auctions-shell {
                width: 100%;
                max-width: 920px;
                margin: 0 auto;
                padding-bottom: 120px;
            }

            .ecc-sticky-header {
                position: sticky;
                top: 0;
                z-index: 1040;
                background: rgba(35, 30, 16, 0.96);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(73, 63, 34, 0.55);
            }

            .ecc-sticky-tabs {
                position: sticky;
                top: 73px;
                z-index: 1035;
                background: rgba(35, 30, 16, 0.95);
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                border-bottom: 1px solid rgba(73, 63, 34, 0.45);
            }

            @media (min-width: 992px) {
                .ecc-sticky-tabs {
                    top: 77px;
                }
            }

            .ecc-icon-btn {
                width: 48px;
                height: 48px;
                border-radius: 50%;
                border: 1px solid rgba(242, 185, 13, 0.10);
                background: rgba(73, 63, 34, 0.22);
                color: #f2b90d;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                text-decoration: none;
                box-shadow: none;
            }

            .ecc-icon-btn:hover {
                background: rgba(73, 63, 34, 0.42);
                color: #ffd04a;
            }

            .ecc-page-title {
                color: #f2b90d;
                font-weight: 700;
                font-size: 1.45rem;
                line-height: 1.2;
                letter-spacing: -0.02em;
                margin: 0;
                text-align: center;
                font-family: "Newsreader", Georgia, serif;
            }

            .ecc-tabs-wrap {
                background: #493f22;
                border-radius: 0.8rem;
                padding: 0.3rem;
            }

            .ecc-auction-tab {
                border: 0 !important;
                border-radius: 0.65rem !important;
                background: transparent !important;
                color: #cbbc90 !important;
                font-size: 0.95rem;
                font-weight: 600;
                padding: 0.7rem 0.75rem;
                line-height: 1.1;
                white-space: nowrap;
                box-shadow: none !important;
            }

            .ecc-auction-tab.active,
            .ecc-auction-tab:active,
            .ecc-auction-tab:focus,
            .ecc-auction-tab:hover.active {
                background: #231e10 !important;
                color: #f2b90d !important;
            }

            .ecc-auction-tab:hover {
                color: #f2b90d !important;
            }

            .ecc-cards-stack {
                padding: 1rem;
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }

            @media (min-width: 768px) {
                .ecc-cards-stack {
                    padding: 1.25rem 1.25rem 1.5rem;
                    gap: 1.75rem;
                }
            }

            @media (min-width: 992px) {
                .ecc-cards-stack {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    padding-left: 1.5rem;
                    padding-right: 1.5rem;
                    gap: 1.75rem;
                }
            }

            .ecc-auction-card {
                background: #2d2616;
                border: 1px solid rgba(73, 63, 34, 0.75);
                border-radius: 1rem;
                overflow: hidden;
                box-shadow: 0 12px 28px rgba(0, 0, 0, 0.24);
                color: #cbbc90;
            }

            .ecc-auction-card.featured {
                border-color: rgba(242, 185, 13, 0.28);
                box-shadow: 0 16px 34px rgba(0, 0, 0, 0.28);
            }

            .ecc-card-image-wrap {
                position: relative;
                overflow: hidden;
                background: #1f1a0f;
            }

            .ecc-card-image {
                width: 100%;
                display: block;
                object-fit: cover;
                background: #1f1a0f;
            }

            .ecc-featured-image {
                aspect-ratio: 4 / 3;
                min-height: 260px;
            }

            .ecc-regular-image {
                aspect-ratio: 16 / 10;
                min-height: 220px;
            }

            @media (min-width: 768px) {
                .ecc-featured-image {
                    min-height: 360px;
                }

                .ecc-regular-image {
                    min-height: 300px;
                }
            }

            .ecc-featured-gradient {
                position: absolute;
                inset: 0;
                background: linear-gradient(to top, rgba(45, 38, 22, 0.97) 12%, rgba(45, 38, 22, 0.20) 58%, rgba(45, 38, 22, 0.04) 100%);
            }

            .ecc-star-badge {
                position: absolute;
                top: 0.9rem;
                right: 0.9rem;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.5rem 0.95rem;
                border-radius: 999px;
                background: #f2b90d;
                color: #231e10;
                font-weight: 800;
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                box-shadow: 0 8px 18px rgba(242, 185, 13, 0.24);
            }

            .ecc-access-badge {
                position: absolute;
                left: 0.75rem;
                bottom: 0.75rem;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.42rem 0.7rem;
                border-radius: 0.5rem;
                font-size: 0.78rem;
                font-weight: 500;
                color: #f2b90d;
                border: 1px solid rgba(242, 185, 13, 0.4);
                background: rgba(36, 29, 15, 0.78);
                backdrop-filter: blur(6px);
            }

            .ecc-featured-body {
                position: relative;
                margin-top: -5.2rem;
                z-index: 3;
                padding: 1.25rem;
            }

            @media (min-width: 768px) {
                .ecc-featured-body {
                    margin-top: -6rem;
                    padding: 1.5rem;
                }
            }

            .ecc-card-body {
                padding: 1rem 1rem 1.1rem;
            }

            @media (min-width: 768px) {
                .ecc-card-body {
                    padding: 1.2rem 1.2rem 1.3rem;
                }
            }

            .ecc-lot-meta {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
                margin-bottom: 0.55rem;
                font-size: 0.92rem;
            }

            .ecc-lot-number {
                color: #cbbc90;
                font-size: 0.96rem;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .ecc-dot {
                width: 4px;
                height: 4px;
                border-radius: 50%;
                background: #cbbc90;
                opacity: 0.85;
            }

            .ecc-hot-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.15rem 0.42rem;
                border-radius: 0.35rem;
                color: #f2b90d;
                background: rgba(242, 185, 13, 0.10);
                border: 1px solid rgba(242, 185, 13, 0.18);
                font-size: 0.76rem;
                font-weight: 500;
            }

            .ecc-lot-title {
                color: #f2b90d;
                font-family: "Newsreader", Georgia, serif;
                font-weight: 700;
                line-height: 1.06;
                letter-spacing: -0.025em;
                margin: 0;
                text-wrap: balance;
            }

            .ecc-lot-title.featured {
                font-size: clamp(2rem, 4vw, 3rem);
            }

            .ecc-lot-title.regular {
                font-size: clamp(1.6rem, 2.4vw, 2.15rem);
            }

            .ecc-divider {
                border: 0;
                height: 1px;
                background: rgba(73, 63, 34, 0.72);
                margin: 1rem 0 0.95rem;
            }

            .ecc-stats-row {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 1rem;
            }

            .ecc-stat-label {
                color: #cbbc90;
                font-size: 0.83rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                margin-bottom: 0.28rem;
            }

            .ecc-stat-value {
                color: #f2b90d;
                font-weight: 800;
                font-size: 1.05rem;
                line-height: 1.15;
            }

            .ecc-stat-value.featured {
                font-size: 1.85rem;
            }

            .ecc-stat-value.subtle {
                color: #f5ebd0;
                font-weight: 600;
            }

            .ecc-time-wrap {
                text-align: right;
            }

            .ecc-time-row {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                color: #f5ebd0;
                font-size: 1rem;
                font-weight: 500;
            }

            .ecc-time-icon {
                color: #f2b90d;
            }

            .ecc-btn-primary-lux {
                border: 0;
                border-radius: 0.75rem;
                background: #f2b90d;
                color: #231e10;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                padding: 0.88rem 1rem;
                width: 100%;
                box-shadow: 0 0 18px rgba(242, 185, 13, 0.15);
            }

            .ecc-btn-primary-lux:hover,
            .ecc-btn-primary-lux:focus {
                background: #ffcb28;
                color: #231e10;
            }

            .ecc-btn-outline-lux {
                border-radius: 0.75rem;
                border: 1px solid rgba(242, 185, 13, 0.42);
                color: #f2b90d;
                background: transparent;
                width: 100%;
                font-weight: 600;
                padding: 0.82rem 1rem;
            }

            .ecc-btn-outline-lux:hover,
            .ecc-btn-outline-lux:focus {
                color: #f2b90d;
                background: rgba(242, 185, 13, 0.06);
                border-color: rgba(242, 185, 13, 0.6);
            }

            .ecc-card-footer-space {
                margin-top: 1rem;
            }

            .ecc-results-footer {
                text-align: center;
                padding: 2rem 1rem 2.5rem;
            }

            .ecc-results-footer .results-icon {
                color: rgba(242, 185, 13, 0.22);
                font-size: 2.3rem;
                margin-bottom: 0.35rem;
            }

            .ecc-results-text {
                color: #d7cba2;
                font-style: italic;
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }

            .ecc-results-link {
                color: #f2b90d;
                text-decoration: underline;
                text-underline-offset: 4px;
                font-weight: 600;
                background: none;
                border: 0;
            }

            .ecc-results-link:hover {
                color: #ffd04a;
            }

            .ecc-empty-state {
                padding: 3rem 1rem 4rem;
                text-align: center;
                color: #d7cba2;
            }

            .ecc-empty-state .mdi {
                color: rgba(242, 185, 13, 0.25);
                font-size: 2.4rem;
                display: inline-block;
                margin-bottom: 0.5rem;
            }

            .ecc-bottom-nav-spacer {
                height: 6.2rem;
            }

            @media (min-width: 992px) {
                .ecc-bottom-nav-spacer {
                    height: 6.8rem;
                }
            }
        </style>
    @endpush

    <div class="ecc-auctions-shell">
        <div class="ecc-sticky-header">
            <div class="px-3 px-md-4 py-3 py-md-3">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <button
                        type="button"
                        class="ecc-icon-btn"
                        @if(method_exists($this, 'openMenu')) wire:click="openMenu" @endif
                        aria-label="Open menu"
                    >
                        <i class="mdi mdi-menu fs-3"></i>
                    </button>

                    <h1 class="ecc-page-title flex-grow-1">Current Auctions</h1>

                    <button
                        type="button"
                        class="ecc-icon-btn"
                        @if(method_exists($this, 'openSearch')) wire:click="openSearch" @endif
                        aria-label="Search auctions"
                    >
                        <i class="mdi mdi-magnify fs-4"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="ecc-sticky-tabs">
            <div class="px-3 px-md-4 py-3">
                <div class="ecc-tabs-wrap">
                    <div class="nav nav-pills nav-fill gap-1" role="tablist" aria-label="Auction filters">
                        <button
                            type="button"
                            class="nav-link ecc-auction-tab {{ ($activeTab ?? 'live') === 'live' ? 'active' : '' }}"
                            wire:click="setTab('live')"
                        >
                            Live Bidding
                        </button>

                        <button
                            type="button"
                            class="nav-link ecc-auction-tab {{ ($activeTab ?? 'live') === 'upcoming' ? 'active' : '' }}"
                            wire:click="setTab('upcoming')"
                        >
                            Upcoming
                        </button>

                        <button
                            type="button"
                            class="nav-link ecc-auction-tab {{ ($activeTab ?? 'live') === 'past' ? 'active' : '' }}"
                            wire:click="setTab('past')"
                        >
                            Past Results
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="ecc-cards-stack">
            @forelse(($lots ?? []) as $lot)
                @php
                    $isFeatured = (bool) ($lot->is_star_lot ?? $lot['is_star_lot'] ?? false);
                    $hasPremiumBadge = (bool) ($lot->requires_premium_access ?? $lot['requires_premium_access'] ?? false);
                    $isLive = (($activeTab ?? 'live') === 'live');
                    $isUpcoming = (($activeTab ?? 'live') === 'upcoming');
                    $lotNo = $lot->lot_number ?? $lot['lot_number'] ?? $lot->lot_no ?? $lot['lot_no'] ?? null;
                    $title = $lot->title ?? $lot['title'] ?? 'Untitled Lot';
                    $image = $lot->image_url ?? $lot['image_url'] ?? $lot->image ?? $lot['image'] ?? $lot->primary_image_url ?? $lot['primary_image_url'] ?? null;
                    $currentBid = $lot->current_bid_display ?? $lot['current_bid_display'] ?? null;
                    $startingBid = $lot->starting_price_display ?? $lot['starting_price_display'] ?? null;
                    $closesIn = $lot->closes_in_human ?? $lot['closes_in_human'] ?? $lot->time_remaining_human ?? $lot['time_remaining_human'] ?? null;
                    $opensIn = $lot->opens_in_human ?? $lot['opens_in_human'] ?? null;
                    $showHot = (bool) ($lot->is_hot ?? $lot['is_hot'] ?? $isFeatured);
                    $detailsUrl = $lot->details_url ?? $lot['details_url'] ?? (isset($lot->id) ? route('auctions.lots.show', $lot->id) : (isset($lot['id']) ? route('auctions.lots.show', $lot['id']) : '#'));
                    $bidUrl = $lot->bid_url ?? $lot['bid_url'] ?? $detailsUrl;

                    $formattedCurrentBid = $currentBid ?: (isset($lot->current_bid) ? '₹' . number_format((float) $lot->current_bid) : (isset($lot['current_bid']) ? '₹' . number_format((float) $lot['current_bid']) : null));
                    $formattedStartingBid = $startingBid ?: (isset($lot->starting_price) ? '₹' . number_format((float) $lot->starting_price) : (isset($lot['starting_price']) ? '₹' . number_format((float) $lot['starting_price']) : null));
                @endphp

                @if($isFeatured)
                    <article class="ecc-auction-card featured">
                        <div class="ecc-card-image-wrap">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $title }}" class="ecc-card-image ecc-featured-image">
                            @else
                                <div class="ecc-card-image ecc-featured-image d-flex align-items-center justify-content-center text-center px-4">
                                    <span class="text-muted">No image available</span>
                                </div>
                            @endif

                            <div class="ecc-featured-gradient"></div>

                            <span class="ecc-star-badge">Star Lot</span>
                        </div>

                        <div class="ecc-featured-body">
                            <div class="ecc-lot-meta">
                                @if($lotNo)
                                    <span class="ecc-lot-number">Lot #{{ $lotNo }}</span>
                                @endif

                                @if($showHot)
                                    <span class="ecc-dot"></span>
                                    <span class="ecc-hot-badge">
                                        <i class="mdi mdi-fire"></i>
                                        Hot
                                    </span>
                                @endif
                            </div>

                            <h2 class="ecc-lot-title featured">{{ $title }}</h2>

                            <hr class="ecc-divider">

                            <div class="ecc-stats-row">
                                <div>
                                    <div class="ecc-stat-label">{{ $isUpcoming ? 'Starting' : 'Current Bid' }}</div>
                                    <div class="ecc-stat-value featured">
                                        {{ $isUpcoming ? ($formattedStartingBid ?? '₹0') : ($formattedCurrentBid ?? '₹0') }}
                                    </div>
                                </div>

                                <div class="ecc-time-wrap">
                                    <div class="ecc-stat-label">{{ $isUpcoming ? 'Opens in' : 'Closes in' }}</div>
                                    <div class="ecc-time-row">
                                        <i class="mdi mdi-timer-outline ecc-time-icon"></i>
                                        <span>{{ $isUpcoming ? ($opensIn ?? '--') : ($closesIn ?? '--') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="ecc-card-footer-space">
                                @if($isLive)
                                    <a href="{{ $bidUrl }}" class="btn ecc-btn-primary-lux">Place Bid</a>
                                @else
                                    <a href="{{ $detailsUrl }}" class="btn ecc-btn-outline-lux">View Details</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @else
                    <article class="ecc-auction-card">
                        <div class="ecc-card-image-wrap">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $title }}" class="ecc-card-image ecc-regular-image">
                            @else
                                <div class="ecc-card-image ecc-regular-image d-flex align-items-center justify-content-center text-center px-4">
                                    <span class="text-muted">No image available</span>
                                </div>
                            @endif

                            @if($hasPremiumBadge)
                                <span class="ecc-access-badge">
                                    <i class="mdi mdi-diamond-stone"></i>
                                    {{ $lot->access_badge_label ?? $lot['access_badge_label'] ?? 'Platinum Access Only' }}
                                </span>
                            @endif
                        </div>

                        <div class="ecc-card-body">
                            @if($lotNo)
                                <div class="ecc-lot-number mb-1">Lot #{{ $lotNo }}</div>
                            @endif

                            <h3 class="ecc-lot-title regular">{{ $title }}</h3>

                            <div class="row g-3 mt-1 align-items-end">
                                <div class="col-6">
                                    <div class="ecc-stat-label">{{ $isUpcoming ? 'Starting' : 'Current Bid' }}</div>
                                    <div class="ecc-stat-value subtle">
                                        {{ $isUpcoming ? ($formattedStartingBid ?? '₹0') : ($formattedCurrentBid ?? '₹0') }}
                                    </div>
                                </div>

                                <div class="col-6 text-end">
                                    <div class="ecc-stat-label">{{ $isUpcoming ? 'Opens in' : 'Closes in' }}</div>
                                    <div class="ecc-stat-value subtle fw-normal">
                                        {{ $isUpcoming ? ($opensIn ?? '--') : ($closesIn ?? '--') }}
                                    </div>
                                </div>
                            </div>

                            <div class="ecc-card-footer-space">
                                @if($isLive)
                                    <a href="{{ $bidUrl }}" class="btn ecc-btn-primary-lux">Place Bid</a>
                                @else
                                    <a href="{{ $detailsUrl }}" class="btn ecc-btn-outline-lux">View Details</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @endif
            @empty
                <div class="ecc-empty-state">
                    <i class="mdi mdi-gavel"></i>
                    <div class="fs-5 fw-semibold mb-2 text-warning">No auctions found</div>
                    <div class="text-light-emphasis">There are no lots available under this section right now.</div>
                </div>
            @endforelse

            @if(!empty($lots))
                <div class="ecc-results-footer">
                    <div class="results-icon">
                        <i class="mdi mdi-gavel"></i>
                    </div>

                    <div class="ecc-results-text">
                        Showing {{ $visibleLotsCount ?? (is_countable($lots ?? null) ? count($lots) : 0) }} of {{ $totalLots ?? (is_countable($lots ?? null) ? count($lots) : 0) }} lots available.
                    </div>

                    @if(($hasMoreLots ?? false) === true)
                        <button type="button" class="ecc-results-link" wire:click="loadMore">
                            Load More Catalog
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="ecc-bottom-nav-spacer"></div>
    </div>
</div>
