<div class="font-body-md text-on-background bg-background min-h-screen pb-12">
    @push('styles')
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:ital,wght@0,100..900;1,100..900&amp;family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-secondary-fixed-variant": "#474746",
                        "primary-fixed": "#ffdea6",
                        "on-tertiary": "#ffffff",
                        "error": "#ba1a1a",
                        "on-surface": "#1b1c1c",
                        "tertiary-fixed": "#e2e3e1",
                        "primary": "#755a24",
                        "tertiary-fixed-dim": "#c6c7c5",
                        "secondary": "#5f5e5e",
                        "outline-variant": "#d1c5b5",
                        "on-tertiary-fixed": "#1a1c1b",
                        "on-tertiary-container": "#3b3d3c",
                        "tertiary": "#5d5f5d",
                        "surface-variant": "#e3e2e2",
                        "inverse-on-surface": "#f2f0f0",
                        "inverse-surface": "#303031",
                        "on-primary-container": "#503904",
                        "surface-container": "#efeded",
                        "tertiary-container": "#a7a8a6",
                        "error-container": "#ffdad6",
                        "surface-tint": "#755a24",
                        "on-secondary-container": "#636262",
                        "on-surface-variant": "#4d463a",
                        "surface-bright": "#fbf9f9",
                        "surface-container-high": "#e9e8e7",
                        "surface-dim": "#dbdad9",
                        "surface-container-highest": "#e3e2e2",
                        "primary-container": "#c5a365",
                        "surface-container-low": "#f5f3f3",
                        "on-secondary": "#ffffff",
                        "secondary-fixed": "#e5e2e1",
                        "on-primary-fixed-variant": "#5b430d",
                        "outline": "#7f7668",
                        "on-tertiary-fixed-variant": "#454746",
                        "secondary-container": "#e2dfde",
                        "surface-container-lowest": "#ffffff",
                        "background": "#fbf9f9",
                        "on-error": "#ffffff",
                        "on-primary": "#ffffff",
                        "on-error-container": "#93000a",
                        "on-secondary-fixed": "#1c1b1b",
                        "secondary-fixed-dim": "#c8c6c5",
                        "surface": "#fbf9f9",
                        "on-background": "#1b1c1c",
                        "on-primary-fixed": "#271900",
                        "primary-fixed-dim": "#e6c180",
                        "inverse-primary": "#e6c180"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "spacing": {
                        "container-max": "1280px",
                        "margin-desktop": "64px",
                        "gutter": "24px",
                        "margin-mobile": "20px",
                        "stack-lg": "32px",
                        "stack-md": "16px",
                        "stack-sm": "8px"
                    },
                    "fontFamily": {
                        "headline-md": ["Hanken Grotesk"],
                        "body-md": ["Hanken Grotesk"],
                        "display-lg": ["Hanken Grotesk"],
                        "headline-lg-mobile": ["Hanken Grotesk"],
                        "body-lg": ["Hanken Grotesk"],
                        "headline-lg": ["Hanken Grotesk"],
                        "label-sm": ["JetBrains Mono"]
                    },
                    "fontSize": {
                        "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "body-md": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "display-lg": ["48px", { "lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "headline-lg-mobile": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                        "body-lg": ["18px", { "lineHeight": "28px", "fontWeight": "400" }],
                        "headline-lg": ["32px", { "lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "700" }],
                        "label-sm": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500" }]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
        }
        .ecc-text-primary {
            color: #755a24 !important;
        }
        .ecc-border-primary {
            border-color: #755a24 !important;
        }
        .hover\:ecc-text-primary:hover {
            color: #755a24 !important;
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
            z-index: 5;
        }
        .shop-range-native::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: transparent;
            border: 0;
            pointer-events: auto;
            cursor: pointer;
        }
        .shop-range-native::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: transparent;
            border: 0;
            pointer-events: auto;
            cursor: pointer;
        }
        .shop-range-native::-webkit-slider-runnable-track {
            height: 4px;
            background: transparent;
            cursor: pointer;
        }
        .shop-range-native::-moz-range-track {
            height: 4px;
            background: transparent;
            cursor: pointer;
        }
        /* Custom styles to target Laravel pagination links inside the Tailwind design */
        .shop-pagination-wrap nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .shop-pagination-wrap nav a,
        .shop-pagination-wrap nav span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.5rem;
            height: 2.5rem;
            padding: 0.5rem;
            border-radius: 9999px;
            border: 1px solid #d1c5b5;
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }
    </style>
    @endpush

    <div class="flex flex-col md:flex-row w-full max-w-container-max mx-auto py-8 gap-gutter relative">
        {{-- SideNavBar / Filters Sidebar --}}
        <aside class="bg-surface border-r border-outline-variant w-64 hidden md:flex sticky top-24 flex-col p-4 gap-4 self-start rounded-xl">
            <!-- Header -->
            <div class="mb-6">
                <div class="text-label-sm font-label-sm text-secondary tracking-widest uppercase mb-1">CATEGORIES</div>
                <div class="text-body-md font-body-md text-on-surface-variant">Browse by Type</div>
            </div>

            <!-- Categories Links -->
            <nav class="flex flex-col gap-2 flex-grow">
                <button
                    type="button"
                    wire:click="$set('activeCategoryId', null)"
                    class="flex items-center p-2 transition-all rounded-xl w-full text-left {{ empty($activeCategoryId) ? 'ecc-text-primary font-bold border-l-4 ecc-border-primary bg-surface-container-high' : 'text-on-surface-variant hover:ecc-text-primary hover:bg-surface-container-high' }}"
                >
                    <span class="text-body-md font-body-md">All Collections</span>
                </button>

                @foreach($categories as $category)
                    @php
                        $categoryId = $category->id;
                        $categoryName = $category->name;
                        $categoryActive = (string) $activeCategoryId === (string) $categoryId;
                        $hasChildren = $category->children->isNotEmpty();
                        $childActive = !$categoryActive && !empty($activeCategoryId) && $category->children->pluck('id')->contains($activeCategoryId);
                    @endphp
                    <div class="w-full">
                        <button
                            type="button"
                            wire:click="$set('activeCategoryId', {{ $categoryActive ? 'null' : "'$categoryId'" }})"
                            class="flex items-center justify-between p-2 transition-all rounded-xl w-full text-left {{ $categoryActive ? 'ecc-text-primary font-bold border-l-4 ecc-border-primary bg-surface-container-high' : ($childActive ? 'ecc-text-primary font-bold bg-surface-container-high' : 'text-on-surface-variant hover:ecc-text-primary hover:bg-surface-container-high') }}"
                        >
                            <span class="text-body-md font-body-md">{{ $categoryName }}</span>
                            @if($hasChildren)
                                <span class="material-symbols-outlined text-sm">{{ $categoryActive || $childActive ? 'expand_more' : 'chevron_right' }}</span>
                            @endif
                        </button>

                        @if($hasChildren && ($categoryActive || $childActive))
                            <div class="ml-6 border-l border-outline-variant pl-3 mt-1 flex flex-col gap-1">
                                @foreach($category->children as $child)
                                    @php
                                        $childId = $child->id;
                                        $isChildActive = (string) $activeCategoryId === (string) $childId;
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="$set('activeCategoryId', '{{ $childId }}')"
                                        class="text-left py-1 text-body-md transition-colors rounded-lg px-2 w-full {{ $isChildActive ? 'ecc-text-primary font-bold bg-surface-container-high' : 'text-on-surface-variant hover:ecc-text-primary hover:bg-surface-container-high' }}"
                                    >
                                        {{ $child->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>

            {{-- Price Range Selector --}}
            <div class="mt-8 border-t border-outline-variant pt-6 flex flex-col gap-2">
                <div class="text-label-sm font-label-sm text-secondary tracking-widest uppercase mb-2 px-2">Price Range</div>
                <div class="px-2 shop-range-wrap">
                    @php
                        $absoluteMin = (int) ($absoluteMinPrice ?? 0);
                        $absoluteMax = (int) ($absoluteMaxPrice ?? 1500);
                        $selectedMin = (int) ($minPrice ?? $absoluteMin);
                        $selectedMax = (int) ($maxPrice ?? $absoluteMax);

                        $rangeSpan = max(1, $absoluteMax - $absoluteMin);
                        $leftPercent = (($selectedMin - $absoluteMin) / $rangeSpan) * 100;
                        $rightPercent = (($selectedMax - $absoluteMin) / $rangeSpan) * 100;
                    @endphp

                    <div class="h-1 bg-surface-variant rounded-full relative mt-4 mb-2 cursor-pointer" onclick="handleTrackClick(event, this.closest('.shop-range-wrap'))">
                        <div
                            class="absolute h-full bg-primary rounded-full"
                            style="left: {{ max(0, min(100, $leftPercent)) }}%; width: {{ max(0, min(100, $rightPercent - $leftPercent)) }}%;"
                        ></div>

                        <span
                            class="absolute -top-2 w-5 h-5 bg-surface border-2 border-primary rounded-full shadow-sm -translate-x-1/2 pointer-events-none z-10"
                            style="left: {{ max(0, min(100, $leftPercent)) }}%;"
                        ></span>

                        <span
                            class="absolute -top-2 w-5 h-5 bg-surface border-2 border-primary rounded-full shadow-sm -translate-x-1/2 pointer-events-none z-10"
                            style="left: {{ max(0, min(100, $rightPercent)) }}%;"
                        ></span>
                    </div>

                    <div class="relative h-5 -mt-4">
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

                    <div class="flex justify-between text-label-sm font-label-sm text-secondary mt-2">
                        <span class="range-min-label">{{ $currencySymbol ?? '₹' }}{{ number_format($selectedMin) }}</span>
                        <span class="range-max-label">{{ $currencySymbol ?? '₹' }}{{ number_format($selectedMax) }}{{ ($absoluteMax === $selectedMax) ? '+' : '' }}</span>
                    </div>
                </div>
            </div>

            {{-- Brands & Tags Selector --}}
            @foreach($tagGroups as $group)
                @php
                    $groupName = $group->name;
                    $groupSlug = $group->slug;
                    $groupTags = $group->tags;
                    $renderAsPills = in_array($groupSlug, ['size', 'sizes']) || ($group->type ?? null) === 'pill';
                @endphp

                @if($groupTags->isNotEmpty())
                    <div class="mt-8 border-t border-outline-variant pt-6">
                        <div class="text-label-sm font-label-sm text-secondary tracking-widest uppercase mb-4 px-2">{{ $groupName }}</div>

                        @if($renderAsPills)
                            <div class="grid grid-cols-3 gap-2 px-2">
                                @foreach($groupTags as $tag)
                                    @php
                                        $tagId = $tag->id;
                                        $tagName = $tag->name;
                                        $isSelected = in_array($tagId, $tags);
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="toggleTag('{{ $tagId }}')"
                                        class="py-2 px-1 border rounded-xl text-center text-label-sm font-label-sm uppercase tracking-widest transition-all {{ $isSelected ? 'border-primary bg-primary-container text-on-primary-container font-bold' : 'border-outline text-secondary hover:bg-surface-container' }}"
                                    >
                                        {{ $tagName }}
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col gap-3 px-2">
                                @foreach($groupTags as $tag)
                                    @php
                                        $tagId = $tag->id;
                                        $tagName = $tag->name;
                                        $isSelected = in_array($tagId, $tags);
                                    @endphp
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input
                                            class="w-4 h-4 border border-outline rounded-[2px] text-primary focus:ring-primary accent-primary cursor-pointer"
                                            type="checkbox"
                                            wire:click="toggleTag('{{ $tagId }}')"
                                            @checked($isSelected)
                                        >
                                        <span class="text-body-md font-body-md text-on-surface-variant group-hover:text-primary transition-colors">{{ $tagName }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </aside>

        {{-- Main Product Area --}}
        <main class="flex-1 w-full min-w-0 px-4 md:px-0">
            {{-- Search & Sort Header Panel --}}
            <div class="flex flex-row items-center justify-between gap-2 bg-surface-container-lowest border border-outline-variant px-2 py-2 md:p-4 mb-4 md:mb-8 shadow-sm rounded-xl sticky top-[60px] md:top-24 z-40">
                <div class="hidden md:flex items-center gap-4">
                    <h1 class="text-headline-md font-headline-md text-on-surface whitespace-nowrap">Shop Equipment</h1>
                    <div class="h-6 w-px bg-outline-variant hidden md:block"></div>
                    <p class="text-body-md text-secondary hidden lg:block">Latest additions</p>
                </div>

                {{-- Search Bar --}}
                <div class="flex-1 min-w-0 relative">
                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-secondary text-xs md:text-sm">search</span>
                    <input
                        class="w-full bg-surface-container-low border border-outline-variant text-on-surface pl-8 md:pl-10 pr-2 md:pr-4 py-1.5 md:py-2 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors text-xs md:text-body-md rounded-lg md:rounded-xl"
                        placeholder="Search gear..."
                        type="text"
                        wire:model.live.debounce.400ms="search"
                    />
                </div>

                {{-- Sorting Selector --}}
                <div class="flex items-center gap-1 md:gap-2 shrink-0">
                    <span class="text-label-sm text-secondary uppercase tracking-widest hidden sm:inline-block">Sort:</span>
                    <div class="relative">
                        <select
                            class="appearance-none bg-surface-container-low border border-outline-variant text-on-surface py-1.5 md:py-2 pl-2 md:pl-3 pr-6 md:pr-8 focus:outline-none focus:border-primary text-[11px] md:text-label-sm font-label-sm cursor-pointer rounded-lg md:rounded-xl max-w-[115px] sm:max-w-none text-ellipsis overflow-hidden"
                            wire:model.live="sort"
                        >
                            <option value="newest">Newest</option>
                            <option value="price_asc">Price: Low-High</option>
                            <option value="price_desc">Price: High-Low</option>
                            <option value="title_asc">Name: A-Z</option>
                            <option value="title_desc">Name: Z-A</option>
                        </select>
                        <span class="material-symbols-outlined absolute right-1.5 md:right-2 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-xs md:text-sm">expand_more</span>
                    </div>
                </div>
            </div>

            {{-- Products Grid --}}
            @if(collect($products->items())->count())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($products->items() as $product)
                        @php
                            $productTitle = $product->title;
                            $productSubtitle = $product->categories->first()->name ?? 'Collection';
                            $productPrice = '₹' . number_format((float)$product->base_price, 2);

                            $img = collect($product->images)->first();
                            $productImage = $img ? url('storage/' . $img->image_path) : 'https://placehold.co/800x1000/17130b/d4af37?text=No+Image';

                            $productUrl = route('shop.show', $product->slug);

                            $isNew = $product->created_at ? $product->created_at->diffInDays(now()) < 14 : false;
                            $badgeLabel = $isNew ? 'New' : null;
                            $isSoldOut = $product->computed_stock <= 0;
                        @endphp

                        <article class="bg-surface-container-lowest border border-outline-variant overflow-hidden group hover:shadow-[0_10px_30px_rgba(0,0,0,0.06)] transition-all duration-300 flex flex-col h-full relative rounded-xl {{ $isSoldOut ? 'opacity-75' : '' }}" x-data="{ activeImage: '{{ $productImage }}', activeSwatch: null }">
                            <a href="{{ $productUrl }}" class="absolute inset-0 z-10"></a>
                            
                            {{-- Product Media --}}
                            <div class="aspect-[4/5] bg-surface-variant overflow-hidden relative">
                                @if($isSoldOut)
                                    <span class="absolute top-3 left-3 bg-inverse-surface text-inverse-on-surface text-label-sm font-label-sm px-2 py-1 uppercase tracking-widest z-10 rounded-sm">Sold Out</span>
                                @elseif(!empty($badgeLabel))
                                    <span class="absolute top-3 left-3 bg-primary text-on-primary text-label-sm font-label-sm px-2 py-1 uppercase tracking-widest z-10 rounded-sm">{{ $badgeLabel }}</span>
                                @endif
                                <img :src="activeImage" alt="{{ $productTitle }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 {{ $isSoldOut ? 'grayscale-[0.2]' : '' }}"/>
                            </div>

                            {{-- Swatches hover preview --}}
                            @if($product->variationGroups->isNotEmpty())
                                @php
                                    $showcaseGroup = $product->variationGroups->firstWhere('show_on_list', true);
                                @endphp
                                @if($showcaseGroup && $showcaseGroup->values->isNotEmpty())
                                    <div class="px-5 pt-4 flex gap-2 overflow-x-auto pb-2 relative z-20">
                                        @foreach($showcaseGroup->values as $value)
                                            @php
                                                $swatchUrl = route('shop.show', ['slug' => $product->slug, 'v' => [$showcaseGroup->id => $value->id]]);
                                                $hoverImageUrl = $value->presentation_image_path ? url('storage/' . $value->presentation_image_path) : $productImage;
                                            @endphp
                                            @if($showcaseGroup->presentation_type === 'image' || $value->presentation_image_path)
                                                <img
                                                    class="w-10 h-10 rounded-sm border border-outline-variant cursor-pointer hover:border-primary transition-colors object-cover flex-shrink-0"
                                                    @mouseover="activeImage = '{{ $hoverImageUrl }}'; activeSwatch = {{ $value->id }}"
                                                    :class="{ 'border-primary': activeSwatch === {{ $value->id }} }"
                                                    src="{{ $hoverImageUrl }}"
                                                />
                                            @elseif($showcaseGroup->presentation_type === 'color' && $value->color_hex)
                                                <span
                                                    class="w-10 h-10 rounded-sm border border-outline-variant cursor-pointer hover:border-primary transition-colors flex items-center justify-center flex-shrink-0"
                                                    @mouseover="activeSwatch = {{ $value->id }}"
                                                    style="background-color: {{ $value->color_hex }};"
                                                ></span>
                                            @else
                                                <span
                                                    class="w-10 h-10 rounded-sm border border-outline-variant cursor-pointer hover:border-primary transition-colors flex items-center justify-center text-xs font-bold px-1 flex-shrink-0"
                                                    @mouseover="activeSwatch = {{ $value->id }}"
                                                >
                                                    {{ $value->caption }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            @endif

                            {{-- Product Content & CTA --}}
                            <div class="p-5 flex flex-col flex-1 border-t border-outline-variant relative z-0 bg-surface-container-lowest">
                                <h2 class="text-headline-md font-headline-md text-on-surface line-clamp-2 mb-1">{{ $productTitle }}</h2>
                                <span class="text-label-sm font-label-sm text-secondary uppercase tracking-widest mb-3">{{ $productSubtitle }}</span>
                                <div class="text-headline-md font-headline-md text-primary mt-auto mb-4">{{ $productPrice }}</div>

                                @if($isSoldOut)
                                    <button class="w-full bg-surface-container-high text-on-surface-variant py-3 px-4 flex items-center justify-center gap-2 cursor-not-allowed text-label-sm font-label-sm uppercase tracking-widest h-12 border border-outline-variant rounded-xl relative z-20" disabled="">
                                        Out of Stock
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click.stop="addToCart({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $product->id }})"
                                        class="w-full bg-primary-container text-on-primary-container py-3 px-4 flex items-center justify-center gap-2 hover:bg-primary hover:text-on-primary transition-colors text-label-sm font-label-sm uppercase tracking-widest h-12 rounded-xl relative z-20"
                                    >
                                        <span wire:loading.remove wire:target="addToCart({{ $product->id }})" class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">shopping_cart</span>Add to Cart
                                        </span>
                                        <span wire:loading wire:target="addToCart({{ $product->id }})">
                                            Adding...
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="mt-12 flex justify-center shop-pagination-wrap">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-12 text-secondary text-body-lg">
                    No products found matching the selected filters.
                </div>
            @endif
        </main>
    </div>

    {{-- QUICK VIEW MODAL OVERLAY --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 backdrop-blur-sm transition-opacity duration-300 {{ $quickViewProduct ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}">
        @if($quickViewProduct)
            <div class="bg-surface border border-outline-variant rounded-2xl w-11/12 max-w-2xl shadow-2xl overflow-hidden relative p-8 text-on-background max-h-[90vh] overflow-y-auto">
                <button type="button" class="absolute top-4 right-4 w-9 h-9 rounded-full border border-outline-variant bg-surface hover:bg-primary hover:text-on-primary hover:border-primary flex items-center justify-center transition-all cursor-pointer z-10" wire:click="closeQuickView" aria-label="Close">
                    <span class="material-symbols-outlined text-base">close</span>
                </button>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- LEFT SIDE: Image Thumbnail --}}
                    <div class="aspect-[4/5] rounded-xl overflow-hidden border border-outline-variant bg-surface-variant flex items-center justify-center">
                        @php
                            $mainImg = collect($currentGallery)->first()['url'] ?? 'https://placehold.co/800x1000/17130b/d4af37?text=No+Image';
                        @endphp
                        <img src="{{ $mainImg }}" alt="{{ $quickViewProduct->title }}" class="w-full h-full object-cover">
                    </div>

                    {{-- RIGHT SIDE: Product details and Selectors --}}
                    <div class="flex flex-col justify-between h-full">
                        <div>
                            <h2 class="text-headline-md font-headline-md text-on-surface mb-2 pr-8">{{ $quickViewProduct->title }}</h2>

                            {{-- Meta line: Color / Size or Category --}}
                            <div class="text-sm text-secondary mb-2">
                                @php
                                    $selectedLabels = [];
                                    foreach ($variationGroups as $group) {
                                        $valId = $selectedVariationValues[$group['id']] ?? null;
                                        if ($valId) {
                                            $val = collect($group['values'])->firstWhere('id', $valId);
                                            if ($val) {
                                                $selectedLabels[] = $group['name'] . ': <span class="text-primary font-bold">' . $val['caption'] . '</span>';
                                            }
                                        }
                                    }
                                @endphp
                                @if(!empty($selectedLabels))
                                    {!! implode(' &bull; ', $selectedLabels) !!}
                                @else
                                    Collection: <span class="text-primary font-bold">{{ $quickViewProduct->categories->first()->name ?? 'ECC Store' }}</span>
                                @endif
                            </div>

                            <a href="{{ route('shop.show', $quickViewProduct->slug) }}" class="text-xs font-bold text-primary uppercase tracking-wider underline hover:text-on-surface transition-colors">
                                See all item details
                            </a>

                            <div class="h-px bg-outline-variant my-4"></div>

                            {{-- DYNAMIC VARIATION SELECTORS --}}
                            @if(count($variationGroups))
                                <div class="flex flex-col gap-4 mb-6">
                                    @foreach($variationGroups as $group)
                                        @php
                                            $groupId = $group['id'];
                                            $groupName = $group['name'];
                                            $selectedValueId = $selectedVariationValues[$groupId] ?? null;
                                        @endphp
                                        <div>
                                            <div class="text-xs font-bold uppercase tracking-wider text-secondary mb-2">{{ $groupName }}:</div>
                                            <select class="w-full bg-surface-container-low border border-outline-variant text-on-surface rounded-xl p-3 text-sm font-semibold outline-none focus:border-primary transition-colors" wire:model.live="selectedVariationValues.{{ $groupId }}">
                                                @foreach($group['values'] as $value)
                                                    @php
                                                        $valueId = $value['id'];
                                                        $label = $value['caption'];
                                                        $isAvailable = in_array((int)$valueId, $availableOptions[$groupId] ?? []);
                                                        $disabled = !$isAvailable;
                                                    @endphp
                                                    <option value="{{ $valueId }}" @selected((string)$selectedValueId === (string)$valueId) @disabled($disabled)>
                                                        {{ $label }} @if($disabled) (Unavailable) @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- BADGES AND PRICE --}}
                            <div class="mt-4">
                                @if($quickViewProduct->is_featured)
                                    <div class="bg-error text-on-error text-xs font-bold px-2 py-1 uppercase tracking-wider rounded-sm w-fit mb-2">Limited time deal</div>
                                @endif

                                <div class="text-headline-lg font-headline-lg text-primary">
                                    <span class="text-base font-bold align-super mr-1">₹</span>{{ number_format((float)($computedPriceDisplay ?? $quickViewProduct->base_price), 2) }}
                                </div>
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="flex items-center justify-end gap-4 mt-6">
                            <button type="button" class="py-2 px-6 border border-outline text-on-surface rounded-full text-sm font-bold hover:bg-surface-container-high transition-all" wire:click="closeQuickView">
                                Cancel
                            </button>
                            <button type="button" class="py-2 px-8 bg-primary text-on-primary rounded-full text-sm font-black tracking-wide shadow-md hover:brightness-110 transition-all disabled:opacity-50 disabled:cursor-not-allowed" wire:click="addQuickViewToCart" wire:loading.attr="disabled" @disabled(!$inStock)>
                                <span wire:loading.remove wire:target="addQuickViewToCart">
                                    Add to cart
                                </span>
                                <span wire:loading wire:target="addQuickViewToCart">
                                    Adding...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Smooth Range Slider UI
    window.updateRangeUI = (el) => {
        const wrap = el.closest('.shop-range-wrap');
        const minInput = wrap.querySelector('.min-range');
        const maxInput = wrap.querySelector('.max-range');
        const activeTrack = wrap.querySelector('.bg-primary');
        const thumbs = wrap.parentElement.querySelectorAll('.w-5.h-5');
        const minLabel = wrap.parentElement.querySelector('.range-min-label');
        const maxLabel = wrap.parentElement.querySelector('.range-max-label');
        
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
        if (e.target.classList.contains('shop-range-native') || e.target.classList.contains('w-5')) return;

        const minInput = wrap.querySelector('.min-range');
        const maxInput = wrap.querySelector('.max-range');
        const track = wrap.querySelector('.bg-surface-variant');
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
        document.querySelectorAll('.shop-range-wrap').forEach(wrap => {
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
