<div class="w-100 pt-1 pb-5 ecc-order-history-page">
    @push('styles')
    <style>
        .ecc-order-history-page {
            color: var(--ecc-text-primary);
            position: relative;
        }

        .ecc-history-glow {
            position: absolute;
            top: -80px;
            left: 50%;
            transform: translateX(-50%);
            width: 1000px;
            height: 560px;
            border-radius: 999px;
            background: rgba(199, 167, 90, 0.05);
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
        }

        .ecc-accent-line {
            width: 48px;
            height: 1px;
            background: var(--ecc-primary);
            display: inline-block;
        }

        .ecc-kicker {
            color: var(--ecc-primary);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .28em;
            text-transform: uppercase;
        }

        .ecc-history-title {
            font-size: clamp(2.6rem, 6vw, 5rem);
            line-height: .95;
            font-weight: 900;
            letter-spacing: -.05em;
            color: var(--ecc-text-primary);
            margin: 0;
            text-transform: uppercase;
        }

        .ecc-history-subtitle {
            max-width: 640px;
            color: var(--ecc-text-muted);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .ecc-stat-card,
        .ecc-order-history-card,
        .ecc-empty-orders-card {
            background: linear-gradient(180deg, var(--ecc-bg-surface), var(--ecc-bg-surface-2));
            border: 1px solid rgba(199, 167, 90,.10);
            border-radius: 1.25rem;
            color: var(--ecc-text-primary);
            box-shadow: var(--ecc-shadow-soft);
        }

        .ecc-stat-card {
            padding: 1.75rem;
            height: 100%;
        }

        .ecc-stat-card.is-highlight {
            box-shadow: 0 0 40px rgba(199, 167, 90,.05), var(--ecc-shadow-soft);
        }

        .ecc-stat-label {
            color: var(--ecc-text-muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            margin-bottom: .5rem;
        }

        .ecc-stat-value {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 900;
            line-height: 1.05;
            color: var(--ecc-text-primary);
        }

        .ecc-stat-value span {
            font-size: .95rem;
            font-weight: 500;
            color: var(--ecc-text-muted);
        }

        .ecc-text-primary {
            color: var(--ecc-primary) !important;
        }

        .ecc-order-history-card {
            overflow: hidden;
            transition: .3s ease;
        }

        .ecc-order-history-card:hover {
            border-color: rgba(199, 167, 90,.24);
            transform: translateY(-1px);
        }

        .ecc-order-history-image {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 170px;
            background: var(--ecc-bg-input);
        }

        @media (min-width: 768px) {
            .ecc-order-history-image {
                width: 192px;
                min-width: 192px;
            }
        }

        .ecc-order-history-image img {
            filter: grayscale(.55);
            opacity: .72;
            transition: .6s ease;
        }

        .ecc-order-history-card:hover .ecc-order-history-image img {
            filter: grayscale(0);
            opacity: 1;
            transform: scale(1.08);
        }

        .ecc-order-history-image-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(11,9,4,.75), transparent);
            pointer-events: none;
        }

        .ecc-order-history-body {
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        @media (min-width: 992px) {
            .ecc-order-history-body {
                padding: 2rem;
            }
        }

        .ecc-order-card-title {
            color: var(--ecc-text-primary);
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .ecc-order-card-date {
            color: var(--ecc-text-muted);
            font-size: .95rem;
        }

        .ecc-order-card-amount {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            color: var(--ecc-text-primary);
        }

        .ecc-order-card-secondary-amount {
            color: var(--ecc-text-subtle);
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
            margin-top: .35rem;
        }

        .ecc-status-pill {
            display: inline-flex;
            align-items: center;
            padding: .38rem .75rem;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .ecc-status-pill.status-processing {
            background: rgba(199, 167, 90,.10);
            color: var(--ecc-primary);
            border-color: var(--ecc-primary-border);
        }

        .ecc-status-pill.status-shipped {
            background: rgba(59,130,246,.10);
            color: #60a5fa;
            border-color: rgba(59,130,246,.18);
        }

        .ecc-status-pill.status-delivered {
            background: rgba(16,185,129,.10);
            color: #34d399;
            border-color: rgba(16,185,129,.18);
        }

        .ecc-status-pill.status-paid,
        .ecc-status-pill.status-default {
            background: var(--ecc-border-soft);
            color: var(--ecc-text-primary);
            border-color: var(--ecc-text-muted);
        }

        .ecc-order-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-top: 1.25rem;
            margin-top: 1.25rem;
            border-top: 1px solid var(--ecc-border-soft);
            flex-wrap: wrap;
        }

        .ecc-order-preview-stack {
            display: flex;
            align-items: center;
        }

        .ecc-order-preview-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            overflow: hidden;
            border: 2px solid #1a140b;
            background: var(--ecc-border-soft);
            margin-left: -10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
            font-weight: 800;
            color: var(--ecc-text-primary);
        }

        .ecc-order-preview-avatar:first-child {
            margin-left: 0;
        }

        .ecc-order-preview-avatar.is-count {
            background: var(--ecc-border-soft);
        }

        .ecc-order-footer-note {
            color: var(--ecc-text-muted);
            font-size: .82rem;
        }

        .ecc-order-transit-note {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            color: var(--ecc-primary);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .ecc-order-details-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--ecc-primary);
            text-decoration: none;
            font-size: .82rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .ecc-order-details-link:hover {
            color: #e7c75c;
        }

        .ecc-btn-outline-gold {
            background: transparent;
            border: 2px solid rgba(199, 167, 90,.28);
            color: var(--ecc-primary);
            font-size: .82rem;
            font-weight: 900;
            letter-spacing: .28em;
            text-transform: uppercase;
        }

        .ecc-btn-outline-gold:hover,
        .ecc-btn-outline-gold:focus {
            color: #e7c75c;
            border-color: var(--ecc-primary);
            background: rgba(199, 167, 90,.05);
        }

        .ecc-btn-primary {
            background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
            border: 1px solid var(--ecc-primary);
            color: #16110a;
            font-weight: 900;
            border-radius: .95rem;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .ecc-btn-primary:hover,
        .ecc-btn-primary:focus {
            background: var(--ecc-primary-gradient-dark);
            color: #16110a;
        }

        .ecc-pagination-note {
            color: var(--ecc-text-muted);
            font-size: .62rem;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .ecc-empty-orders-card {
            padding: 3rem 1.5rem;
        }

        .ecc-empty-orders-icon {
            width: 72px;
            height: 72px;
            margin-inline: auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ecc-primary-soft);
            color: var(--ecc-primary);
            font-size: 2rem;
        }

        .ecc-empty-orders-title {
            color: var(--ecc-text-primary);
            font-size: 1.5rem;
            font-weight: 800;
        }

        .ecc-empty-orders-text {
            max-width: 520px;
            margin-inline: auto;
            color: var(--ecc-ecc-ecc-text-muted);
            line-height: 1.8;
        }
    </style>
    @endpush

    <div class="row justify-content-center">
        <div class="col-12 col-xxl-10 position-relative">

            <!-- Atmosphere glow -->
            <div class="ecc-history-glow d-none d-lg-block"></div>

            <!-- Header -->
            <header class="mb-4 position-relative">
                <div class="d-flex align-items-center gap-3 mb-3" style="z-index: 10;">
                    <span class="ecc-accent-line"></span>
                    <span class="ecc-kicker">THE DIGITAL CURATOR</span>
                </div>

                <h1 class="ecc-history-title mb-3" style="z-index: 10;">ORDER HISTORY</h1>

                <p class="ecc-history-subtitle mb-0" style="z-index: 10;">
                    Review your legacy acquisitions and track the transit of your curated cricket memorabilia.
                </p>
            </header>

            <!-- Stats -->
            <section class="mb-4 mb-xl-5 position-relative" style="z-index: 10;">
                <div class="row g-3 g-lg-4">
                    <div class="col-12 col-md-4">
                        <div class="ecc-stat-card is-highlight">
                            <div class="ecc-stat-label">TOTAL INVESTED</div>
                            <div class="ecc-stat-value ecc-text-primary">{{ $formattedTotalInvested }}</div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="ecc-stat-card">
                            <div class="ecc-stat-label">ACTIVE TRANSITS</div>
                            <div class="ecc-stat-value">
                                {{ $activeTransitCount }}
                                <span>{{ \Illuminate\Support\Str::plural('Order', $activeTransitCount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="ecc-stat-card">
                            <div class="ecc-stat-label">CURATED ITEMS</div>
                            <div class="ecc-stat-value">
                                {{ $curatedItemsCount }}
                                <span>{{ \Illuminate\Support\Str::plural('Artifact', $curatedItemsCount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Orders -->
            <section class="position-relative" style="z-index: 10;">
                @if($orders->count())
                    <div class="d-flex flex-column gap-3 gap-lg-4">
                        @foreach($orders as $order)
                            <div class="ecc-order-history-card">
                                <div class="row g-0 align-items-stretch">
                                    <div class="col-12 col-md-auto">
                                        <div class="ecc-order-history-image">
                                            <img src="{{ $order->hero_image_url }}"
                                                 alt="{{ $order->hero_image_alt ?? $order->display_reference }}"
                                                 class="w-100 h-100 object-fit-cover">
                                            <div class="ecc-order-history-image-overlay"></div>
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="ecc-order-history-body">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 mb-4">
                                                <div>
                                                    <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-1">
                                                        <h3 class="ecc-order-card-title mb-0">
                                                            Order {{ $order->display_reference }}
                                                        </h3>

                                                        <span class="ecc-status-pill {{ $order->status_badge_class }}">
                                                            {{ $order->status_label }}
                                                        </span>
                                                    </div>

                                                    <p class="ecc-order-card-date mb-0">
                                                        Purchased on {{ $order->placed_at_label }}
                                                    </p>
                                                </div>

                                                <div class="text-lg-end">
                                                    <div class="ecc-order-card-amount {{ $order->status_emphasis_class }}">
                                                        {{ $order->formatted_total }}
                                                    </div>

                                                    @if($order->formatted_secondary_total)
                                                        <div class="ecc-order-card-secondary-amount">
                                                            {{ $order->formatted_secondary_total }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="ecc-order-card-footer">
                                                <div class="d-flex align-items-center flex-wrap gap-3">
                                                    @if($order->preview_images && count($order->preview_images))
                                                        <div class="ecc-order-preview-stack">
                                                            @foreach(array_slice($order->preview_images, 0, 3) as $previewImage)
                                                                <span class="ecc-order-preview-avatar">
                                                                    <img src="{{ $previewImage }}" alt="Order item preview" class="w-100 h-100 object-fit-cover">
                                                                </span>
                                                            @endforeach

                                                            @if($order->extra_items_count > 0)
                                                                <span class="ecc-order-preview-avatar is-count">
                                                                    +{{ $order->extra_items_count }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @elseif($order->footer_note)
                                                        <div class="ecc-order-footer-note">
                                                            {{ $order->footer_note }}
                                                        </div>
                                                    @endif

                                                    @if($order->transit_note)
                                                        <div class="ecc-order-transit-note">
                                                            <span class="material-symbols-outlined fs-5 align-middle">local_shipping</span>
                                                            <span>{{ $order->transit_note }}</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <a href="{{ $order->details_url }}"
                                                   class="ecc-order-details-link">
                                                    <span>VIEW DETAILS</span>
                                                    <span class="material-symbols-outlined fs-5 align-middle">arrow_forward</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($hasMoreOrders)
                        <div class="mt-5 mt-xl-6 d-flex flex-column align-items-center gap-4">
                            <button type="button"
                                    class="btn ecc-btn-outline-gold rounded-pill px-5 py-3"
                                    wire:click="loadMore"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="loadMore">LOAD ARCHIVE HISTORY</span>
                                <span wire:loading wire:target="loadMore">LOADING...</span>
                            </button>

                            <div class="ecc-pagination-note">
                                Showing {{ $visibleOrdersCount }} of {{ $totalOrdersCount }} acquisitions
                            </div>
                        </div>
                    @else
                        <div class="mt-5 d-flex justify-content-center">
                            <div class="ecc-pagination-note">
                                Showing {{ $visibleOrdersCount }} of {{ $totalOrdersCount }} acquisitions
                            </div>
                        </div>
                    @endif
                @else
                    <div class="ecc-empty-orders-card text-center">
                        <div class="ecc-empty-orders-icon mb-3">
                            <span class="material-symbols-outlined fs-1">package_2</span>
                        </div>

                        <h3 class="ecc-empty-orders-title mb-2">No acquisitions yet</h3>

                        <p class="ecc-empty-orders-text mb-4">
                            Your order history will appear here once you complete your first curated purchase.
                        </p>

                        <a href="{{ $continueShoppingUrl }}"
                           class="btn ecc-btn-primary px-4 py-3">
                            CONTINUE SHOPPING
                        </a>
                    </div>
                @endif
            </section>

        </div>
    </div>
</div>
