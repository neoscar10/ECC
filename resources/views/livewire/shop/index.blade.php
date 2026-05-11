<div class="shop-page">
    @push('styles')
    <style>
        .shop-page {
            padding-top: .25rem;
            padding-bottom: 1.5rem;
            overflow: visible !important;
        }

        .shop-layout {
            display: flex;
            gap: 2rem;
            align-items: stretch;
            overflow: visible !important;
        }

        .shop-sidebar {
            width: 310px;
            flex: 0 0 310px;
        }

        @media (min-width: 992px) {
            .shop-sidebar-inner {
                position: sticky;
                top: 110px; /* Offset for header + breathe */
                max-height: calc(100vh - 130px);
                overflow-y: auto;
                overscroll-behavior: contain;
                display: flex;
                flex-direction: column;
                gap: 0;
                background: rgba(255, 255, 255, 0.02);
                border: 1px solid rgba(212, 175, 55, 0.12);
                border-radius: 20px;
                padding: 0;
                box-shadow: 0 12px 34px rgba(0,0,0,0.18);
                scrollbar-width: thin;
                scrollbar-color: rgba(212, 175, 55, 0.22) transparent;
            }

            .shop-sidebar-inner::-webkit-scrollbar {
                width: 6px;
            }

            .shop-sidebar-inner::-webkit-scrollbar-track {
                background: transparent;
            }

            .shop-sidebar-inner::-webkit-scrollbar-thumb {
                background: rgba(212, 175, 55, 0.22);
                border-radius: 10px;
            }

            .shop-sidebar-inner::-webkit-scrollbar-thumb:hover {
                background: rgba(212, 175, 55, 0.4);
            }
        }

        /* Fallback/Mobile Sidebar Reset */
        @media (max-width: 991.98px) {
            .shop-sidebar-inner {
                position: relative;
                top: 0;
                max-height: none;
                overflow: visible;
                background: none;
                border: none;
                box-shadow: none;
            }
        }

        .shop-filter-section {
            padding: 1.75rem;
            border-bottom: 1px solid rgba(212, 175, 55, 0.08);
        }

        .shop-filter-section:last-child {
            border-bottom: 0;
        }

        .shop-filter-block-title {
            color: var(--luxe-gold);
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .shop-filter-block-title::before {
            content: '';
            width: 3px;
            height: 12px;
            background: var(--luxe-gold);
            border-radius: 999px;
            display: inline-block;
        }

        .shop-category-list,
        .shop-tag-list {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .shop-filter-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            color: rgba(245,240,231,.92);
            text-decoration: none;
            font-size: .96rem;
            font-weight: 600;
            transition: .2s ease;
            background: none;
            border: none;
            padding: 0;
            width: 100%;
        }

        .shop-filter-link:hover {
            color: var(--luxe-gold);
        }

        .shop-filter-link.active {
            color: var(--luxe-gold);
            font-weight: 800;
        }

        .shop-filter-count {
            color: var(--luxe-text-soft);
            font-size: .72rem;
            font-weight: 700;
        }

        .shop-range-wrap {
            padding-right: .2rem;
        }

        .shop-range-track {
            position: relative;
            height: 4px;
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            margin: 1rem 0 .75rem;
        }

        .shop-range-active {
            position: absolute;
            top: 0;
            height: 100%;
            border-radius: 999px;
            background: var(--luxe-gold);
        }

        .shop-range-thumb-visual {
            position: absolute;
            top: 50%;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--luxe-gold);
            border: 2px solid #110f09;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 0 4px rgba(212,175,55,.08);
            pointer-events: none;
            z-index: 3;
        }

        .shop-range-labels {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            color: var(--luxe-text-soft);
            font-size: .75rem;
            font-weight: 700;
            margin-top: .75rem;
        }

        .shop-range-inputs {
            position: relative;
            height: 20px;
            margin-top: -14px;
        }

        .shop-range-native {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 20px;
            background: transparent;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .shop-range-native::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: transparent;
            border: 0;
            pointer-events: auto;
            cursor: pointer;
        }

        .shop-range-native::-moz-range-thumb {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: transparent;
            border: 0;
            pointer-events: auto;
            cursor: pointer;
        }

        .shop-range-native::-webkit-slider-runnable-track {
            height: 4px;
            background: transparent;
        }

        .shop-range-native::-moz-range-track {
            height: 4px;
            background: transparent;
        }

        .shop-range-native.min-range {
            z-index: 2;
        }

        .shop-range-native.max-range {
            z-index: 1;
        }

        .shop-pill-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .55rem;
        }

        .shop-pill-btn {
            min-height: 40px;
            border-radius: 10px;
            border: 1px solid rgba(212,175,55,.14);
            background: rgba(255,255,255,.02);
            color: rgba(245,240,231,.92);
            font-size: .78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
            transition: .2s ease;
        }

        .shop-pill-btn:hover {
            border-color: rgba(212,175,55,.34);
            color: var(--luxe-gold);
        }

        .shop-pill-btn.active {
            border-color: var(--luxe-gold);
            background: rgba(212,175,55,.12);
            color: var(--luxe-gold);
        }

        .shop-checkbox-list {
            display: flex;
            flex-direction: column;
            gap: .9rem;
        }

        .shop-checkbox {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: rgba(245,240,231,.92);
            font-size: .92rem;
            font-weight: 500;
            cursor: pointer;
        }

        .shop-checkbox-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1px solid rgba(212,175,55,.20);
            background: rgba(255,255,255,.03);
            accent-color: var(--luxe-gold);
        }

        .shop-main {
            min-width: 0;
            flex: 1 1 auto;
        }

    .ecc-shop-search-block {
        background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
        border: 1px solid rgba(212,175,55,.14);
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
        margin-bottom: 2rem;
    }

    .ecc-shop-search-inner {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .ecc-shop-search-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(212,175,55,.10);
        color: #d4af37;
        font-size: 1.3rem;
    }

    .ecc-shop-search-label {
        color: #d4af37;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .22em;
        text-transform: uppercase;
    }

    .ecc-shop-search-input {
        min-height: 52px;
        border-radius: 1rem;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(212,175,55,.12);
        color: #f5efe1;
        box-shadow: none !important;
    }

    .ecc-shop-search-input:focus {
        background: rgba(255,255,255,.06);
        border-color: rgba(212,175,55,.42);
        color: #fff;
    }

    .ecc-shop-search-input::placeholder {
        color: rgba(245,239,225,.38);
    }

    .ecc-shop-search-helper {
        color: rgba(245,239,225,.52);
        font-size: .82rem;
        line-height: 1.6;
        padding-left: calc(52px + 1rem);
    }

    .shop-products-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .shop-products-title {
            color: #fff;
            font-size: 2.15rem;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: -.03em;
            margin: 0;
        }

        .shop-products-subtitle {
            color: var(--luxe-text-soft);
            font-size: .92rem;
            margin-top: .35rem;
        }

        .shop-sort-inline {
            display: inline-flex;
            align-items: center;
            gap: .8rem;
            color: var(--luxe-text-soft);
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .shop-sort-inline .form-select {
            min-width: 190px;
            border: 0;
            background-color: transparent;
            color: #fff;
            font-size: .95rem;
            font-weight: 800;
            box-shadow: none;
            padding-right: 2rem;
        }

        .shop-sort-inline .form-select option {
            color: #111;
        }

        .shop-grid {
            margin-bottom: 2.5rem;
        }

        .shop-card {
            position: relative;
            height: 100%;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid transparent;
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                rgba(35,31,23,.90);
            box-shadow: 0 16px 34px rgba(0,0,0,.22);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
        }

        .shop-card:hover {
            transform: translateY(-4px);
            border-color: rgba(212,175,55,.18);
            box-shadow: 0 22px 44px rgba(0,0,0,.32);
        }

        .shop-card-media {
            position: relative;
            aspect-ratio: 4 / 5;
            overflow: hidden;
            background: rgba(255,255,255,.03);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .shop-card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .7s ease;
        }

        .shop-card:hover .shop-card-media img {
            transform: scale(1.08);
        }

        .shop-card-body {
            padding: 1.2rem 1rem 1rem;
        }

        .shop-card-head {
            display: flex;
            justify-content: space-between;
            gap: .85rem;
            align-items: start;
            margin-bottom: .85rem;
        }

        .shop-card-title {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -.02em;
            margin: 0;
        }

        .shop-card-subtitle {
            color: var(--luxe-text-soft);
            font-size: .7rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            margin-top: .35rem;
        }

        .shop-card-price {
            color: var(--luxe-gold);
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -.03em;
            white-space: nowrap;
        }

        .shop-card-actions {
            display: flex;
            gap: .55rem;
        }

        .shop-card-view-btn {
            flex: 1 1 auto;
            min-height: 44px;
            border-radius: 10px;
            border: 0;
            background: #3d392f;
            color: #fff;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            text-decoration: none;
            transition: .2s ease;
        }

        .shop-card-view-btn:hover {
            background: var(--luxe-gold);
            color: #111;
        }

        .shop-card-cart-btn {
            flex: 1;
            min-height: 44px;
            border-radius: 10px;
            border: 0;
            background: var(--luxe-gold);
            color: #111;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: .2s ease;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .shop-card-cart-btn:hover {
            filter: brightness(1.05);
            color: #111;
        }

        .shop-card-cart-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--luxe-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            color: #111;
            z-index: 5;
        }

        .shop-card-cart-loading .spinner-border {
            margin: 0 !important;
        }

        .shop-card-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 28px;
            padding: .4rem .8rem;
            border-radius: 999px;
            background: var(--luxe-gold);
            color: #111;
            font-size: .62rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            box-shadow: 0 10px 18px rgba(212,175,55,.18);
        }

        .shop-pagination-wrap {
            display: flex;
            justify-content: center;
            margin-top: 1.25rem;
        }

        .shop-pagination {
            display: inline-flex;
            align-items: center;
            gap: .9rem;
        }

        .shop-pagination-arrow,
        .shop-pagination-page {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid rgba(212,175,55,.14);
            background: transparent;
            color: rgba(245,240,231,.92);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            text-decoration: none;
            transition: .2s ease;
        }

        .shop-pagination-page.active {
            background: var(--luxe-gold);
            border-color: var(--luxe-gold);
            color: #111;
        }

        .shop-pagination-arrow:hover,
        .shop-pagination-page:hover {
            border-color: rgba(212,175,55,.38);
            color: var(--luxe-gold);
        }

        .shop-pagination-page.active:hover {
            color: #111;
        }

        .shop-mobile-filter-trigger {
            display: none;
            width: 100%;
            min-height: 46px;
            border-radius: 12px;
            border: 1px solid rgba(212,175,55,.16);
            background: rgba(255,255,255,.03);
            color: #fff;
            font-size: .82rem;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .shop-filter-mobile-card {
            border-radius: 18px;
            border: 1px solid rgba(212,175,55,.14);
            background:
                linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
                rgba(16,13,7,.95);
        }

        @media (max-width: 1199.98px) {
            .shop-layout {
                gap: 1.5rem;
            }

            .shop-sidebar {
                width: 260px;
                flex-basis: 260px;
            }
        }

        @media (max-width: 991.98px) {
            .shop-layout {
                display: block;
            }

            .shop-sidebar {
                display: none;
            }

            .shop-mobile-filter-trigger {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: .6rem;
            }

            .ecc-shop-search-block {
                padding: 1rem;
            }

            .ecc-shop-search-inner {
                align-items: flex-start;
            }

            .ecc-shop-search-helper {
                padding-left: 0;
            }

            .shop-products-head {
                flex-direction: column;
                align-items: start;
            }
        }

        @media (max-width: 575.98px) {
            .shop-featured-banner {
                min-height: 260px;
            }

            .shop-featured-text {
                font-size: .95rem;
                line-height: 1.7;
            }

            .shop-card-body {
                padding: 1rem;
            }

            .shop-card-title {
                font-size: 1.05rem;
            }

            .shop-card-price {
                font-size: 1.2rem;
            }

            .shop-pagination {
                gap: .55rem;
            }

            .shop-pagination-arrow,
            .shop-pagination-page {
                width: 40px;
                height: 40px;
            }
        }
        .hover-opacity-100 { transition: opacity 0.2s ease; }
        .hover-opacity-100:hover { opacity: 1 !important; }
        .text-gold { color: var(--luxe-gold) !important; }
    </style>
    @endpush

    @php
        // Shop search and filters are already wired to the existing Livewire Index component logic
    @endphp

    <button
        class="shop-mobile-filter-trigger"
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#shopFiltersCanvas"
        aria-controls="shopFiltersCanvas"
    >
        <i class="mdi mdi-filter-variant"></i>
        <span>Filters & Sort</span>
    </button>

    <div class="shop-layout">
        {{-- DESKTOP SIDEBAR --}}
        <aside class="shop-sidebar">
            <div class="shop-sidebar-inner">
                {{-- CATEGORIES --}}
                <div class="shop-filter-section">
                    <div class="shop-filter-block-title">Categories</div>

                    <div class="shop-category-list">
                        <button
                            type="button"
                            wire:click="$set('activeCategoryId', null)"
                            class="shop-filter-link {{ empty($activeCategoryId) ? 'active' : '' }}"
                        >
                            <span>All Collections</span>
                        </button>

                        @foreach($categories as $category)
                            @php
                                $categoryId = $category->id;
                                $categoryName = $category->name;
                                $categoryCount = tap($category->products_count, fn($c) => $c);
                                $categoryActive = (string) $activeCategoryId === (string) $categoryId;
                            @endphp

                            <button
                                type="button"
                                wire:click="$set('activeCategoryId', '{{ $categoryId }}')"
                                class="shop-filter-link {{ $categoryActive ? 'active' : '' }}"
                            >
                                <span>{{ $categoryName }}</span>
                                @if(!is_null($categoryCount))
                                    <span class="shop-filter-count">{{ $categoryCount }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- PRICE RANGE --}}
                <div class="shop-filter-section">
                    <div class="shop-filter-block-title">Price Range</div>

                    <div class="shop-range-wrap">
                        @php
                            $absoluteMin = (int) ($absoluteMinPrice ?? 0);
                            $absoluteMax = (int) ($absoluteMaxPrice ?? 1500);
                            $selectedMin = (int) ($minPrice ?? $absoluteMin);
                            $selectedMax = (int) ($maxPrice ?? $absoluteMax);

                            $rangeSpan = max(1, $absoluteMax - $absoluteMin);
                            $leftPercent = (($selectedMin - $absoluteMin) / $rangeSpan) * 100;
                            $rightPercent = (($selectedMax - $absoluteMin) / $rangeSpan) * 100;
                        @endphp

                        <div class="shop-range-track" onclick="handleTrackClick(event, this.closest('.shop-range-wrap'))">
                            <div
                                class="shop-range-active"
                                style="left: {{ max(0, min(100, $leftPercent)) }}%; width: {{ max(0, min(100, $rightPercent - $leftPercent)) }}%;"
                            ></div>

                            <span
                                class="shop-range-thumb-visual"
                                style="left: {{ max(0, min(100, $leftPercent)) }}%;"
                            ></span>

                            <span
                                class="shop-range-thumb-visual"
                                style="left: {{ max(0, min(100, $rightPercent)) }}%;"
                            ></span>
                        </div>

                        <div class="shop-range-inputs">
                            <input
                                type="range"
                                min="{{ $absoluteMinPrice }}"
                                max="{{ $absoluteMaxPrice }}"
                                step="1"
                                wire:model="minPrice"
                                class="shop-range-native min-range"
                                oninput="updateRangeUI(this)"
                            >

                            <input
                                type="range"
                                min="{{ $absoluteMinPrice }}"
                                max="{{ $absoluteMaxPrice }}"
                                step="1"
                                wire:model="maxPrice"
                                class="shop-range-native max-range"
                                oninput="updateRangeUI(this)"
                            >
                        </div>

                        <div class="shop-range-labels">
                            <span class="range-min-label">{{ $currencySymbol ?? '₹' }}{{ number_format($selectedMin) }}</span>
                            <span class="range-max-label">{{ $currencySymbol ?? '₹' }}{{ number_format($selectedMax) }}{{ ($absoluteMax === $selectedMax) ? '+' : '' }}</span>
                        </div>
                    </div>
                </div>

                {{-- TAG GROUPS / TAGS --}}
                @foreach($tagGroups as $group)
                    @php
                        $groupName = $group->name;
                        $groupSlug = $group->slug;
                        $groupTags = $group->tags;

                        // Identify groups to render as pills vs checkboxes
                        $renderAsPills = in_array($groupSlug, ['size', 'sizes']) || ($group->type ?? null) === 'pill';
                    @endphp

                    @if($groupTags->isNotEmpty())
                        <div class="shop-filter-section">
                            <div class="shop-filter-block-title">{{ $groupName }}</div>

                            @if($renderAsPills)
                                <div class="shop-pill-grid">
                                    @foreach($groupTags as $tag)
                                        @php
                                            $tagId = $tag->id;
                                            $tagName = $tag->name;
                                            $isSelected = in_array($tagId, $tags);
                                        @endphp

                                        <button
                                            type="button"
                                            wire:click="toggleTag('{{ $tagId }}')"
                                            class="btn shop-pill-btn {{ $isSelected ? 'active' : '' }}"
                                        >
                                            {{ $tagName }}
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="shop-checkbox-list">
                                    @foreach($groupTags as $tag)
                                        @php
                                            $tagId = $tag->id;
                                            $tagName = $tag->name;
                                            $isSelected = in_array($tagId, $tags);
                                        @endphp

                                        <label class="shop-checkbox">
                                            <input
                                                class="shop-checkbox-input"
                                                type="checkbox"
                                                wire:click="toggleTag('{{ $tagId }}')"
                                                @checked($isSelected)
                                            >
                                            <span>{{ $tagName }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </aside>

        {{-- MOBILE FILTER OFFCANVAS --}}
        <div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="shopFiltersCanvas" aria-labelledby="shopFiltersCanvasLabel" wire:ignore.self>
            <div class="offcanvas-body pt-5">
                {{-- Spacer to clear the fixed navbar --}}
                <div class="d-lg-none" style="height: 40px;"></div>
                <div class="shop-filter-mobile-card p-3">
                    <div class="d-flex flex-column gap-4">
                        <section>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="shop-filter-block-title mb-0">Categories</div>
                                <button type="button" class="btn btn-icon btn-ghost-light text-white opacity-75 hover-opacity-100 p-0" 
                                        data-bs-dismiss="offcanvas" aria-label="Close">
                                    <i class="mdi mdi-close fs-22"></i>
                                </button>
                            </div>

                            <div class="shop-category-list">
                                <button
                                    type="button"
                                    wire:click="$set('activeCategoryId', null)"
                                    class="shop-filter-link {{ empty($activeCategoryId) ? 'active' : '' }}"
                                >
                                    <span>All Collections</span>
                                </button>

                                @foreach($categories as $category)
                                    @php
                                        $categoryId = $category->id;
                                        $categoryName = $category->name;
                                        $categoryCount = tap($category->products_count, fn($c) => $c);
                                        $categoryActive = (string) $activeCategoryId === (string) $categoryId;
                                    @endphp

                                    <button
                                        type="button"
                                        wire:click="$set('activeCategoryId', '{{ $categoryId }}')"
                                        class="shop-filter-link {{ $categoryActive ? 'active' : '' }}"
                                    >
                                        <span>{{ $categoryName }}</span>
                                        @if(!is_null($categoryCount))
                                            <span class="shop-filter-count">{{ $categoryCount }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </section>

                        {{-- PRICE RANGE --}}
                        <section>
                            <div class="shop-filter-block-title">Price Range</div>

                            <div class="shop-range-wrap">
                                @php
                                    $absoluteMin = (int) ($absoluteMinPrice ?? 0);
                                    $absoluteMax = (int) ($absoluteMaxPrice ?? 1500);
                                    $selectedMin = (int) ($minPrice ?? $absoluteMin);
                                    $selectedMax = (int) ($maxPrice ?? $absoluteMax);

                                    $rangeSpan = max(1, $absoluteMax - $absoluteMin);
                                    $leftPercent = (($selectedMin - $absoluteMin) / $rangeSpan) * 100;
                                    $rightPercent = (($selectedMax - $absoluteMin) / $rangeSpan) * 100;
                                @endphp

                                <div class="shop-range-track" onclick="handleTrackClick(event, this.closest('.shop-range-wrap'))">
                                    <div
                                        class="shop-range-active"
                                        style="left: {{ max(0, min(100, $leftPercent)) }}%; width: {{ max(0, min(100, $rightPercent - $leftPercent)) }}%;"
                                    ></div>

                                    <span
                                        class="shop-range-thumb-visual"
                                        style="left: {{ max(0, min(100, $leftPercent)) }}%;"
                                    ></span>

                                    <span
                                        class="shop-range-thumb-visual"
                                        style="left: {{ max(0, min(100, $rightPercent)) }}%;"
                                    ></span>
                                </div>

                        <div class="shop-range-inputs">
                            <input
                                type="range"
                                min="{{ $absoluteMinPrice }}"
                                max="{{ $absoluteMaxPrice }}"
                                step="1"
                                wire:model="minPrice"
                                class="shop-range-native min-range"
                                oninput="updateRangeUI(this)"
                            >

                            <input
                                type="range"
                                min="{{ $absoluteMinPrice }}"
                                max="{{ $absoluteMaxPrice }}"
                                step="1"
                                wire:model="maxPrice"
                                class="shop-range-native max-range"
                                oninput="updateRangeUI(this)"
                            >
                        </div>

                        <div class="shop-range-labels">
                            <span class="range-min-label">{{ $currencySymbol ?? '₹' }}{{ number_format($selectedMin) }}</span>
                            <span class="range-max-label">{{ $currencySymbol ?? '₹' }}{{ number_format($selectedMax) }}{{ ($absoluteMax === $selectedMax) ? '+' : '' }}</span>
                        </div>
                    </div>
                </section>

                        @foreach($tagGroups as $group)
                            @php
                                $groupName = $group->name;
                                $groupSlug = $group->slug;
                                $groupTags = $group->tags;
                                $renderAsPills = in_array($groupSlug, ['size', 'sizes']) || ($group->type ?? null) === 'pill';
                            @endphp

                            @if($groupTags->isNotEmpty())
                                <section>
                                    <div class="shop-filter-block-title">{{ $groupName }}</div>

                                    @if($renderAsPills)
                                        <div class="shop-pill-grid">
                                            @foreach($groupTags as $tag)
                                                @php
                                                    $tagId = $tag->id;
                                                    $tagName = $tag->name;
                                                    $isSelected = in_array($tagId, $tags);
                                                @endphp

                                                <button
                                                    type="button"
                                                    wire:click="toggleTag('{{ $tagId }}')"
                                                    class="btn shop-pill-btn {{ $isSelected ? 'active' : '' }}"
                                                >
                                                    {{ $tagName }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="shop-checkbox-list">
                                            @foreach($groupTags as $tag)
                                                @php
                                                    $tagId = $tag->id;
                                                    $tagName = $tag->name;
                                                    $isSelected = in_array($tagId, $tags);
                                                @endphp

                                                <label class="shop-checkbox">
                                                    <input
                                                        class="shop-checkbox-input"
                                                        type="checkbox"
                                                        wire:click="toggleTag('{{ $tagId }}')"
                                                        @checked($isSelected)
                                                    >
                                                    <span>{{ $tagName }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </section>
                            @endif
                        @endforeach
                    </div>
                </div>


            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="shop-main">
            {{-- PREMIUM SEARCH BLOCK --}}
            <div class="ecc-shop-search-block mb-4 mb-lg-5">
                <div class="ecc-shop-search-inner">
                    <div class="ecc-shop-search-icon">
                        <i class="mdi mdi-magnify"></i>
                    </div>

                    <div class="flex-grow-1">
                        <label class="ecc-shop-search-label d-block mb-2">SEARCH PRODUCTS</label>
                        <input type="text"
                               class="form-control ecc-shop-search-input"
                               placeholder="Search premium cricket gear, apparel, and accessories..."
                               wire:model.live.debounce.400ms="search">
                    </div>
                </div>

                <div class="ecc-shop-search-helper mt-2">
                    Find products instantly while keeping your current filters applied.
                </div>
            </div>

            {{-- PRODUCTS HEAD --}}
            <section>
                <div class="shop-products-head">
                    <div>
                        <h2 class="shop-products-title">Shop Equipment</h2>
                        <div class="shop-products-subtitle">Latest additions to our collection</div>
                    </div>

                    <div class="shop-sort-inline">
                        <span>Sort By:</span>

                        <select wire:model.live="sort" class="form-select">
                            <option value="newest">Newest First</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="price_asc">Price: Low to High</option>
                            <option value="title_asc">Name: A to Z</option>
                            <option value="title_desc">Name: Z to A</option>
                        </select>
                    </div>
                </div>

                {{-- PRODUCT GRID --}}
                @if(collect($products->items())->count())
                    <div class="shop-grid">
                        <div class="row g-4">
                            @foreach($products->items() as $product)
                                @php
                                    $productTitle = $product->title;
                                    $productSubtitle = $product->categories->first()->name ?? '';
                                    $productPrice = '₹' . number_format((float)$product->base_price, 2);

                                    $img = collect($product->images)->first();
                                    $productImage = $img ? url('storage/' . $img->image_path) : 'https://placehold.co/800x1000/17130b/d4af37?text=No+Image';

                                    $productUrl = route('shop.show', $product->slug);

                                    $isNew = $product->created_at ? $product->created_at->diffInDays(now()) < 14 : false;
                                    $badgeLabel = $isNew ? 'New' : null;
                                    $isSoldOut = $product->computed_stock <= 0;
                                @endphp

                                <div class="col-12 col-sm-6 col-lg-4">
                                    <article class="shop-card position-relative {{ $isSoldOut ? 'opacity-75' : '' }}">
                                        <a href="{{ $productUrl }}" class="stretched-link"></a>
                                        <div class="shop-card-media">
                                            @if($productImage)
                                                <img src="{{ $productImage }}" alt="{{ $productTitle }}">
                                            @else
                                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                                    <i class="mdi mdi-image-outline fs-1"></i>
                                                </div>
                                            @endif

                                            @if($isSoldOut)
                                                <div class="shop-card-badge bg-dark text-white border border-secondary">Sold Out</div>
                                            @elseif(!empty($badgeLabel))
                                                <div class="shop-card-badge">{{ $badgeLabel }}</div>
                                            @endif
                                        </div>

                                        <div class="shop-card-body">
                                            <div class="shop-card-head">
                                                <div>
                                                    <h3 class="shop-card-title">{{ $productTitle }}</h3>
                                                    @if(!empty($productSubtitle))
                                                        <div class="shop-card-subtitle">{{ $productSubtitle }}</div>
                                                    @endif
                                                </div>

                                                <div class="shop-card-price">{{ $productPrice }}</div>
                                            </div>

                                            <div class="shop-card-actions position-relative z-2">
                                                <button
                                                    type="button"
                                                    class="shop-card-cart-btn position-relative"
                                                    wire:click.stop="addToCart({{ $product->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="addToCart({{ $product->id }})"
                                                    @disabled($isSoldOut)
                                                >
                                                    <span wire:loading.remove wire:target="addToCart({{ $product->id }})">
                                                        <i class="mdi mdi-shopping-outline"></i>
                                                        <span>Add to Cart</span>
                                                    </span>

                                                    <div wire:loading.flex wire:target="addToCart({{ $product->id }})" class="shop-card-cart-loading">
                                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- PAGINATION --}}
                    <div class="shop-pagination-wrap">
                        <div class="w-100 d-flex justify-content-center">
                            {{ $products->links() }}
                        </div>
                    </div>
                @else
                    <div class="archive-empty-state text-light-emphasis text-center py-5">
                        No products found for the selected filters.
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Smooth Range Slider UI
    window.updateRangeUI = (el) => {
        const wrap = el.closest('.shop-range-wrap');
        const minInput = wrap.querySelector('.min-range');
        const maxInput = wrap.querySelector('.max-range');
        const activeTrack = wrap.querySelector('.shop-range-active');
        const thumbs = wrap.querySelectorAll('.shop-range-thumb-visual');
        const minLabel = wrap.querySelector('.range-min-label');
        const maxLabel = wrap.querySelector('.range-max-label');
        
        if (!minInput || !maxInput || !activeTrack || thumbs.length < 2) return;

        // Prevent crossing
        if (el.classList.contains('min-range')) {
            if (parseInt(minInput.value) > parseInt(maxInput.value)) {
                minInput.value = maxInput.value;
            }
        } else {
            if (parseInt(maxInput.value) < parseInt(minInput.value)) {
                maxInput.value = minInput.value;
            }
        }
        
        const min = parseInt(minInput.value);
        const max = parseInt(maxInput.value);
        const absMin = parseInt(minInput.min);
        const absMax = parseInt(minInput.max);
        const range = absMax - absMin || 1;
        
        const left = ((min - absMin) / range) * 100;
        const right = ((max - absMin) / range) * 100;
        
        activeTrack.style.left = left + '%';
        activeTrack.style.width = (right - left) + '%';
        thumbs[0].style.left = left + '%';
        thumbs[1].style.left = right + '%';
        
        if (minLabel) minLabel.innerText = '{{ $currencySymbol ?? '₹' }}' + min.toLocaleString();
        if (maxLabel) maxLabel.innerText = '{{ $currencySymbol ?? '₹' }}' + max.toLocaleString() + (max === absMax ? '+' : '');
        
        // Z-Index Logic
        if (min > (absMax * 0.6)) {
            minInput.style.zIndex = "3";
            maxInput.style.zIndex = "2";
        } else {
            minInput.style.zIndex = "2";
            maxInput.style.zIndex = "3";
        }
    };

    // Click to move functionality
    window.handleTrackClick = (e, wrap) => {
        // Don't trigger if we clicked a thumb
        if (e.target.classList.contains('shop-range-native') || e.target.classList.contains('shop-range-thumb-visual')) return;

        const minInput = wrap.querySelector('.min-range');
        const maxInput = wrap.querySelector('.max-range');
        const track = wrap.querySelector('.shop-range-track');
        if (!minInput || !maxInput || !track) return;

        const rect = track.getBoundingClientRect();
        const percent = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
        
        const absMin = parseInt(minInput.min);
        const absMax = parseInt(minInput.max);
        const newValue = absMin + (percent * (absMax - absMin));
        
        const distMin = Math.abs(newValue - parseInt(minInput.value));
        const distMax = Math.abs(newValue - parseInt(maxInput.value));
        
        if (distMin < distMax) {
            minInput.value = newValue;
            updateRangeUI(minInput);
            minInput.dispatchEvent(new Event('change')); // Trigger Livewire
        } else {
            maxInput.value = newValue;
            updateRangeUI(maxInput);
            maxInput.dispatchEvent(new Event('change')); // Trigger Livewire
        }
    };

    const initPriceRange = () => {
        document.querySelectorAll('.shop-range-inputs').forEach(wrap => {
            const minInput = wrap.querySelector('.min-range');
            if (minInput) updateRangeUI(minInput);
        });
    };

    document.addEventListener('DOMContentLoaded', initPriceRange);
    
    // Re-init on Livewire update
    Livewire.hook('request', (({ component, commit, respond, succeed, fail }) => {
        succeed(({ snapshot, effect }) => {
            queueMicrotask(() => {
                initPriceRange();
            });
        })
    }));
</script>
@endpush
