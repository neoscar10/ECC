<div class="shop-detail-page">
    @push('styles')
    <style>
        .shop-detail-page {
            padding-top: .25rem;
            padding-bottom: 1.5rem;
        }

        .shop-detail-main {
            margin-bottom: 5rem;
        }

        .shop-detail-gallery-stage {
            position: relative;
            flex: 1;
            min-height: 400px;
            max-height: 600px;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(212,175,55,.14);
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                rgba(23,19,13,.95);
            box-shadow: 0 12px 32px rgba(0,0,0,.3);
        }

        .shop-detail-gallery-stage img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            transition: transform .7s ease;
        }

        .shop-detail-gallery-stage:hover img {
            transform: scale(1.04);
        }

        .shop-detail-stage-badge {
            position: absolute;
            top: 1.25rem;
            left: 1.25rem;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: .45rem .9rem;
            border-radius: 999px;
            background: var(--luxe-gold);
            color: #111;
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            box-shadow: 0 10px 18px rgba(212,175,55,.18);
        }

        .shop-detail-stage-control {
            position: absolute;
            z-index: 4;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,.14);
            background: rgba(0,0,0,.45);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
            backdrop-filter: blur(8px);
        }

        .shop-detail-stage-control:hover {
            background: var(--luxe-gold);
            color: #111;
            border-color: var(--luxe-gold);
        }

        .shop-detail-stage-control.prev {
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .shop-detail-stage-control.next {
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .shop-detail-stage-control.zoom {
            right: 1rem;
            bottom: 1rem;
        }

        .shop-detail-gallery-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .shop-detail-thumb-rail {
            display: flex;
            flex-direction: row;
            gap: .75rem;
            overflow-x: auto;
            padding: .25rem;
            scrollbar-width: none;
        }

        .shop-detail-thumb-rail::-webkit-scrollbar {
            display: none;
        }

        .shop-detail-thumb {
            position: relative;
            flex: 0 0 70px;
            width: 70px;
            aspect-ratio: 1 / 1;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(212,175,55,.12);
            background: rgba(35,31,23,.88);
            padding: 0;
            transition: .25s ease;
        }

        .shop-detail-thumb:hover,
        .shop-detail-thumb.active {
            border: 2px solid var(--luxe-gold);
            box-shadow: 0 0 12px rgba(212,175,55,.2);
        }

        .shop-detail-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: .25s ease;
        }

        .shop-detail-thumb:not(.active) img {
            opacity: .65;
        }

        .shop-detail-thumb:hover img,
        .shop-detail-thumb.active img {
            opacity: 1;
            transform: scale(1.05);
        }

        .shop-detail-thumb-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--luxe-gold);
            background: rgba(255,255,255,.03);
            font-size: 1.4rem;
        }

        .shop-detail-editorial {
            position: sticky;
            top: 100px;
            display: flex;
            flex-direction: column;
            gap: 1.35rem;
        }

        .shop-detail-kicker {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            color: var(--luxe-gold);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .shop-detail-title {
            color: #fff;
            font-size: clamp(2.3rem, 4vw, 4.25rem);
            line-height: .95;
            font-weight: 900;
            letter-spacing: -.06em;
            text-transform: uppercase;
            font-style: italic;
            margin: 0;
        }

        .shop-detail-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .shop-detail-price-block {
            display: flex;
            align-items: center;
            gap: .9rem;
            flex-wrap: wrap;
        }

        .shop-detail-price {
            color: var(--luxe-gold);
            font-size: 2.4rem;
            font-weight: 900;
            letter-spacing: -.04em;
        }

        .shop-detail-stock-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: .35rem .75rem;
            border-radius: 999px;
            background: rgba(16,185,129,.10);
            color: #34d399;
            border: 1px solid rgba(16,185,129,.18);
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .shop-detail-rating {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: var(--luxe-text-soft);
            font-size: .78rem;
            font-weight: 700;
        }

        .shop-detail-rating-stars {
            color: var(--luxe-gold);
            display: inline-flex;
            align-items: center;
            gap: .08rem;
        }

        .shop-detail-divider {
            height: 1px;
            background: linear-gradient(90deg, rgba(212,175,55,.18), transparent);
        }

        .shop-detail-field-label {
            color: var(--luxe-text-soft);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .shop-detail-inline-link {
            color: var(--luxe-gold);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .shop-detail-size-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .65rem;
        }

        .shop-detail-size-btn {
            min-height: 48px;
            border-radius: 12px;
            border: 1px solid rgba(212,175,55,.14);
            background: transparent;
            color: #fff;
            font-size: .9rem;
            font-weight: 800;
            transition: .2s ease;
        }

        .shop-detail-size-btn:hover {
            border-color: rgba(212,175,55,.4);
            color: var(--luxe-gold);
        }

        .shop-detail-size-btn.active {
            border: 2px solid var(--luxe-gold);
            background: rgba(212,175,55,.06);
            color: #fff;
        }

        .shop-detail-size-btn.disabled,
        .shop-detail-size-btn:disabled {
            opacity: .45;
            cursor: not-allowed;
            background: rgba(255,255,255,.03);
        }

        .shop-detail-swatches {
            display: flex;
            gap: .7rem;
            flex-wrap: wrap;
        }

        .shop-detail-swatch {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 1px solid rgba(212,175,55,.16);
            padding: 3px;
            background: transparent;
            transition: .2s ease;
        }

        .shop-detail-swatch:hover,
        .shop-detail-swatch.active {
            border: 2px solid var(--luxe-gold);
        }

        .shop-detail-swatch-core {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: block;
            box-shadow: inset 0 1px 2px rgba(0,0,0,.25);
        }

        .shop-detail-purchase-card {
            border-radius: 18px;
            border: 1px solid rgba(212,175,55,.12);
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                rgba(45,42,33,.86);
            box-shadow: 0 18px 36px rgba(0,0,0,.18);
            padding: 1.35rem;
        }

        .shop-detail-purchase-row {
            display: flex;
            gap: .85rem;
            align-items: center;
        }

        .shop-detail-qty {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            padding: .3rem;
            border-radius: 999px;
            border: 1px solid rgba(212,175,55,.12);
            background: rgba(35,31,23,.92);
        }

        .shop-detail-qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 0;
            background: transparent;
            color: #fff;
            transition: .2s ease;
        }

        .shop-detail-qty-btn:hover {
            background: rgba(255,255,255,.04);
            color: var(--luxe-gold);
        }

        .shop-detail-qty-value {
            min-width: 18px;
            text-align: center;
            font-size: .92rem;
            font-weight: 800;
        }

        .shop-detail-add-btn {
            flex: 1 1 auto;
            min-height: 52px;
            border-radius: 999px;
            border: 0;
            background: var(--luxe-gold);
            color: #111;
            font-size: .82rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            transition: .2s ease;
        }

        .shop-detail-add-btn:hover {
            filter: brightness(1.05);
            color: #111;
        }

        .shop-detail-icon-btn {
            width: 52px;
            height: 52px;
            flex: 0 0 auto;
            border-radius: 50%;
            border: 1px solid rgba(212,175,55,.14);
            background: transparent;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: .2s ease;
        }

        .shop-detail-icon-btn:hover {
            border-color: rgba(212,175,55,.34);
            color: var(--luxe-gold);
        }

        .shop-detail-micro-features {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            color: var(--luxe-text-soft);
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-top: .9rem;
        }

        .shop-detail-micro-feature {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
        }

        .shop-detail-story-title {
            color: #fff;
            font-size: .9rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            margin-bottom: .85rem;
        }

        .shop-detail-story,
        .shop-detail-story p,
        .shop-detail-story li {
            color: var(--luxe-text-soft);
            font-size: .94rem;
            line-height: 1.9;
        }

        .shop-detail-story ul,
        .shop-detail-story ol {
            padding-left: 1.1rem;
            margin-bottom: 0;
        }

        .shop-detail-story li {
            margin-bottom: .45rem;
        }

        .shop-detail-related-section {
            margin-top: 6rem;
        }

        .shop-detail-related-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .shop-detail-related-kicker {
            color: var(--luxe-gold);
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .2em;
            text-transform: uppercase;
            margin-bottom: .5rem;
        }

        .shop-detail-related-title {
            color: #fff;
            font-size: 2.5rem;
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -.04em;
            text-transform: uppercase;
            margin: 0;
        }

        .shop-detail-related-link {
            color: #fff;
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            text-decoration: none;
            border-bottom: 2px solid var(--luxe-gold);
            padding-bottom: .2rem;
        }

        .shop-detail-bento-card {
            position: relative;
            height: 100%;
            min-height: 320px;
            border-radius: 18px;
            overflow: hidden;
            background: rgba(35,31,23,.86);
            box-shadow: 0 18px 34px rgba(0,0,0,.18);
        }

        .shop-detail-bento-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .7s ease;
        }

        .shop-detail-bento-card:hover img {
            transform: scale(1.05);
        }

        .shop-detail-bento-overlay {
            position: absolute;
            inset: 0;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: end;
            background: linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(11,9,4,.88) 100%);
        }

        .shop-detail-bento-kicker {
            color: var(--luxe-gold);
            font-size: .62rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            margin-bottom: .4rem;
        }

        .shop-detail-bento-title {
            color: #fff;
            font-size: 1.35rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -.03em;
            text-transform: uppercase;
            margin-bottom: .6rem;
        }

        .shop-detail-bento-price {
            color: var(--luxe-gold);
            font-size: 1.05rem;
            font-weight: 900;
        }

        .shop-detail-bento-btn {
            width: fit-content;
            min-height: 34px;
            padding: .4rem .9rem;
            border-radius: 999px;
            border: 1px solid rgba(212,175,55,.18);
            background: transparent;
            color: #fff;
            font-size: .68rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            transition: .2s ease;
            text-decoration: none;
        }

        .shop-detail-bento-btn:hover {
            background: var(--luxe-gold);
            color: #111;
            border-color: var(--luxe-gold);
        }

        @media (max-width: 1199.98px) {
            .shop-detail-title {
                font-size: clamp(2rem, 4vw, 3.3rem);
            }
        }

        @media (max-width: 991.98px) {
            .shop-detail-editorial {
                position: static;
            }

            .shop-detail-gallery-stage {
                min-height: 420px;
                max-height: 550px;
            }

            .shop-detail-size-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .shop-detail-gallery-stage {
                min-height: 340px;
                max-height: 450px;
            }

            .shop-detail-thumb-rail {
                gap: .6rem;
            }

            .shop-detail-thumb {
                flex: 0 0 calc(25% - .45rem);
            }

            .shop-detail-price-row {
                align-items: start;
                flex-direction: column;
            }

            .shop-detail-purchase-row {
                flex-wrap: wrap;
            }

            .shop-detail-add-btn {
                min-width: 100%;
            }

            .shop-detail-related-head {
                flex-direction: column;
                align-items: start;
            }

            .shop-detail-related-title {
                font-size: 2rem;
            }

            .shop-detail-bento-card {
                min-height: 260px;
            }
        }

            .shop-detail-thumb {
                flex: 0 0 65px;
                width: 65px;
            }

            .shop-detail-gallery-stage {
                min-height: 320px;
                max-height: 400px;
            }
        }

        @media (min-width: 992px) {
            .shop-detail-gallery-container {
                flex-direction: row;
                align-items: flex-start;
                gap: .75rem;
            }

            .shop-detail-thumb-rail {
                flex-direction: column;
                flex: 0 0 140px;
                max-height: 600px;
                overflow-y: auto;
                overflow-x: hidden;
                padding: 0 .2rem 0 0;
            }

            .shop-detail-thumb {
                flex: 0 0 140px;
                width: 140px;
                margin-bottom: .65rem;
                border-radius: 18px;
            }
        }
    </style>
    @endpush

    @php
        $productItem = $product;

        $galleryItems = collect($currentGallery)->values();

        $mainImage = $currentGallery[$selectedMediaIndex]['url'] ?? $currentGallery[0]['url'] ?? null;

        $title = $productItem->title ?? 'Product';
        $price = ($product->currency ?? 'INR') . ' ' . ($computedPriceDisplay ?? number_format($product->base_price, 2));

        $badge = $productItem->is_featured ? 'Featured Edition' : null;

        $stockLabel = $availabilityLabel ?: ($inStock ? 'In Stock' : 'Out of Stock');

        $ratingValue = 0; // Placeholder
        $reviewCount = 0; // Placeholder

        $descriptionHtml = \Illuminate\Support\Str::markdown($product->description ?? '');

        $features = collect([]);

        $sizeOptions = collect([]);
        $colorOptions = collect([]);
        
        // Map variation groups to specific UI sections
        foreach(($variationGroups ?? []) as $group) {
            if (request()->is('*') /* placeholder logic to match groups */) {
                // We'll use the generic loop instead of splitting for now to remain safe
            }
        }
        
        $relatedProducts = collect([]);
        $collectionUrl = route('shop.index');
    @endphp

    <div class="shop-detail-main">
        <div class="row g-4 align-items-start">
            {{-- LEFT: GALLERY --}}
            <div class="col-lg-8">
                <div class="shop-detail-gallery-container">
                    <div class="shop-detail-gallery-stage">
                        @if(!empty($mainImage))
                            <img
                                src="{{ $mainImage }}"
                                alt="{{ $title }}"
                                id="shopDetailMainImage"
                            >
                        @else
                            <img
                                src="https://placehold.co/1000x1200/17130b/d4af37?text=Product"
                                alt="{{ $title }}"
                                id="shopDetailMainImage"
                            >
                        @endif

                        @if(!empty($badge))
                            <div class="shop-detail-stage-badge">{{ $badge }}</div>
                        @endif

                        @if($galleryItems->count() > 1)
                            <button type="button" class="shop-detail-stage-control prev" wire:click="selectMedia({{ ($selectedMediaIndex - 1 + $galleryItems->count()) % $galleryItems->count() }})" aria-label="Previous image">
                                <i class="mdi mdi-chevron-left"></i>
                            </button>

                            <button type="button" class="shop-detail-stage-control next" wire:click="selectMedia({{ ($selectedMediaIndex + 1) % $galleryItems->count() }})" aria-label="Next image">
                                <i class="mdi mdi-chevron-right"></i>
                            </button>
                        @endif

                        <button type="button" class="shop-detail-stage-control zoom" id="shopDetailZoomBtn" aria-label="Zoom image">
                            <i class="mdi mdi-magnify-plus-outline"></i>
                        </button>
                    </div>

                    <div class="shop-detail-thumb-rail" id="shopDetailThumbGrid">
                        @if($galleryItems->count())
                            @foreach($galleryItems as $index => $media)
                                @php
                                    $thumbUrl = $media['thumb_url'] ?? $media['url'] ?? null;
                                    $fullUrl = $media['url'] ?? null;
                                @endphp

                                <button
                                    type="button"
                                    class="shop-detail-thumb {{ (int)$selectedMediaIndex === (int)$index ? 'active' : '' }}"
                                    wire:click="selectMedia({{ $index }})"
                                    aria-label="View image {{ $index + 1 }}"
                                >
                                    <img src="{{ $thumbUrl ?: $fullUrl }}" alt="{{ $title }} thumbnail {{ $index + 1 }}">
                                </button>
                            @endforeach
                        @else
                            <div class="shop-detail-thumb"></div>
                            <div class="shop-detail-thumb"></div>
                            <div class="shop-detail-thumb"></div>
                            <div class="shop-detail-thumb shop-detail-thumb-placeholder">
                                <i class="mdi mdi-play-circle-outline"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RIGHT: EDITORIAL PANEL --}}
            <div class="col-lg-4">
                <div class="shop-detail-editorial">
                    <header>
                        @if(!empty($badge))
                            <div class="shop-detail-kicker mb-3">
                                <i class="mdi mdi-star-four-points-outline"></i>
                                <span>{{ $badge }}</span>
                            </div>
                        @endif

                        <h1 class="shop-detail-title">{{ $title }}</h1>

                        <div class="shop-detail-price-row mt-4">
                            <div class="shop-detail-price-block">
                                <div class="shop-detail-price">{{ $price }}</div>
                                <div class="shop-detail-stock-pill {{ $inStock ? '' : 'text-danger border-danger-subtle bg-danger-subtle' }}">
                                    {{ $stockLabel }}
                                </div>
                            </div>

                            <div class="shop-detail-rating">
                                <span class="shop-detail-rating-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="mdi {{ $i <= round((float)$ratingValue) ? 'mdi-star' : 'mdi-star-outline' }}"></i>
                                    @endfor
                                </span>
                                <span>({{ $reviewCount }} Reviews)</span>
                            </div>
                        </div>
                    </header>

                    <div class="shop-detail-divider"></div>

                    {{-- VARIATION GROUPS --}}
                    @foreach(($variationGroups ?? []) as $group)
                        @php
                            $groupId = $group['id'];
                            $groupName = $group['name'] ?? 'Option';
                            $presentation = $group['presentation_type'] ?? 'text';
                            $values = $group['values'] ?? [];
                            $selectedValueId = $selectedVariationValues[$groupId] ?? null;
                        @endphp

                        <section>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="shop-detail-field-label">{{ $groupName }}</div>

                                @if(strtolower($group['slug'] ?? '') === 'size')
                                    <button type="button" class="btn p-0 border-0 bg-transparent shop-detail-inline-link">
                                        Size Guide
                                    </button>
                                @endif
                            </div>

                            @if($presentation === 'color')
                                <div class="shop-detail-swatches">
                                    @foreach($values as $value)
                                        @php
                                            $valueId = $value['id'];
                                            $label = $value['caption'] ?? 'Option';
                                            $stock = (int)($value['stock_qty'] ?? 0);
                                            $disabled = $stock <= 0;
                                            $isActive = (string)$selectedValueId === (string)$valueId;
                                            $colorHex = $value['color_hex'] ?? '#ffffff';
                                        @endphp
                                        <button
                                            type="button"
                                            class="shop-detail-swatch {{ $isActive ? 'active' : '' }}"
                                            style="background-color: transparent;"
                                            title="{{ $label }}{{ $disabled ? ' (Out of stock)' : '' }}"
                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                            @if($disabled) disabled @endif
                                        >
                                            <span class="shop-detail-swatch-core" style="background-color: {{ $colorHex }};"></span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="shop-detail-size-grid">
                                    @foreach($values as $value)
                                        @php
                                            $valueId = $value['id'];
                                            $label = $value['caption'] ?? 'Option';
                                            $stock = (int)($value['stock_qty'] ?? 0);
                                            $disabled = $stock <= 0;
                                            $isActive = (string)$selectedValueId === (string)$valueId;
                                        @endphp

                                        <button
                                            type="button"
                                            class="shop-detail-size-btn {{ $isActive ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}"
                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                            @disabled($disabled)
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endforeach

                    {{-- PURCHASE CARD --}}
                    <section class="shop-detail-purchase-card">
                        <div class="shop-detail-purchase-row">
                            <div class="shop-detail-qty">
                                <button type="button" class="shop-detail-qty-btn" wire:click="decrementQuantity">
                                    <i class="mdi mdi-minus"></i>
                                </button>

                                <span class="shop-detail-qty-value">{{ $quantity ?? 1 }}</span>

                                <button type="button" class="shop-detail-qty-btn" wire:click="incrementQuantity">
                                    <i class="mdi mdi-plus"></i>
                                </button>
                            </div>

                            <button type="button" class="shop-detail-add-btn" wire:click="addToCart" wire:loading.attr="disabled" @disabled(!$inStock)>
                                <span wire:loading.remove wire:target="addToCart">
                                    {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                                </span>
                                <span wire:loading wire:target="addToCart">
                                    Adding...
                                </span>
                            </button>

                            <button type="button" class="shop-detail-icon-btn" aria-label="Add to wishlist">
                                <i class="mdi mdi-heart-outline"></i>
                            </button>
                        </div>

                        <div class="shop-detail-micro-features">
                            <div class="shop-detail-micro-feature">
                                <i class="mdi mdi-truck-fast-outline"></i>
                                <span>Free Express Shipping</span>
                            </div>

                            <div class="shop-detail-micro-feature">
                                <i class="mdi mdi-shield-check-outline"></i>
                                <span>Authentic Heritage Gear</span>
                            </div>
                        </div>
                    </section>

                    {{-- STORY / DESCRIPTION --}}
                    <section>
                        <div class="shop-detail-story-title">The Craftsmanship</div>

                        <div class="shop-detail-story">
                            {!! $descriptionHtml !!}
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    {{-- RELATED PRODUCTS --}}
    @if($relatedProducts->count())
    <section class="shop-detail-related-section">
        <div class="shop-detail-related-head">
            <div>
                <div class="shop-detail-related-kicker">Complete the Kit</div>
                <h2 class="shop-detail-related-title">Curated Essentials</h2>
            </div>

            <a href="{{ $collectionUrl }}" class="shop-detail-related-link">View Collection</a>
        </div>

        <div class="row g-4">
            @foreach($relatedProducts->take(3) as $index => $related)
                @php
                    $relatedTitle = $related->title ?? 'Related Product';
                    $relatedPrice = ($product->currency ?? 'INR') . ' ' . number_format($related->base_price, 2);
                    $relatedImage = $related->images->first()->url ?? 'https://placehold.co/800x800/17130b/d4af37?text=Product';
                    $relatedUrl = route('shop.show', $related->slug);
                    $relatedBadge = $index === 0 ? 'Legacy Series' : null;
                @endphp

                @if($index === 0)
                    <div class="col-md-6">
                        <article class="shop-detail-bento-card">
                            <img src="{{ $relatedImage }}" alt="{{ $relatedTitle }}">

                            <div class="shop-detail-bento-overlay">
                                @if(!empty($relatedBadge))
                                    <div class="shop-detail-bento-kicker">{{ $relatedBadge }}</div>
                                @endif

                                <div class="shop-detail-bento-title">{{ $relatedTitle }}</div>

                                <a href="{{ $relatedUrl }}" class="shop-detail-bento-btn">Details</a>
                            </div>
                        </article>
                    </div>
                @else
                    <div class="col-md-3">
                        <article class="shop-detail-bento-card">
                            <img src="{{ $relatedImage }}" alt="{{ $relatedTitle }}">

                            <div class="shop-detail-bento-overlay">
                                <div class="shop-detail-bento-title fs-5">{{ $relatedTitle }}</div>
                                <div class="shop-detail-bento-price">{{ $relatedPrice }}</div>
                            </div>
                        </article>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
    @endif
</div>

