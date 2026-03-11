<div class="ecc-shop-product-page">
    @push('styles')
        <style>
            .ecc-shop-product-page {
                background:
                    radial-gradient(circle at top, rgba(242, 185, 13, 0.08), transparent 26%),
                    linear-gradient(180deg, #221e10 0%, #1d190d 100%);
                min-height: 100%;
                color: #fff;
            }

            .ecc-shop-product-shell {
                width: 100%;
                max-width: 1120px;
                margin: 0 auto;
                padding: 0 0 6.5rem;
            }

            @media (min-width: 992px) {
                .ecc-shop-product-shell {
                    padding-bottom: 2rem;
                }
            }

            .ecc-shop-topbar {
                position: sticky;
                top: 0;
                z-index: 40;
                background: rgba(34, 30, 16, 0.94);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(255,255,255,0.05);
                padding: 1rem 1rem 0.75rem;
            }

            .ecc-shop-topbar-inner {
                max-width: 1120px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .ecc-shop-icon-btn {
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

            .ecc-shop-icon-btn:hover {
                background: rgba(255,255,255,0.08);
            }

            .ecc-shop-page-title {
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

            .ecc-product-layout {
                max-width: 1120px;
                margin: 0 auto;
            }

            @media (min-width: 992px) {
                .ecc-product-layout {
                    display: grid;
                    grid-template-columns: minmax(0, 1.05fr) minmax(360px, 440px);
                    gap: 2rem;
                    align-items: start;
                    padding: 1.5rem 1.5rem 0;
                }
            }

            .ecc-gallery-col,
            .ecc-detail-col {
                min-width: 0;
            }

            .ecc-gallery-wrap {
                position: relative;
                background: #16130b;
            }

            .ecc-main-media {
                position: relative;
                width: 100%;
                aspect-ratio: 4 / 5;
                overflow: hidden;
                background: #16130b;
            }

            .ecc-main-media img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }



            .ecc-gallery-dots {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 1rem;
                display: flex;
                justify-content: center;
                gap: 0.45rem;
            }

            .ecc-gallery-dot {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: rgba(255,255,255,0.45);
                border: 0;
                padding: 0;
            }

            .ecc-gallery-dot.active {
                background: #f2b90d;
                box-shadow: 0 0 0 2px rgba(242,185,13,0.18);
            }

            .ecc-detail-card {
                background: rgba(45, 40, 24, 0.96);
                padding: 1.25rem 1.25rem 1.5rem;
            }

            @media (min-width: 992px) {
                .ecc-detail-card {
                    border-radius: 1rem;
                    border: 1px solid rgba(255,255,255,0.05);
                }
            }

            .ecc-product-title-row {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .ecc-product-title-main {
                margin: 0;
                color: #fff;
                font-size: 2rem;
                line-height: 1.08;
                font-weight: 800;
                letter-spacing: -0.03em;
            }

            .ecc-product-price-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
                margin-top: 0.6rem;
            }

            .ecc-product-price {
                color: #f2b90d;
                font-size: 2.35rem;
                line-height: 1;
                font-weight: 800;
                letter-spacing: -0.03em;
            }

            .ecc-stock-badge {
                display: inline-flex;
                align-items: center;
                height: 32px;
                padding: 0 0.8rem;
                border-radius: 0.5rem;
                font-size: 0.85rem;
                font-weight: 800;
                text-transform: uppercase;
                background: rgba(242,185,13,0.18);
                color: #f2b90d;
            }
            
            .ecc-stock-badge.out-of-stock {
                background: rgba(220, 53, 69, 0.18);
                color: #ff6b6b;
            }

            .ecc-rating-row {
                display: flex;
                align-items: center;
                gap: 0.2rem;
                margin-top: 0.85rem;
                color: #cbbc90;
                flex-wrap: wrap;
            }

            .ecc-rating-row .star {
                color: #f2b90d;
            }

            .ecc-divider {
                margin: 1.5rem 0;
                height: 1px;
                background: rgba(255,255,255,0.08);
            }

            .ecc-section-label-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 0.85rem;
            }

            .ecc-section-label {
                margin: 0;
                color: #fff;
                font-size: 0.98rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .ecc-section-link {
                color: #f2b90d;
                font-size: 0.85rem;
                font-weight: 700;
                text-decoration: none;
            }

            .ecc-option-row {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
            }

            .ecc-option-btn {
                min-width: 54px;
                height: 54px;
                padding: 0 1rem;
                border-radius: 0.8rem;
                border: 1px solid rgba(255,255,255,0.18);
                background: transparent;
                color: #fff;
                font-weight: 700;
                position: relative;
                transition: all .2s ease;
            }

            .ecc-option-btn:hover {
                border-color: #f2b90d;
            }

            .ecc-option-btn.active {
                background: #f2b90d;
                color: #000;
                border-color: #f2b90d;
                box-shadow: 0 10px 20px rgba(242,185,13,0.16);
            }

            .ecc-option-btn.disabled,
            .ecc-option-btn:disabled {
                opacity: 0.35;
                cursor: not-allowed;
                border-color: rgba(255,255,255,0.12);
            }

            .ecc-option-btn.disabled::after {
                content: '';
                position: absolute;
                left: 8px;
                right: 8px;
                top: 50%;
                height: 1px;
                background: rgba(255,255,255,0.35);
                transform: rotate(-25deg);
            }

            .ecc-color-swatch {
                width: 42px;
                height: 42px;
                border-radius: 999px;
                border: 2px solid transparent;
                position: relative;
                transition: all .2s ease;
                box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
            }

            .ecc-color-swatch.active {
                border-color: #f2b90d;
                box-shadow: 0 0 0 4px rgba(242,185,13,0.12);
            }

            .ecc-image-option {
                width: 72px;
                height: 72px;
                border-radius: 0.85rem;
                overflow: hidden;
                border: 2px solid transparent;
                padding: 0;
                background: #1a1a1a;
                transition: all .2s ease;
            }

            .ecc-image-option img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .ecc-image-option.active {
                border-color: #f2b90d;
                box-shadow: 0 10px 20px rgba(242,185,13,0.14);
            }

            .ecc-description-text {
                color: #d7d2c4;
                font-size: 1.02rem;
                line-height: 1.8;
            }

            .ecc-description-list {
                margin: 0.85rem 0 0;
                padding-left: 1.25rem;
                color: #d7d2c4;
            }

            .ecc-description-list li {
                margin-bottom: 0.55rem;
            }

            .ecc-description-list li::marker {
                color: #f2b90d;
            }

            .ecc-sticky-cartbar {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 70px;
                z-index: 35;
                background: rgba(34, 30, 16, 0.97);
                border-top: 1px solid rgba(255,255,255,0.06);
                padding: 0.9rem 1rem;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }

            @media (min-width: 992px) {
                .ecc-sticky-cartbar {
                    position: sticky;
                    bottom: 0;
                    max-width: 1120px;
                    margin: 0 auto;
                    padding: 1rem 0 0;
                    background: transparent;
                    border-top: 0;
                    backdrop-filter: none;
                    -webkit-backdrop-filter: none;
                }
            }

            .ecc-sticky-cartbar-inner {
                max-width: 1120px;
                margin: 0 auto;
                display: flex;
                gap: 1rem;
                align-items: center;
            }

            .ecc-qty-box {
                height: 56px;
                min-width: 122px;
                border-radius: 0.85rem;
                background: rgba(255,255,255,0.06);
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 0.5rem;
                flex: 0 0 auto;
            }

            .ecc-qty-btn {
                width: 40px;
                height: 40px;
                border-radius: 0.7rem;
                border: 0;
                background: transparent;
                color: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .ecc-qty-value {
                min-width: 28px;
                text-align: center;
                font-weight: 800;
                color: #fff;
            }

            .ecc-addcart-btn {
                flex: 1 1 auto;
                min-height: 56px;
                border: 0;
                border-radius: 0.85rem;
                background: #f2b90d;
                color: #000;
                font-weight: 800;
                font-size: 1.1rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.45rem;
                box-shadow: 0 14px 28px rgba(242,185,13,0.16);
                transition: opacity 0.2s ease;
            }
            
            .ecc-addcart-btn:disabled, .ecc-addcart-btn.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .ecc-addcart-btn .price-frag {
                font-size: 0.95rem;
                opacity: 0.85;
                font-weight: 700;
            }
        </style>
    @endpush

    <div class="ecc-shop-product-shell">
        <div class="ecc-shop-topbar">
            <div class="ecc-shop-topbar-inner">
                <a href="{{ route('shop.index') }}" class="ecc-shop-icon-btn" aria-label="Go back">
                    <span class="material-symbols-outlined fs-5">arrow_back</span>
                </a>

                <h1 class="ecc-shop-page-title">Club Store</h1>

                <div style="width: 42px;"></div>
            </div>
        </div>
        
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show mx-3 mt-3 shadow-sm border-0 bg-success text-white" role="alert" style="background: rgba(40, 167, 69, 0.2) !important; color: #72cf85 !important;">
                 <span class="material-symbols-outlined me-2 align-middle">check_circle</span>
                 <span class="align-middle">{{ session('success') }}</span>
                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 shadow-sm border-0" role="alert" style="background: rgba(220, 53, 69, 0.2) !important; color: #ff6b6b !important;">
                 <span class="material-symbols-outlined me-2 align-middle">error</span>
                 <span class="align-middle">{{ session('error') }}</span>
                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="ecc-product-layout">
            <div class="ecc-gallery-col">
                @php
                    $gallery = current($currentGallery) ?: $currentGallery; // Handle edge cases
                    $mainImage = $currentGallery[$selectedMediaIndex]['url'] ?? $currentGallery[0]['url'] ?? null;
                @endphp

                <div class="ecc-gallery-wrap">
                    <div class="ecc-main-media">
                        @if($mainImage)
                            <img src="{{ $mainImage }}" alt="{{ $product['title'] ?? 'Product image' }}">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                <span class="material-symbols-outlined fs-1">image_not_supported</span>
                            </div>
                        @endif



                        @if(!empty($currentGallery) && count($currentGallery) > 1)
                            <div class="ecc-gallery-dots">
                                @foreach($currentGallery as $index => $media)
                                    <button
                                        type="button"
                                        class="ecc-gallery-dot {{ (int)($selectedMediaIndex ?? 0) === (int)$index ? 'active' : '' }}"
                                        wire:click="selectMedia({{ $index }})"
                                        aria-label="Select image {{ $index + 1 }}"
                                    ></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="ecc-detail-col">
                <div class="ecc-detail-card">
                    <div class="ecc-product-title-row">
                        <div>
                            <h2 class="ecc-product-title-main">
                                {{ $product->title ?? 'Product' }}
                                @if(!empty($product->is_featured ?? false))
                                    <span class="text-warning">★</span>
                                @endif
                            </h2>
                        </div>

                        <button type="button" class="ecc-shop-icon-btn" aria-label="Wishlist" wire:click="$refresh">
                            <span class="material-symbols-outlined fs-4">bookmark_border</span>
                        </button>
                    </div>

                    <div class="ecc-product-price-row">
                        <span class="ecc-product-price">
                            {{ $displayPrice ?? 'INR' }} {{ $computedPriceDisplay ?? number_format($product->base_price, 2) }}
                        </span>

                        @if($availabilityLabel === 'Out of Stock')
                            <span class="ecc-stock-badge out-of-stock">Out of Stock</span>
                        @elseif($availabilityLabel)
                            <span class="ecc-stock-badge">{{ $availabilityLabel }}</span>
                        @else
                            <span class="ecc-stock-badge">In Stock</span>
                        @endif
                    </div>

                    <div class="ecc-divider"></div>

                    @foreach(($variationGroups ?? []) as $group)
                        @php
                            $groupId = $group['id'];
                            $groupName = $group['name'] ?? 'Option';
                            $presentation = $group['presentation_type'] ?? 'text'; // Fallbacks as per API
                            $values = $group['values'] ?? [];
                            $selectedValueId = $selectedVariationValues[$groupId] ?? null;
                            $hasGalleryImages = (bool)($group['has_images'] ?? false);
                        @endphp

                        <div class="mb-4">
                            <div class="ecc-section-label-row">
                                <h3 class="ecc-section-label">{{ $groupName }}</h3>

                                @if(strtolower($group['slug'] ?? '') === 'size')
                                    <a href="#" class="ecc-section-link">Size Guide</a>
                                @endif
                            </div>

                            <div class="ecc-option-row">
                                @foreach($values as $value)
                                    @php
                                        $valueId = $value['id'];
                                        $label = $value['caption'] ?? 'Option'; // API uses 'caption'
                                        $stock = (int)($value['stock_qty'] ?? 0);
                                        $disabled = $stock <= 0;
                                        $isActive = (string)$selectedValueId === (string)$valueId;
                                        $colorHex = $value['color_hex'] ?? null;
                                        $displayImage = $value['presentation_image_path'] ? url('storage/' . $value['presentation_image_path']) : null;
                                    @endphp

                                    @if($presentation === 'color')
                                        <button
                                            type="button"
                                            class="ecc-color-swatch {{ $isActive ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}"
                                            style="background-color: {{ $colorHex ?: '#1a1a1a' }}"
                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                            @if($disabled) disabled @endif
                                            aria-label="{{ $label }}"
                                            title="{{ $label }}{{ $disabled ? ' (Out of stock)' : '' }}"
                                        ></button>
                                    @elseif($presentation === 'image')
                                        <button
                                            type="button"
                                            class="ecc-image-option {{ $isActive ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}"
                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                            @if($disabled) disabled @endif
                                            aria-label="{{ $label }}"
                                            title="{{ $label }}{{ $disabled ? ' (Out of stock)' : '' }}"
                                        >
                                            @if($displayImage)
                                                <img src="{{ $displayImage }}" alt="{{ $label }}">
                                            @else
                                                <span class="d-inline-flex align-items-center justify-content-center w-100 h-100 text-white fw-bold">{{ substr($label, 0, 2) }}</span>
                                            @endif
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="ecc-option-btn {{ $isActive ? 'active' : '' }} {{ $disabled ? 'disabled' : '' }}"
                                            wire:click="selectVariationValue({{ $groupId }}, {{ $valueId }})"
                                            @if($disabled) disabled @endif
                                            title="{{ $label }}{{ $disabled ? ' (Out of stock)' : '' }}"
                                        >
                                            {{ $label }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    @if(!empty($variationGroups))
                        <div class="ecc-divider"></div>
                    @endif

                    <div>
                        <h3 class="ecc-section-label mb-3">Description</h3>
                        <div class="ecc-description-text">{!! \Illuminate\Support\Str::markdown($product->description ?? '') !!}</div>
                    </div>
                </div>

                <div class="ecc-sticky-cartbar">
                    <div class="ecc-sticky-cartbar-inner">
                        <div class="ecc-qty-box">
                            <button type="button" class="ecc-qty-btn" wire:click="decrementQuantity" aria-label="Decrease quantity" @if(!$inStock) disabled @endif>
                                <span class="material-symbols-outlined fs-5">remove</span>
                            </button>

                            <span class="ecc-qty-value">{{ $quantity ?? 1 }}</span>

                            <button type="button" class="ecc-qty-btn" wire:click="incrementQuantity" aria-label="Increase quantity" @if(!$inStock) disabled @endif>
                                <span class="material-symbols-outlined fs-5">add</span>
                            </button>
                        </div>

                        <button 
                            type="button" 
                            class="ecc-addcart-btn" 
                            wire:click="addToCart" 
                            wire:loading.attr="disabled"
                            wire:target="addToCart"
                            @if(!$inStock) disabled @endif
                        >
                            <span wire:loading.remove wire:target="addToCart">
                                {{ $inStock ? 'Add to Cart' : 'Out of Stock' }}
                            </span>
                            <span wire:loading wire:target="addToCart">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Adding...
                            </span>
                            
                            @if($inStock)
                                <span class="price-frag" wire:loading.remove wire:target="addToCart">• {{ $displayPrice ?? 'INR' }} {{ number_format((str_replace(',', '', $computedPriceDisplay) * $quantity), 2) }}</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
