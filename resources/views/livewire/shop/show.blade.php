<div>
    @push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;600;700;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        /* ─── Design Tokens ─────────────────────────────── */
        :root {
            --hg-primary:          #755a24;
            --hg-primary-light:    #c5a365;
            --hg-primary-faint:    rgba(117,90,36,.08);
            --hg-primary-border:   rgba(117,90,36,.22);
            --hg-surface:          #fbf9f9;
            --hg-surface-low:      #f5f3f3;
            --hg-surface-container:#efeded;
            --hg-surface-high:     #e9e8e7;
            --hg-surface-highest:  #e3e2e2;
            --hg-on-surface:       #1b1c1c;
            --hg-on-surface-var:   #4d463a;
            --hg-outline:          #7f7668;
            --hg-outline-var:      #d1c5b5;
            --hg-on-primary:       #ffffff;
            --hg-on-primary-cont:  #503904;
            --hg-font-body:        'Hanken Grotesk', system-ui, sans-serif;
            --hg-font-mono:        'JetBrains Mono', monospace;
        }

        /* ─── Base ─────────────────────────────────────── */
        .sdp { font-family: var(--hg-font-body); background: var(--hg-surface); color: var(--hg-on-surface); }

        /* ─── Gallery Grid ──────────────────────────────── */
        .sdp-gallery-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .sdp-gallery-cell {
            border-radius: 0.5rem;
            overflow: hidden;
            background: #fff;
            cursor: zoom-in;
            position: relative;
        }
        .sdp-gallery-cell img {
            width: 100%;
            aspect-ratio: 3/4;
            object-fit: cover;
            display: block;
            transition: transform .5s ease;
        }
        .sdp-gallery-cell:hover img { transform: scale(1.03); }
        .sdp-gallery-cell.active-thumb { outline: 2px solid var(--hg-primary); }
        .sdp-badge {
            position: absolute;
            top: .85rem; left: .85rem;
            background: var(--hg-primary);
            color: var(--hg-on-primary);
            font-family: var(--hg-font-mono);
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            padding: .3rem .75rem;
            border-radius: 999px;
            z-index: 2;
        }
        .sdp-show-more {
            display: flex;
            justify-content: center;
            margin-top: .5rem;
        }
        .sdp-show-more-btn {
            padding: .65rem 2.25rem;
            border: 1px solid var(--hg-outline-var);
            border-radius: 999px;
            background: transparent;
            color: var(--hg-on-surface);
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .sdp-show-more-btn:hover { background: var(--hg-surface-container); }

        /* ─── Sticky Sidebar ────────────────────────────── */
        .sdp-sidebar {
            position: sticky;
            top: 90px;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .sdp-sidebar-inner { display: flex; flex-direction: column; gap: 1.5rem; }

        /* ─── Breadcrumbs ───────────────────────────────── */
        .sdp-breadcrumb {
            display: flex;
            gap: .4rem;
            align-items: center;
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            letter-spacing: .05em;
            color: var(--hg-on-surface-var);
        }
        .sdp-breadcrumb a:hover { color: var(--hg-primary); }
        .sdp-breadcrumb span.sep { opacity: .5; }

        /* ─── Header ────────────────────────────────────── */
        .sdp-kicker {
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--hg-on-surface-var);
        }
        .sdp-title {
            font-size: clamp(1.6rem, 3vw, 2rem);
            line-height: 1.1;
            font-weight: 700;
            letter-spacing: -.025em;
            text-transform: uppercase;
            color: var(--hg-on-surface);
            margin: 0;
        }
        .sdp-price {
            font-family: var(--hg-font-body);
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--hg-on-surface);
            line-height: 1.3;
        }
        .sdp-price-note {
            font-family: var(--hg-font-mono);
            font-size: .62rem;
            letter-spacing: .03em;
            color: var(--hg-on-surface-var);
            margin-top: .15rem;
        }
        .sdp-stock-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            font-family: var(--hg-font-mono);
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .sdp-stock-pill.in-stock {
            background: rgba(16,185,129,.1);
            color: #059669;
            border: 1px solid rgba(16,185,129,.2);
        }
        .sdp-stock-pill.out-stock {
            background: rgba(186,26,26,.1);
            color: #b91c1c;
            border: 1px solid rgba(186,26,26,.2);
        }
        .sdp-stock-pill.select-opts {
            background: var(--hg-surface-high);
            color: var(--hg-on-surface-var);
            border: 1px solid var(--hg-outline-var);
        }

        /* ─── Variation Selectors ───────────────────────── */
        .sdp-group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: .6rem;
        }
        .sdp-group-label {
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--hg-on-surface);
        }
        .sdp-size-guide-link {
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--hg-primary);
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 3px;
            padding: 0;
        }
        .sdp-size-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: .5rem;
        }
        .sdp-size-btn {
            height: 48px;
            border-radius: 0.5rem;
            border: 1px solid var(--hg-outline-var);
            background: var(--hg-surface);
            color: var(--hg-on-surface-var);
            font-family: var(--hg-font-mono);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            transition: border-color .15s, color .15s, background .15s;
        }
        .sdp-size-btn:hover:not(:disabled) { border-color: var(--hg-primary); color: var(--hg-primary); }
        .sdp-size-btn.active { border: 2px solid var(--hg-primary); color: var(--hg-primary); background: var(--hg-primary-faint); font-weight: 900; }
        .sdp-size-btn:disabled { opacity: .4; cursor: not-allowed; }

        /* Color swatches */
        .sdp-swatches { display: flex; gap: .6rem; flex-wrap: wrap; }
        .sdp-swatch {
            width: 34px; height: 34px;
            border-radius: 50%;
            border: 2px solid transparent;
            padding: 3px;
            background: transparent;
            cursor: pointer;
            transition: border-color .15s;
        }
        .sdp-swatch.active, .sdp-swatch:hover:not(:disabled) { border-color: var(--hg-primary); }
        .sdp-swatch:disabled { opacity: .35; cursor: not-allowed; }
        .sdp-swatch-core { width: 100%; height: 100%; border-radius: 50%; display: block; box-shadow: inset 0 1px 3px rgba(0,0,0,.25); }

        /* Image swatches */
        .sdp-swatch-image {
            width: 48px; height: 60px;
            border-radius: 6px;
            border: 2px solid transparent;
            overflow: hidden;
            cursor: pointer;
            transition: border-color .15s;
        }
        .sdp-swatch-image.active, .sdp-swatch-image:hover:not(:disabled) { border-color: var(--hg-primary); }
        .sdp-swatch-image img { width: 100%; height: 100%; object-fit: cover; }

        /* ─── Quantity ───────────────────────────────────── */
        .sdp-qty {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .3rem;
            border-radius: 999px;
            border: 1px solid var(--hg-outline-var);
            background: var(--hg-surface-low);
        }
        .sdp-qty-btn {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--hg-on-surface);
            font-size: 1.1rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s;
        }
        .sdp-qty-btn:hover { background: var(--hg-surface-container); color: var(--hg-primary); }
        .sdp-qty-value {
            min-width: 2rem;
            text-align: center;
            font-family: var(--hg-font-mono);
            font-size: .88rem;
            font-weight: 700;
        }

        /* ─── Action Buttons ────────────────────────────── */
        .sdp-action-row { display: flex; gap: .65rem; align-items: stretch; }
        .sdp-add-btn {
            flex: 1;
            min-height: 56px;
            border-radius: 0.5rem;
            border: none;
            background: var(--hg-primary-light);
            color: var(--hg-on-primary-cont);
            font-family: var(--hg-font-mono);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            transition: background .2s, color .2s;
        }
        .sdp-add-btn:hover:not(:disabled) { background: var(--hg-primary); color: var(--hg-on-primary); }
        .sdp-add-btn:disabled { opacity: .55; cursor: not-allowed; }
        .sdp-wish-btn {
            width: 56px;
            min-height: 56px;
            border-radius: 0.5rem;
            border: 1px solid var(--hg-outline-var);
            background: var(--hg-surface);
            color: var(--hg-on-surface-var);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: border-color .2s, color .2s;
        }
        .sdp-wish-btn:hover { border-color: var(--hg-primary); color: var(--hg-primary); }
        .sdp-wish-btn .material-symbols-outlined { font-size: 22px; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }

        /* ─── Info tip ───────────────────────────────────── */
        .sdp-info-tip {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .75rem 1rem;
            border-radius: 0.25rem;
            background: var(--hg-surface-low);
            border: 1px solid var(--hg-outline-var);
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            letter-spacing: .03em;
            color: var(--hg-on-surface-var);
            line-height: 1.5;
        }
        .sdp-info-tip .material-symbols-outlined { font-size: 16px; flex-shrink: 0; margin-top: 1px; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }

        /* ─── Value Props ────────────────────────────────── */
        .sdp-value-props {
            display: flex;
            flex-direction: column;
            gap: .75rem;
            padding: 1.25rem 0;
            border-top: 1px solid var(--hg-outline-var);
            border-bottom: 1px solid var(--hg-outline-var);
        }
        .sdp-value-prop {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            letter-spacing: .03em;
            color: var(--hg-on-surface-var);
        }
        .sdp-value-prop .material-symbols-outlined { font-size: 18px; color: var(--hg-primary); flex-shrink: 0; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }

        /* ─── Accordions ─────────────────────────────────── */
        .sdp-accordion { display: flex; flex-direction: column; }
        .sdp-accordion-item { border-bottom: 1px solid var(--hg-outline-var); }
        .sdp-accordion-item summary {
            list-style: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            cursor: pointer;
            font-family: var(--hg-font-body);
            font-size: .9rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--hg-on-surface);
            user-select: none;
        }
        .sdp-accordion-item summary::-webkit-details-marker { display: none; }
        .sdp-accordion-item summary .material-symbols-outlined { font-size: 20px; transition: transform .2s; color: var(--hg-on-surface-var); font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }
        .sdp-accordion-item[open] summary .material-symbols-outlined { transform: rotate(180deg); }
        .sdp-accordion-body {
            padding-bottom: 1rem;
            color: var(--hg-on-surface-var);
            font-size: .92rem;
            line-height: 1.7;
        }
        .sdp-accordion-body ul { padding-left: 1.2rem; }
        .sdp-accordion-body li { margin-bottom: .3rem; }
        .sdp-care-row { display: flex; align-items: center; gap: .75rem; margin-bottom: .5rem; }
        .sdp-care-row .material-symbols-outlined { font-size: 18px; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }

        /* ─── Related / Complete The Look ───────────────── */
        .sdp-related-section { border-top: 1px solid var(--hg-outline-var); padding: 3.5rem 0; }
        .sdp-related-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        @media (min-width: 768px) { .sdp-related-grid { grid-template-columns: repeat(4, 1fr); } }
        .sdp-related-card { display: flex; flex-direction: column; gap: .65rem; text-decoration: none; color: inherit; }
        .sdp-related-card:hover .sdp-related-img { transform: scale(1.05); }
        .sdp-related-img-wrap {
            background: var(--hg-surface-low);
            aspect-ratio: 3/4;
            overflow: hidden;
            border-radius: 0.25rem;
            position: relative;
        }
        .sdp-related-img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s ease; }
        .sdp-related-title {
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--hg-on-surface);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sdp-related-price {
            font-family: var(--hg-font-mono);
            font-size: .68rem;
            color: var(--hg-on-surface-var);
            margin-top: .1rem;
        }

        /* ─── Mobile Adjustments ────────────────────────── */
        @media (max-width: 767px) {
            .sdp-gallery-grid { grid-template-columns: 1fr; }
            .sdp-size-grid { grid-template-columns: repeat(4, 1fr); }
            .sdp-main-grid { grid-template-columns: 1fr !important; }
        }
        @media (min-width: 768px) {
            .sdp-gallery-grid { grid-template-columns: 1fr 1fr; }
            .sdp-main-grid { grid-template-columns: 1fr minmax(0, 390px) !important; gap: 3rem !important; align-items: start !important; }
        }
    </style>
    @endpush

    @php
        $galleryItems    = collect($currentGallery)->values();
        $title           = $product->title ?? 'Product';
        $badge           = $product->is_featured ? 'Featured Edition' : null;
        $priceDisplay    = ($product->currency ?? 'INR') . ' ' . ($computedPriceDisplay ?? number_format($product->base_price, 2));
        $stockLabel      = $availabilityLabel ?: ($inStock ? 'In Stock' : 'Out of Stock');
        $stockClass      = $inStock ? 'in-stock' : ($availabilityLabel === 'Select options' ? 'select-opts' : 'out-stock');
        $descriptionHtml = \Illuminate\Support\Str::markdown($product->description ?? '');
        $collectionUrl   = route('shop.index');

        // Breadcrumbs
        $primaryCategory = $product->categories->first();
        $parentCategory  = $primaryCategory?->parent;

        // Show "Show More" if gallery has more than 4 images
        $initialImages = $galleryItems->take(4);
        $extraImages   = $galleryItems->slice(4);

        // Variation groups
        $sizeGroups  = collect($variationGroups)->filter(fn($g) => stripos($g['name'] ?? '', 'size') !== false);
        $colorGroups = collect($variationGroups)->filter(fn($g) => ($g['presentation_type'] ?? '') === 'color');
        $imageGroups = collect($variationGroups)->filter(fn($g) => ($g['presentation_type'] ?? '') === 'image');
        $textGroups  = collect($variationGroups)->filter(fn($g) => ($g['presentation_type'] ?? 'text') === 'text' && stripos($g['name'] ?? '', 'size') === false);
    @endphp

    <div class="sdp">
        {{-- ═══════════════════════════════════════════════════════════
             MAIN LAYOUT
             ═══════════════════════════════════════════════════════════ --}}
        <main style="max-width: 1280px; margin: 0 auto; padding: 1rem 1.5rem 5rem;">

            {{-- Mobile Breadcrumb --}}
            <nav class="sdp-breadcrumb d-md-none mb-3">
                <a href="{{ route('shop.index') }}">Shop</a>
                @if($parentCategory)
                    <span class="sep">/</span>
                    <a href="{{ route('shop.index') }}?cat={{ $parentCategory->id }}">{{ $parentCategory->name }}</a>
                @endif
                @if($primaryCategory)
                    <span class="sep">/</span>
                    <span style="color: var(--hg-on-surface); font-weight: 700;">{{ $primaryCategory->name }}</span>
                @endif
            </nav>

            <div style="display: grid; gap: 1.5rem;" class="sdp-main-grid">

                {{-- ── LEFT: Gallery Column ───────────────────── --}}
                <div>
                    {{-- 2×N Gallery Grid --}}
                    <div class="sdp-gallery-grid" id="sdpGallery" x-data="{ showAll: false }">
                        @foreach($initialImages as $index => $media)
                            <div class="sdp-gallery-cell {{ (int)$selectedMediaIndex === (int)$index ? 'active-thumb' : '' }}"
                                 wire:click="selectMedia({{ $index }})">
                                @if($index === 0 && $badge)
                                    <span class="sdp-badge">{{ $badge }}</span>
                                @endif
                                <img src="{{ $media['url'] }}" alt="{{ $title }} — View {{ $index + 1 }}" loading="{{ $index < 2 ? 'eager' : 'lazy' }}">
                            </div>
                        @endforeach

                        @if($extraImages->count())
                            @foreach($extraImages as $index => $media)
                                @php $realIndex = $index + 4; @endphp
                                <div class="sdp-gallery-cell {{ (int)$selectedMediaIndex === (int)$realIndex ? 'active-thumb' : '' }}"
                                     wire:click="selectMedia({{ $realIndex }})"
                                     x-show="showAll"
                                     x-cloak>
                                    <img src="{{ $media['url'] }}" alt="{{ $title }} — View {{ $realIndex + 1 }}" loading="lazy">
                                </div>
                            @endforeach

                            <div class="sdp-show-more" x-show="!showAll" style="grid-column: 1 / -1;">
                                <button class="sdp-show-more-btn" @click="showAll = true">Show More</button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── RIGHT: Sticky Sidebar ──────────────────── --}}
                <div class="sdp-sidebar" id="sdpSidebar">
                    <div class="sdp-sidebar-inner">

                        {{-- Desktop Breadcrumb --}}
                        <nav class="sdp-breadcrumb d-none d-md-flex">
                            <a href="{{ route('shop.index') }}">Shop</a>
                            @if($parentCategory)
                                <span class="sep">/</span>
                                <a href="{{ route('shop.index') }}?cat={{ $parentCategory->id }}">{{ $parentCategory->name }}</a>
                            @endif
                            @if($primaryCategory)
                                <span class="sep">/</span>
                                <span style="color: var(--hg-on-surface); font-weight: 700;">{{ $primaryCategory->name }}</span>
                            @endif
                        </nav>

                        {{-- Header: Kicker, Title, Price --}}
                        <div>
                            @if($primaryCategory)
                                <div class="sdp-kicker mb-1">{{ $primaryCategory->name }}</div>
                            @endif
                            <h1 class="sdp-title">{{ $title }}</h1>
                            <div style="margin-top: .75rem; display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;">
                                <div class="sdp-price">{{ $priceDisplay }}</div>
                                <span class="sdp-stock-pill {{ $stockClass }}">
                                    @if($inStock && !$availabilityLabel)
                                        <span class="material-symbols-outlined" style="font-size:13px; font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">circle</span>
                                    @endif
                                    {{ $stockLabel }}
                                </span>
                            </div>
                            <p class="sdp-price-note">Inclusive of all taxes.</p>
                        </div>

                        {{-- ── Variation Selectors ───────────────── --}}
                        @if(count($variationGroups ?? []))
                            <div style="display: flex; flex-direction: column; gap: 1.25rem;">

                                {{-- Text/Size groups --}}
                                @foreach($variationGroups as $group)
                                    @php
                                        $groupId       = $group['id'];
                                        $groupName     = $group['name'] ?? 'Option';
                                        $presentation  = $group['presentation_type'] ?? 'text';
                                        $values        = $group['values'] ?? [];
                                        $selectedVId   = $selectedVariationValues[$groupId] ?? null;
                                        $hasSizeName   = stripos($groupName, 'size') !== false;
                                    @endphp

                                    <div>
                                        <div class="sdp-group-header">
                                            <span class="sdp-group-label">{{ $groupName }}</span>
                                            @if(isset($sizeGuide) && $hasSizeName)
                                                <button type="button" class="sdp-size-guide-link"
                                                        data-bs-toggle="offcanvas" data-bs-target="#sizeGuideOffcanvas">
                                                    Size Guide
                                                </button>
                                            @endif
                                        </div>

                                        @if($presentation === 'color')
                                            <div class="sdp-swatches">
                                                @foreach($values as $value)
                                                    @php
                                                        $valueId     = $value['id'];
                                                        $isAvailable = in_array((int)$valueId, $availableOptions[$groupId] ?? []);
                                                        $isActive    = (string)$selectedVId === (string)$valueId;
                                                        $colorHex    = $value['color_hex'] ?? '#ccc';
                                                        $label       = $value['caption'] ?? 'Option';
                                                    @endphp
                                                    <button type="button"
                                                            class="sdp-swatch {{ $isActive ? 'active' : '' }}"
                                                            title="{{ $label }}{{ !$isAvailable ? ' (Unavailable)' : '' }}"
                                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                                            @disabled(!$isAvailable)
                                                            @if(!$isAvailable) style="opacity:.3;cursor:not-allowed;" @endif>
                                                        <span class="sdp-swatch-core" style="background-color:{{ $colorHex }};"></span>
                                                    </button>
                                                @endforeach
                                            </div>

                                        @elseif($presentation === 'image')
                                            <div class="sdp-swatches">
                                                @foreach($values as $value)
                                                    @php
                                                        $valueId     = $value['id'];
                                                        $isAvailable = in_array((int)$valueId, $availableOptions[$groupId] ?? []);
                                                        $isActive    = (string)$selectedVId === (string)$valueId;
                                                        $label       = $value['caption'] ?? 'Option';
                                                        $imgUrl      = $value['presentation_image_path'] ? url('storage/'.$value['presentation_image_path']) : 'https://placehold.co/100x120/eee/999?text=Img';
                                                    @endphp
                                                    <button type="button"
                                                            class="sdp-swatch-image {{ $isActive ? 'active' : '' }}"
                                                            title="{{ $label }}{{ !$isAvailable ? ' (Unavailable)' : '' }}"
                                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                                            @disabled(!$isAvailable)
                                                            @if(!$isAvailable) style="opacity:.3;cursor:not-allowed;" @endif>
                                                        <img src="{{ $imgUrl }}" alt="{{ $label }}">
                                                    </button>
                                                @endforeach
                                            </div>

                                        @else {{-- text / size --}}
                                            <div class="sdp-size-grid">
                                                @foreach($values as $value)
                                                    @php
                                                        $valueId     = $value['id'];
                                                        $isAvailable = in_array((int)$valueId, $availableOptions[$groupId] ?? []);
                                                        $isActive    = (string)$selectedVId === (string)$valueId;
                                                        $label       = $value['caption'] ?? '?';
                                                    @endphp
                                                    <button type="button"
                                                            class="sdp-size-btn {{ $isActive ? 'active' : '' }}"
                                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                                            @disabled(!$isAvailable)
                                                            title="{{ !$isAvailable ? 'Unavailable for current selection' : '' }}">
                                                        {{ $label }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Size Guide link when no size group but guide exists --}}
                                @if(isset($sizeGuide) && !collect($variationGroups)->contains(fn($g) => stripos($g['name'] ?? '', 'size') !== false))
                                    <button type="button" class="sdp-size-guide-link" style="text-align:left;"
                                            data-bs-toggle="offcanvas" data-bs-target="#sizeGuideOffcanvas">
                                        <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 24;">straighten</span>
                                        Size Guide
                                    </button>
                                @endif

                            </div>

                            {{-- Sizing tip --}}
                            @if(isset($sizeGuide) && count($variationGroups) > 0 && collect($variationGroups)->contains(fn($g) => stripos($g['name'] ?? '', 'size') !== false))
                                <div class="sdp-info-tip">
                                    <span class="material-symbols-outlined">info</span>
                                    <span>True to size. We recommend ordering your usual size.</span>
                                </div>
                            @endif
                        @else
                            {{-- No variations, show size guide link standalone if available --}}
                            @if(isset($sizeGuide))
                                <button type="button" class="sdp-size-guide-link"
                                        data-bs-toggle="offcanvas" data-bs-target="#sizeGuideOffcanvas">
                                    <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle; font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 24;">straighten</span>
                                    Size Guide
                                </button>
                            @endif
                        @endif

                        {{-- ── Quantity ──────────────────────────── --}}
                        <div>
                            <div class="sdp-group-label" style="margin-bottom:.6rem;">Quantity</div>
                            <div class="sdp-qty">
                                <button type="button" class="sdp-qty-btn" wire:click="decrementQuantity" aria-label="Decrease quantity">
                                    <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 24;">remove</span>
                                </button>
                                <span class="sdp-qty-value">{{ $quantity ?? 1 }}</span>
                                <button type="button" class="sdp-qty-btn" wire:click="incrementQuantity" aria-label="Increase quantity">
                                    <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 24;">add</span>
                                </button>
                            </div>
                        </div>

                        {{-- ── Add to Cart ────────────── --}}
                        <div class="sdp-action-row">
                            <button type="button" class="sdp-add-btn"
                                    wire:click="addToCart"
                                    wire:loading.attr="disabled"
                                    @disabled(!$inStock)>
                                <span wire:loading.remove wire:target="addToCart">
                                    {{ $inStock ? 'Add to Cart' : ($availabilityLabel ?? 'Out of Stock') }}
                                </span>
                                <span wire:loading wire:target="addToCart">Adding…</span>
                                <span class="material-symbols-outlined" style="font-size:20px; font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 24;" wire:loading.remove wire:target="addToCart">arrow_forward</span>
                            </button>
                        </div>

                        {{-- ── Value Props ───────────────────────── --}}
                        <div class="sdp-value-props">
                            <div class="sdp-value-prop">
                                <span class="material-symbols-outlined">verified</span>
                                <span>Certified &amp; Authentic. Guaranteed heritage gear from ECC.</span>
                            </div>
                            <div class="sdp-value-prop">
                                <span class="material-symbols-outlined">lock</span>
                                <span>Secure checkout. Your payment is protected.</span>
                            </div>
                        </div>

                        {{-- ── Accordions ────────────────────────── --}}
                        <div class="sdp-accordion">

                            {{-- Specifications (variation groups listed as bullets) --}}
                            @if(count($variationGroups ?? []))
                                <details class="sdp-accordion-item" open>
                                    <summary>
                                        <span>Specifications</span>
                                        <span class="material-symbols-outlined">expand_more</span>
                                    </summary>
                                    <div class="sdp-accordion-body">
                                        <ul>
                                            @foreach($variationGroups as $group)
                                                @php
                                                    $groupId   = $group['id'];
                                                    $selId     = $selectedVariationValues[$groupId] ?? null;
                                                    $selValue  = collect($group['values'] ?? [])->firstWhere('id', $selId);
                                                    $selCaption = $selValue['caption'] ?? null;
                                                @endphp
                                                @if($selCaption)
                                                    <li><strong>{{ $group['name'] }}:</strong> {{ $selCaption }}</li>
                                                @endif
                                            @endforeach
                                            @if($product->sku)
                                                <li><strong>SKU:</strong> {{ $product->sku }}</li>
                                            @endif
                                            @if($product->weight)
                                                <li><strong>Weight:</strong> {{ $product->weight }} {{ $product->weight_unit ?? 'g' }}</li>
                                            @endif
                                        </ul>
                                    </div>
                                </details>
                            @endif

                            {{-- The Craftsmanship (product description) --}}
                            <details class="sdp-accordion-item" {{ !count($variationGroups ?? []) ? 'open' : '' }}>
                                <summary>
                                    <span>The Craftsmanship</span>
                                    <span class="material-symbols-outlined">expand_more</span>
                                </summary>
                                <div class="sdp-accordion-body">
                                    {!! $descriptionHtml !!}
                                </div>
                            </details>

                            {{-- Tags as extra info --}}
                            @if($product->tags->count())
                                <details class="sdp-accordion-item">
                                    <summary>
                                        <span>Details & Tags</span>
                                        <span class="material-symbols-outlined">expand_more</span>
                                    </summary>
                                    <div class="sdp-accordion-body">
                                        <div style="display: flex; flex-wrap: wrap; gap: .4rem;">
                                            @foreach($product->tags as $tag)
                                                <span style="display:inline-block; padding:.2rem .7rem; border-radius:999px; border:1px solid var(--hg-outline-var); font-family: var(--hg-font-mono); font-size:.62rem; font-weight:700; letter-spacing:.06em; color: var(--hg-on-surface-var);">
                                                    {{ $tag->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                            @endif

                        </div>
                    </div>
                </div>
                {{-- /RIGHT --}}

            </div>
            {{-- /MAIN GRID --}}

        </main>

        {{-- ═══════════════════════════════════════════════════════════
             COMPLETE THE LOOK (Related Products)
             ═══════════════════════════════════════════════════════════ --}}
        @if(isset($relatedProducts) && $relatedProducts->count())
            <section class="sdp-related-section" style="max-width:1280px; margin:0 auto; padding-left:1.5rem; padding-right:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 2rem; flex-wrap:wrap; gap:.75rem;">
                    <h2 style="font-family:var(--hg-font-body); font-size:clamp(1.4rem,3vw,2rem); font-weight:700; text-transform:uppercase; letter-spacing:-.025em; color:var(--hg-on-surface); margin:0;">
                        Complete The Look
                    </h2>
                    <a href="{{ $collectionUrl }}"
                       style="font-family:var(--hg-font-mono); font-size:.7rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--hg-on-surface); text-decoration:none; border-bottom:2px solid var(--hg-primary); padding-bottom:.15rem;">
                        View All
                    </a>
                </div>
                <div class="sdp-related-grid">
                    @foreach($relatedProducts as $related)
                        @php
                            $relatedImg   = $related->images->first();
                            $relatedImgUrl = $relatedImg ? url('storage/'.$relatedImg->image_path) : 'https://placehold.co/400x530/eeebe8/7f7668?text=Product';
                            $relatedPrice = ($product->currency ?? 'INR') . ' ' . number_format($related->base_price, 2);
                            $relatedUrl   = route('shop.show', $related->slug);
                        @endphp
                        <a href="{{ $relatedUrl }}" class="sdp-related-card">
                            <div class="sdp-related-img-wrap">
                                <img src="{{ $relatedImgUrl }}" alt="{{ $related->title }}" class="sdp-related-img" loading="lazy">
                            </div>
                            <div>
                                <div class="sdp-related-title">{{ $related->title }}</div>
                                <div class="sdp-related-price">{{ $relatedPrice }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ═══════════════════════════════════════════════════════════
             SIZE GUIDE OFFCANVAS (unchanged — preserved exactly)
             ═══════════════════════════════════════════════════════════ --}}
        @if(isset($sizeGuide))
            <div class="offcanvas offcanvas-end bg-white text-dark" tabindex="-1" id="sizeGuideOffcanvas" aria-labelledby="sizeGuideOffcanvasLabel"
                 style="width: 500px; max-width: 100vw; border-left: none; box-shadow: -5px 0 25px rgba(0,0,0,0.1);"
                 x-data="{ unit: 'cm', multiplier: {{ \App\Models\Setting::get('global_cm_to_inch_multiplier', 0.3937) }} }">
                <div class="offcanvas-header pb-0 border-0">
                    <h4 class="offcanvas-title fw-bolder text-uppercase" id="sizeGuideOffcanvasLabel" style="letter-spacing: 2px;">
                        SIZE GUIDE
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body pt-3">
                    <h5 class="fw-bolder text-uppercase mb-3" style="letter-spacing: 1px;">
                        {{ $sizeGuide->name }}
                    </h5>

                    @if($sizeGuide->description)
                        <p class="fs-15 mb-4">{{ $sizeGuide->description }}</p>
                    @endif

                    @php
                        $tableData = is_array($sizeGuide->table_data) ? $sizeGuide->table_data : [];
                        $cmTables = [];
                        $inchTables = [];
                        
                        if (isset($tableData[0]['rows'][0]['values'])) {
                            // Unified format
                            foreach ($tableData as $table) {
                                $columns = $table['columns'] ?? [];
                                $cmRows = [];
                                $inchRows = [];
                                
                                foreach ($table['rows'] ?? [] as $row) {
                                    $label = $row['label'] ?? '';
                                    $cmRow = [$label];
                                    $inchRow = [$label];
                                    
                                    foreach ($columns as $cIndex => $colName) {
                                        if ($cIndex === 0) continue;
                                        $cmRow[] = $row['values']['cm'][$cIndex] ?? '';
                                        $inchRow[] = $row['values']['inch'][$cIndex] ?? '';
                                    }
                                    $cmRows[] = $cmRow;
                                    $inchRows[] = $inchRow;
                                }
                                
                                $cmTables[] = [
                                    'title' => $table['title'] ?? '',
                                    'columns' => $columns,
                                    'rows' => $cmRows
                                ];
                                $inchTables[] = [
                                    'title' => $table['title'] ?? '',
                                    'columns' => $columns,
                                    'rows' => $inchRows
                                ];
                            }
                        } else if (isset($tableData['cm']) || isset($tableData['inch'])) {
                            $cmTables = $tableData['cm'] ?? [];
                            $inchTables = $tableData['inch'] ?? [];
                        } else {
                            // Backwards compatibility
                            if (isset($tableData['columns'])) {
                                $legacy = [['title' => '', 'columns' => $tableData['columns'], 'rows' => $tableData['rows'] ?? []]];
                            } else {
                                $legacy = $tableData;
                            }
                            $cmTables = $legacy;
                            
                            // generate inch fallback
                            $multiplier = \App\Models\Setting::get('global_cm_to_inch_multiplier', 0.3937);
                            $inchTables = [];
                            foreach ($legacy as $table) {
                                $newRows = [];
                                foreach ($table['rows'] ?? [] as $row) {
                                    $newRow = [];
                                    foreach ($row as $cIndex => $val) {
                                        if ($cIndex > 0 && is_numeric(trim($val))) {
                                            $newRow[] = round((float)trim($val) * $multiplier, 1);
                                        } else {
                                            $newRow[] = $val;
                                        }
                                    }
                                    $newRows[] = $newRow;
                                }
                                $inchTables[] = [
                                    'title' => $table['title'] ?? '',
                                    'columns' => $table['columns'] ?? [],
                                    'rows' => $newRows
                                ];
                            }
                        }

                        // Check if completely empty helper
                        $isEmptyTableCollection = function($tablesList) {
                            if (empty($tablesList)) return true;
                            foreach ($tablesList as $table) {
                                if (!empty($table['rows'])) {
                                    foreach ($table['rows'] as $row) {
                                        foreach ($row as $cell) {
                                            if (trim($cell) !== '') {
                                                return false;
                                            }
                                        }
                                    }
                                }
                            }
                            return true;
                        };

                        $cmIsEmpty = $isEmptyTableCollection($cmTables);
                        $inchIsEmpty = $isEmptyTableCollection($inchTables);
                        
                        // Determine default unit to show
                        $defaultUnit = !$cmIsEmpty ? 'cm' : (!$inchIsEmpty ? 'inch' : '');
                    @endphp

                    @if(!$cmIsEmpty || !$inchIsEmpty)
                        <div x-data="{ unit: '{{ $defaultUnit }}' }">
                            @if(!$cmIsEmpty && !$inchIsEmpty)
                                <div class="d-flex mb-4 border-bottom">
                                    <button type="button" class="btn btn-link text-decoration-none rounded-0 px-3 py-2 fw-semibold text-dark"
                                            :class="{ 'border-bottom border-dark border-2': unit === 'inch' }" @click="unit = 'inch'">Inches</button>
                                    <button type="button" class="btn btn-link text-decoration-none rounded-0 px-3 py-2 fw-semibold text-dark"
                                            :class="{ 'border-bottom border-dark border-2': unit === 'cm' }" @click="unit = 'cm'">cm</button>
                                </div>
                            @endif

                            @if(!$cmIsEmpty)
                                <div x-show="unit === 'cm'">
                                    @foreach($cmTables as $table)
                                        @if(!empty($table['title']))
                                            <h6 class="fw-bold mb-3">{{ $table['title'] }}</h6>
                                        @endif

                                        <div class="table-responsive mb-4" style="border: 1px solid #dee2e6;">
                                            <table class="table table-striped table-hover text-center align-middle mb-0" style="min-width: max-content;">
                                                <thead class="text-white" style="background-color: #000;">
                                                    <tr>
                                                        @foreach($table['columns'] ?? [] as $header)
                                                            <th class="py-3 px-4 border-0" style="font-weight: 700;">{{ $header }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($table['rows'] ?? [] as $row)
                                                        <tr>
                                                            @foreach($row as $ci => $cell)
                                                                @if($ci === 0)
                                                                    <td class="fw-bold text-start py-3 px-4" style="background-color: #fff; border-right: 1px solid #dee2e6;">{{ $cell }}</td>
                                                                @else
                                                                    <td class="py-3 px-4">{{ $cell }}</td>
                                                                @endif
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if(!$inchIsEmpty)
                                <div x-show="unit === 'inch'">
                                    @foreach($inchTables as $table)
                                        @if(!empty($table['title']))
                                            <h6 class="fw-bold mb-3">{{ $table['title'] }}</h6>
                                        @endif

                                        <div class="table-responsive mb-4" style="border: 1px solid #dee2e6;">
                                            <table class="table table-striped table-hover text-center align-middle mb-0" style="min-width: max-content;">
                                                <thead class="text-white" style="background-color: #000;">
                                                    <tr>
                                                        @foreach($table['columns'] ?? [] as $header)
                                                            <th class="py-3 px-4 border-0" style="font-weight: 700;">{{ $header }}</th>
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($table['rows'] ?? [] as $row)
                                                        <tr>
                                                            @foreach($row as $ci => $cell)
                                                                @if($ci === 0)
                                                                    <td class="fw-bold text-start py-3 px-4" style="background-color: #fff; border-right: 1px solid #dee2e6;">{{ $cell }}</td>
                                                                @else
                                                                    <td class="py-3 px-4">{{ $cell }}</td>
                                                                @endif
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <p class="text-muted small mt-n2 mb-4">
                                <i class="mdi mdi-ray-start-arrow text-muted me-1"></i> Scroll horizontally to see more.
                            </p>
                        </div>
                    @endif

                    @if($sizeGuide->how_to_measure_text)
                        <div class="mt-5 border-top pt-4">
                            <h5 class="fw-bold mb-3">How to measure</h5>
                            <div class="fs-15 summernote-rendered-content" style="line-height: 1.8;">
                                {!! $sizeGuide->how_to_measure_text !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>
    {{-- /sdp --}}

    @push('scripts')
    <script>
        // Apply responsive grid to main layout without requiring Alpine/Tailwind
        document.addEventListener('DOMContentLoaded', function () {
            const grid = document.querySelector('.sdp-main-grid');
            if (!grid) return;
            function applyGrid() {
                if (window.innerWidth >= 768) {
                    grid.style.gridTemplateColumns = '1fr minmax(0, 380px)';
                    grid.style.gap = '3rem';
                    grid.style.alignItems = 'start';
                } else {
                    grid.style.gridTemplateColumns = '1fr';
                    grid.style.gap = '1.5rem';
                }
            }
            applyGrid();
            window.addEventListener('resize', applyGrid);
        });
    </script>
    @endpush
</div>
