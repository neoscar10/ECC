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
            min-height: 850px;
            height: clamp(850px, 85vh, 1000px);
            max-height: 1000px;
            border-radius: 22px;
            overflow: hidden;
            border: 1px solid rgba(212,175,55,.14);
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                rgba(23,19,13,.95);
            box-shadow: 0 12px 32px rgba(0,0,0,.3);
            display: flex;
            align-items: stretch;
            padding: 1.5rem;
        }

        .shop-detail-stage-image-wrap {
            flex-grow: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            overflow: hidden;
        }

        .shop-detail-stage-thumbs {
            flex-shrink: 0;
            width: 110px;
            height: 100%;
            padding-left: 1.25rem;
            display: flex;
            flex-direction: column;
        }

        .shop-detail-stage-image-wrap img#shopDetailMainImage {
            width: 100%;
            height: 100%;
            max-width: 98%;
            max-height: 98%;
            object-fit: contain;
            display: block;
            margin: auto;
            transition: transform .7s ease;
        }

        .shop-detail-stage-image-wrap:hover img#shopDetailMainImage {
            transform: scale(1.04);
        }

        @media (max-width: 991.98px) {
            .shop-detail-gallery-stage {
                min-height: 540px;
                height: auto;
                max-height: 640px;
            }

            .shop-detail-gallery-stage img {
                width: auto;
                height: 100%;
                max-width: 98%;
                max-height: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .shop-detail-gallery-stage {
                min-height: 400px;
                height: auto;
                max-height: 500px;
            }

            .shop-detail-gallery-stage img {
                width: auto;
                height: 100%;
                max-width: 100%;
                max-height: 100%;
            }
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
            flex-direction: column;
            gap: 1rem;
            height: 100%;
            overflow-y: auto;
            scrollbar-width: none;
        }

        .shop-detail-thumb-rail-mobile {
            display: flex;
            gap: .85rem;
            overflow-x: auto;
            padding: 1rem 0;
            scrollbar-width: none;
        }

        .shop-detail-thumb-rail::-webkit-scrollbar {
            display: none;
        }

        .shop-detail-thumb {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(212,175,55,.14);
            background: #120f08;
            aspect-ratio: 1 / 1;
            padding: 0;
            transition: .2s ease;
            box-shadow: 0 8px 16px rgba(0,0,0,.16);
            width: 100%;
            flex: 0 0 auto;
        }

        .shop-detail-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: .25s ease;
            opacity: .74;
        }

        .shop-detail-thumb:hover img,
        .shop-detail-thumb.active img {
            opacity: 1;
            transform: scale(1.03);
        }

        .shop-detail-thumb.active {
            border-color: var(--luxe-gold);
            box-shadow: 0 0 0 2px rgba(212,175,55,.12);
        }

        .shop-detail-thumb-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--luxe-gold);
            background: rgba(255,255,255,.03);
            font-size: 1.4rem;
        }

        .shop-detail-sidebar {
            position: sticky;
            top: 100px;
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .shop-detail-side-card,
        .shop-detail-cert-card {
            border-radius: 22px;
            border: 1px solid rgba(212,175,55,.14);
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                rgba(16,13,7,.78);
            box-shadow: 0 18px 36px rgba(0,0,0,.22);
            padding: 1.5rem;
        }

        .shop-detail-side-kicker {
            color: var(--luxe-text-soft);
            font-size: .7rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            margin-bottom: .8rem;
        }

        .shop-detail-cert-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: .35rem;
        }

        .shop-detail-cert-card i {
            color: var(--luxe-gold);
            font-size: 1.4rem;
        }

        .shop-detail-cert-title {
            color: #fff;
            font-size: .8rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .shop-detail-cert-subtitle {
            color: var(--luxe-text-soft);
            font-size: .7rem;
            line-height: 1.5;
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
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 900;
            letter-spacing: -.02em;
            line-height: 1.1;
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
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -.03em;
            margin: 0 0 1.25rem;
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            text-transform: uppercase;
        }

        .shop-detail-story-title::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 24px;
            border-radius: 999px;
            background: var(--luxe-gold);
            flex: 0 0 auto;
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
                flex: 0 0 auto;
                width: 100%;
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
                flex: 0 0 auto;
                max-height: 450px;
                overflow-y: auto;
                overflow-x: hidden;
                padding: 0 .4rem 0 0;
            }

            .shop-detail-thumb {
                flex: 0 0 auto;
                width: 100%;
                aspect-ratio: 1 / 1;
                margin-bottom: .65rem;
                border-radius: 12px;
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
        {{-- MAIN CONTENT GRID (9/3 Split for the entire layout) --}}
        <div class="row g-4 g-xl-5 mb-5">
            {{-- LEFT: PRODUCT CONTENT & GALLERY STAGE (9/12) --}}
            <div class="col-lg-9">
                
                {{-- GALLERY STAGE (Includes Main Image & Desktop Thumbnails) --}}
                <div class="shop-detail-gallery-stage mb-5">
                    
                    {{-- MAIN IMAGE WRAPPER --}}
                    <div class="shop-detail-stage-image-wrap">
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

                    {{-- DESKTOP THUMBNAILS (Inside the Stage card) --}}
                    <div class="shop-detail-stage-thumbs d-none d-lg-flex">
                        <div class="shop-detail-thumb-rail scroll-luxury w-100" id="shopDetailThumbGrid">
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
                            @endif
                        </div>
                    </div>
                </div>

                {{-- MOBILE THUMBS (Hidden on Desktop) --}}
                <div class="col-12 d-lg-none mb-5">
                    <div class="shop-detail-thumb-rail-mobile scroll-luxury">
                        @foreach($galleryItems as $index => $media)
                            @php
                                $thumbUrl = $media['thumb_url'] ?? $media['url'] ?? null;
                            @endphp
                            <button
                                type="button"
                                class="shop-detail-thumb {{ (int)$selectedMediaIndex === (int)$index ? 'active' : '' }}"
                                wire:click="selectMedia({{ $index }})"
                            >
                                <img src="{{ $thumbUrl }}" alt="thumb">
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- PRODUCT INFO BELOW GALLERY --}}
                <div class="shop-detail-info-block mt-lg-5 mt-4">
                    <header class="mb-3">
                        @if(!empty($badge))
                            <div class="shop-detail-kicker mb-2">
                                <i class="mdi mdi-star-four-points-outline"></i>
                                <span>{{ $badge }}</span>
                            </div>
                        @endif

                        <h1 class="shop-detail-title">{{ $title }}</h1>
                    </header>

                    <div class="shop-detail-price-row mb-4">
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

                    <div class="shop-detail-divider mb-4" style="background: linear-gradient(90deg, rgba(212,175,55,.18), transparent); height: 1px;"></div>

                    {{-- VARIATION GROUPS / SPECIFICATIONS --}}
                    @if(count($variationGroups ?? []))
                        <div class="shop-detail-spec-section mb-5">
                            <div class="shop-detail-story-title mb-4">
                                <span style="color: var(--luxe-gold); margin-right: .5rem;">|</span>Specifications
                            </div>

                            <div class="row g-4 leading-relaxed">
                                @foreach($variationGroups as $group)
                                    @php
                                        $groupId = $group['id'];
                                        $groupName = $group['name'] ?? 'Option';
                                        $presentation = $group['presentation_type'] ?? 'text';
                                        $values = $group['values'] ?? [];
                                        $selectedValueId = $selectedVariationValues[$groupId] ?? null;
                                    @endphp

                                    <div class="col-md-6">
                                        <section>
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="shop-detail-field-label text-white-50 opacity-75 small uppercase font-bold">{{ $groupName }}</div>

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
                                                            // Combination-aware availability
                                                            $isAvailable = in_array((int)$valueId, $availableOptions[$groupId] ?? []);
                                                            $disabled = !$isAvailable;
                                                            $isActive = (string)$selectedValueId === (string)$valueId;
                                                            $colorHex = $value['color_hex'] ?? '#ffffff';
                                                        @endphp
                                                        <button
                                                            type="button"
                                                            class="shop-detail-swatch {{ $isActive ? 'active' : '' }}"
                                                            style="background-color: transparent; {{ $disabled ? 'opacity: 0.3; cursor: not-allowed;' : '' }}"
                                                            title="{{ $label }}{{ $disabled ? ' (Unavailable)' : '' }}"
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
                                                            // Combination-aware availability
                                                            $isAvailable = in_array((int)$valueId, $availableOptions[$groupId] ?? []);
                                                            $disabled = !$isAvailable;
                                                            $isActive = (string)$selectedValueId === (string)$valueId;
                                                        @endphp

                                                        <button
                                                            type="button"
                                                            class="shop-detail-size-btn {{ $isActive ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}"
                                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                                            @disabled($disabled)
                                                            title="{{ $disabled ? 'Unavailable for current selection' : '' }}"
                                                        >
                                                            {{ $label }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </section>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="shop-detail-divider mb-4" style="background: linear-gradient(90deg, rgba(212,175,55,.18), transparent); height: 1px;"></div>

                    <div class="shop-detail-story-title">
                        <span style="color: var(--luxe-gold); margin-right: .5rem;">|</span>The Craftsmanship
                    </div>
                    <div class="shop-detail-story">
                        {!! $descriptionHtml !!}
                    </div>
                </div>
            </div>

            {{-- RIGHT: SIDEBAR (3/12) --}}
            <div class="col-lg-3">
                <div class="shop-detail-sidebar">
                    {{-- SIDE CARD (Mirroring Archive Side Card) --}}
                    <div class="shop-detail-side-card">
                        <div class="shop-detail-side-kicker">ADD TO CART</div>
                        
                        {{-- PURCHASE ACTIONS --}}
                        <div class="shop-detail-purchase-row flex-column gap-3">
                            <div class="shop-detail-qty w-100 d-flex justify-content-between p-2" style="background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                                <button type="button" class="shop-detail-qty-btn" wire:click="decrementQuantity">
                                    <i class="mdi mdi-minus"></i>
                                </button>

                                <span class="shop-detail-qty-value">{{ $quantity ?? 1 }}</span>

                                <button type="button" class="shop-detail-qty-btn" wire:click="incrementQuantity">
                                    <i class="mdi mdi-plus"></i>
                                </button>
                            </div>

                            <button type="button" class="shop-detail-add-btn w-100 py-3" wire:click="addToCart" wire:loading.attr="disabled" @disabled(!$inStock)>
                                <span wire:loading.remove wire:target="addToCart">
                                    {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                                </span>
                                <span wire:loading wire:target="addToCart">
                                    Adding...
                                </span>
                            </button>
                        </div>
                    </div>

                    {{-- CERT CARD --}}
                    <div class="shop-detail-cert-card mt-3">
                        <i class="mdi mdi-seal-variant"></i>
                        <div class="shop-detail-cert-title">Certified & Authentic</div>
                        <div class="shop-detail-cert-subtitle">Guaranteed heritage gear from ECC.</div>
                    </div>

                    <div class="shop-detail-micro-features mt-3">
                        <div class="shop-detail-micro-feature">
                            <i class="mdi mdi-truck-fast-outline"></i>
                            <span>Free Express Shipping</span>
                        </div>
                    </div>
                </div>
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
    {{-- PREMIUM CART SUCCESS TOAST --}}
    <div
        x-data="{ show: false, name: '' }"
        @item-added-to-cart.window="show = true; setTimeout(() => show = false, 6000)"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-10 scale-95"
        class="fixed-bottom p-4 d-flex justify-content-center"
        style="z-index: 9999; pointer-events: none;"
    >
        <div class="cart-success-toast p-1" style="pointer-events: auto;">
            <div class="d-flex align-items-center gap-3 px-3 py-2">
                <div class="toast-icon-wrap">
                    <i class="mdi mdi-check-circle-outline"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="toast-title">Added to Collection</div>
                    <div class="toast-msg">{{ $title }} has been reserved.</div>
                </div>
                <div class="d-flex gap-2 ms-3">
                    <a href="{{ route('shop.cart') }}" class="toast-btn primary">View Cart</a>
                    <button type="button" @click="show = false" class="toast-close-btn">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        [x-cloak] { display: none !important; }
        
        .cart-success-toast {
            background: linear-gradient(135deg, rgba(23, 19, 11, 0.98), rgba(35, 30, 20, 0.95));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.4);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(212, 175, 55, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.05);
            min-width: 340px;
            max-width: 90vw;
            animation: toast-glow 3s infinite alternate;
        }

        @keyframes toast-glow {
            from { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(212, 175, 55, 0.1); }
            to { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 15px 2px rgba(212, 175, 55, 0.15); }
        }

        .toast-icon-wrap {
            width: 44px;
            height: 44px;
            background: rgba(212, 175, 55, 0.15);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--luxe-gold);
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .toast-title {
            color: #fff;
            font-weight: 800;
            font-size: 0.88rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .toast-msg {
            color: var(--luxe-text-soft);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .toast-btn {
            height: 38px;
            padding: 0 1.25rem;
            border-radius: 14px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .toast-btn.primary {
            background: var(--luxe-gold);
            color: #111;
            border: none;
        }

        .toast-btn.primary:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
            color: #111;
        }

        .toast-close-btn {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .toast-close-btn:hover {
            background: rgba(255,255,255,0.1);
            color: var(--luxe-gold);
        }
    </style>
    @endpush
</div>

