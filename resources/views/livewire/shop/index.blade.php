<div class="ecc-club-store-page">
    @push('styles')
        <style>
            .ecc-club-store-page {
                background:
                    radial-gradient(circle at top, rgba(242, 185, 13, 0.08), transparent 26%),
                    linear-gradient(180deg, #221e10 0%, #1d190d 100%);
                min-height: 100%;
                color: #fff;
            }

            .ecc-store-shell {
                width: 100%;
                max-width: 1180px;
                margin: 0 auto;
                padding: 0 1rem 5.5rem;
            }

            @media (min-width: 768px) {
                .ecc-store-shell {
                    padding-left: 1.25rem;
                    padding-right: 1.25rem;
                    padding-bottom: 2rem;
                }
            }

            .ecc-store-topbar {
                position: sticky;
                top: 0;
                z-index: 40;
                background: rgba(34, 30, 16, 0.94);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(255,255,255,0.05);
                margin: 0 -1rem;
                padding: 1rem 1rem 0.75rem;
            }

            @media (min-width: 768px) {
                .ecc-store-topbar {
                    margin-left: -1.25rem;
                    margin-right: -1.25rem;
                    padding-left: 1.25rem;
                    padding-right: 1.25rem;
                }
            }

            .ecc-store-topbar-inner {
                max-width: 1180px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .ecc-store-icon-btn {
                width: 42px;
                height: 42px;
                border-radius: 999px;
                border: 0;
                background: transparent;
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: relative;
                transition: all .2s ease;
            }

            .ecc-store-icon-btn:hover {
                background: rgba(255,255,255,0.08);
            }

            .ecc-store-title {
                margin: 0;
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
                text-align: center;
                flex: 1;
            }

            .ecc-cart-badge {
                position: absolute;
                top: 2px;
                right: 2px;
                min-width: 18px;
                height: 18px;
                border-radius: 999px;
                background: #f2b90d;
                color: #000;
                font-size: 0.65rem;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
            }

            .ecc-store-search-wrap {
                position: sticky;
                top: 60px;
                z-index: 35;
                background: #221e10;
                padding: 1rem 0 0.9rem;
            }

            .ecc-store-search {
                display: flex;
                align-items: center;
                width: 100%;
                border-radius: 0.95rem;
                background: #493f22;
                box-shadow: inset 0 1px 2px rgba(0,0,0,0.18);
                overflow: hidden;
            }

            .ecc-store-search-icon,
            .ecc-store-search-action {
                width: 48px;
                height: 48px;
                flex: 0 0 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #f2b90d;
                border: 0;
                background: transparent;
            }

            .ecc-store-search-input {
                flex: 1;
                height: 48px;
                border: 0;
                background: transparent;
                color: #fff;
                font-size: 1rem;
                outline: none;
                box-shadow: none;
            }

            .ecc-store-search-input::placeholder {
                color: #cbbc90;
            }

            .ecc-store-chip-row {
                display: flex;
                gap: 0.75rem;
                overflow-x: auto;
                padding: 0 0 1rem;
                scrollbar-width: none;
            }

            .ecc-store-chip-row::-webkit-scrollbar {
                display: none;
            }

            .ecc-store-chip {
                height: 2.35rem;
                flex: 0 0 auto;
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                border-radius: 0.7rem;
                border: 1px solid rgba(255,255,255,0.05);
                background: #493f22;
                color: #fff;
                padding: 0 1rem;
                font-size: 0.92rem;
                font-weight: 500;
            }

            .ecc-store-chip.active {
                background: #f2b90d;
                color: #000;
                border-color: #f2b90d;
                font-weight: 700;
                box-shadow: 0 10px 22px rgba(242,185,13,0.12);
            }

            .ecc-store-section-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 0.9rem;
            }

            .ecc-store-section-title {
                margin: 0;
                font-size: 1.35rem;
                font-weight: 700;
                color: #fff;
            }

            .ecc-store-link {
                color: #f2b90d;
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                text-decoration: none;
            }

            .ecc-featured-row {
                display: flex;
                gap: 1rem;
                overflow-x: auto;
                padding-bottom: 0.35rem;
                scrollbar-width: none;
            }

            .ecc-featured-row::-webkit-scrollbar {
                display: none;
            }

            .ecc-feature-card {
                width: 17rem;
                flex: 0 0 auto;
                display: flex;
                flex-direction: column;
                gap: 0.8rem;
                background: #2d281a;
                border-radius: 1rem;
                padding: 0.85rem;
                box-shadow: 0 12px 26px rgba(0,0,0,0.20);
            }

            @media (min-width: 992px) {
                .ecc-feature-card {
                    width: 19rem;
                }
            }

            .ecc-feature-image-wrap {
                position: relative;
                width: 100%;
                aspect-ratio: 4 / 3;
                border-radius: 0.8rem;
                overflow: hidden;
                background: #1a1a1a;
            }

            .ecc-feature-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .ecc-badge {
                position: absolute;
                top: 0.6rem;
                left: 0.6rem;
                border-radius: 0.35rem;
                padding: 0.22rem 0.5rem;
                font-size: 0.62rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
            }

            .ecc-badge-new {
                background: #f2b90d;
                color: #000;
            }

            .ecc-badge-sale {
                background: #dc3545;
                color: #fff;
            }

            .ecc-product-title {
                font-size: 1rem;
                font-weight: 600;
                color: #fff;
                margin: 0;
                line-height: 1.3;
            }

            .ecc-product-subtitle {
                font-size: 0.8rem;
                color: #cbbc90;
                margin: 0;
            }

            .ecc-price {
                font-size: 1rem;
                font-weight: 800;
                color: #f2b90d;
            }

            .ecc-price-old {
                font-size: 0.8rem;
                color: rgba(255,255,255,0.35);
                text-decoration: line-through;
            }

            .ecc-grid-wrap {
                margin-top: 1.75rem;
            }

            .ecc-product-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
            }

            @media (min-width: 768px) {
                .ecc-product-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1200px) {
                .ecc-product-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .ecc-product-card {
                display: flex;
                flex-direction: column;
                overflow: hidden;
                border-radius: 1rem;
                background: #2d281a;
                height: 100%;
            }

            .ecc-product-image-wrap {
                position: relative;
                aspect-ratio: 1 / 1;
                overflow: hidden;
                background: #1a1a1a;
            }

            .ecc-product-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
                transition: transform .3s ease;
            }

            .ecc-product-card:hover .ecc-product-image {
                transform: scale(1.04);
            }

            .ecc-product-action {
                position: absolute;
                right: 0.65rem;
                bottom: 0.65rem;
                width: 34px;
                height: 34px;
                border-radius: 999px;
                border: 0;
                background: rgba(255,255,255,0.12);
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                transition: all .2s ease;
            }

            .ecc-product-action:hover {
                background: #f2b90d;
                color: #000;
            }

            .ecc-product-body {
                padding: 0.85rem;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                flex: 1;
            }

            .ecc-product-title-sm {
                margin: 0;
                font-size: 0.96rem;
                font-weight: 600;
                color: #fff;
                line-height: 1.3;
            }

            .ecc-product-subtitle-sm {
                margin: 0;
                font-size: 0.8rem;
                color: #cbbc90;
            }

            .ecc-product-meta {
                margin-top: auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .ecc-rating {
                display: inline-flex;
                align-items: center;
                gap: 0.2rem;
                color: #f2b90d;
                font-size: 0.78rem;
            }

            .ecc-rating .value {
                color: rgba(255,255,255,0.72);
            }

            .ecc-soldout {
                opacity: 0.75;
            }

            .ecc-soldout .ecc-product-image {
                filter: grayscale(1);
                opacity: 0.5;
            }

            .ecc-soldout-badge {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .ecc-soldout-badge span {
                border-radius: 0.45rem;
                padding: 0.35rem 0.7rem;
                font-size: 0.75rem;
                font-weight: 700;
                color: #fff;
                background: rgba(0,0,0,0.60);
                border: 1px solid rgba(255,255,255,0.18);
            }
        </style>
    @endpush

    <div class="ecc-store-shell">
        <div class="ecc-store-topbar">
            <div class="ecc-store-topbar-inner">
                <button type="button" class="ecc-store-icon-btn" aria-label="Go back" onclick="history.back()">
                    <i class="mdi mdi-arrow-left fs-5"></i>
                </button>

                <h1 class="ecc-store-title">Club Store</h1>

                <button type="button" class="ecc-store-icon-btn" aria-label="Cart">
                    <i class="mdi mdi-cart-outline fs-4"></i>
                    @if(($cartCount ?? 0) > 0)
                        <span class="ecc-cart-badge">{{ $cartCount }}</span>
                    @endif
                </button>
            </div>
        </div>

        <div class="ecc-store-search-wrap">
            <div class="ecc-store-search mb-3">
                <button type="button" class="ecc-store-search-icon" aria-label="Search">
                    <i class="mdi mdi-magnify fs-5"></i>
                </button>

                <input
                    type="text"
                    class="ecc-store-search-input"
                    placeholder="Search equipment, jerseys..."
                    wire:model.live.debounce.400ms="search"
                >

                <button type="button" class="ecc-store-search-action" aria-label="Filter">
                    <i class="mdi mdi-tune-variant fs-5"></i>
                </button>
            </div>

            <div class="ecc-store-chip-row">
                <button
                    type="button"
                    class="ecc-store-chip {{ empty($activeCategoryId) ? 'active' : '' }}"
                    wire:click="$set('activeCategoryId', null)"
                >
                    <i class="mdi mdi-view-grid-outline"></i>
                    <span>All</span>
                </button>

                @foreach(($categories ?? []) as $category)
                    <button
                        type="button"
                        class="ecc-store-chip {{ (string)($activeCategoryId ?? '') === (string)($category->id ?? $category['id'] ?? '') ? 'active' : '' }}"
                        wire:click="$set('activeCategoryId', '{{ $category->id ?? $category['id'] ?? '' }}')"
                    >
                        <i class="mdi mdi-tag-outline"></i>
                        <span>{{ $category->name ?? $category['name'] ?? 'Category' }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <section class="mb-4">
            <div class="ecc-store-section-head">
                <h2 class="ecc-store-section-title">New Arrivals</h2>
                <a href="{{ route('shop.index', ['sort' => 'newest']) }}" class="ecc-store-link">View All</a>
            </div>

            <div class="ecc-featured-row">
                @forelse(($newArrivals ?? []) as $product)
                    @php
                        $image = $product['image_url'] ?? null;
                        $title = $product['name'] ?? 'Product';
                        $subtitle = $product['short_description'] ?? '';
                        $priceDisplay = $product['price_display'] ?? '';
                        $oldPriceDisplay = $product['old_price_display'] ?? null;
                        $isNew = (bool)($product['is_new'] ?? false);
                        $isSale = (bool)($product['is_on_sale'] ?? false);
                        $detailsUrl = $product['details_url'] ?? '#';
                    @endphp

                    <article class="ecc-feature-card">
                        <a href="{{ $detailsUrl }}" class="text-decoration-none">
                            <div class="ecc-feature-image-wrap">
                                @if($image)
                                    <img src="{{ $image }}" alt="{{ $title }}" class="ecc-feature-image">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                        <i class="mdi mdi-image-outline fs-1"></i>
                                    </div>
                                @endif

                                @if($isNew)
                                    <span class="ecc-badge ecc-badge-new">New</span>
                                @elseif($isSale)
                                    <span class="ecc-badge ecc-badge-sale">Sale</span>
                                @endif
                            </div>
                        </a>

                        <div class="px-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h3 class="ecc-product-title">{{ $title }}</h3>
                                <span class="ecc-price">{{ $priceDisplay }}</span>
                            </div>
                            @if($subtitle)
                                <p class="ecc-product-subtitle">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="text-light-emphasis">No new arrivals available.</div>
                @endforelse
            </div>
        </section>

        <section class="ecc-grid-wrap">
            <h2 class="ecc-store-section-title mb-3">All Equipment</h2>

            <div class="ecc-product-grid">
                @forelse(($products ?? []) as $product)
                    @php
                        $image = $product['image_url'] ?? null;
                        $title = $product['name'] ?? 'Product';
                        $subtitle = $product['short_description'] ?? '';
                        $priceDisplay = $product['price_display'] ?? '';
                        $oldPriceDisplay = $product['old_price_display'] ?? null;
                        $isSoldOut = (bool)($product['is_sold_out'] ?? false);
                        $isSale = (bool)($product['is_on_sale'] ?? false);
                        $rating = $product['rating'] ?? null;
                        $detailsUrl = $product['details_url'] ?? '#';
                    @endphp

                    <article class="ecc-product-card {{ $isSoldOut ? 'ecc-soldout' : '' }}">
                        <a href="{{ $detailsUrl }}" class="text-decoration-none">
                            <div class="ecc-product-image-wrap">
                                @if($image)
                                    <img src="{{ $image }}" alt="{{ $title }}" class="ecc-product-image">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                        <i class="mdi mdi-image-outline fs-1"></i>
                                    </div>
                                @endif

                                @if($isSale)
                                    <span class="ecc-badge ecc-badge-sale">Sale</span>
                                @endif

                                @if($isSoldOut)
                                    <div class="ecc-soldout-badge">
                                        <span>Sold Out</span>
                                    </div>
                                @else
                                    <button type="button" class="ecc-product-action" aria-label="Add to cart">
                                        <i class="mdi mdi-plus fs-6"></i>
                                    </button>
                                @endif
                            </div>
                        </a>

                        <div class="ecc-product-body">
                            <div>
                                <a href="{{ $detailsUrl }}" class="text-decoration-none">
                                    <h3 class="ecc-product-title-sm">{{ $title }}</h3>
                                </a>
                                @if($subtitle)
                                    <p class="ecc-product-subtitle-sm">{{ Str::limit($subtitle, 35) }}</p>
                                @endif
                            </div>

                            <div class="ecc-product-meta">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="ecc-price">{{ $priceDisplay }}</span>
                                    @if($oldPriceDisplay)
                                        <span class="ecc-price-old">{{ $oldPriceDisplay }}</span>
                                    @endif
                                </div>

                                @if($rating)
                                    <span class="ecc-rating">
                                        <i class="mdi mdi-star"></i>
                                        <span class="value">{{ $rating }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="text-light-emphasis">No products available.</div>
                @endforelse
            </div>
        </section>
        
        <div class="mt-4">
            {{ $paginator->links() ?? '' }}
        </div>
    </div>
</div>
