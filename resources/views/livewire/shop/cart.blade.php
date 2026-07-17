<div class="ecc-cart-page">
    @push('styles')
        <style>
            .ecc-cart-page {
                background:
                    radial-gradient(circle at top left, rgba(212, 175, 53, 0.05) 0%, transparent 50%),
                    linear-gradient(180deg, var(--ecc-bg-page), var(--ecc-bg-surface));
                color: var(--ecc-text-secondary);
                min-height: 100vh;
                padding-bottom: 5rem;
            }

            .ecc-cart-shell {
                max-width: 1320px;
                margin: 0 auto;
                padding: 3rem 1.25rem 4rem;
            }

            .ecc-cart-hero {
                margin-bottom: 2.5rem;
            }

            .ecc-cart-title {
                font-size: clamp(2.8rem, 5vw, 5rem);
                line-height: 0.95;
                font-weight: 900;
                letter-spacing: -0.05em;
                color: var(--ecc-primary);
                margin: 0 0 0.5rem;
            }

            .ecc-cart-subtitle {
                color: var(--ecc-text-muted);
                font-size: 1rem;
                font-weight: 500;
                margin: 0;
            }

            .ecc-cart-item {
                background: var(--ecc-bg-surface);
                border: 1px solid var(--ecc-border-soft);
                border-radius: 1rem;
                padding: 1.25rem;
                transition: all .2s ease;
            }

            .ecc-cart-item:hover {
                background: var(--ecc-bg-surface-2);
                border-color: var(--ecc-text-primary);
            }

            .ecc-cart-thumb {
                width: 100%;
                max-width: 160px;
                aspect-ratio: 1 / 1;
                border-radius: 0.85rem;
                overflow: hidden;
                background: var(--ecc-bg-page);
                border: 1px solid var(--ecc-border-soft);
                flex: 0 0 auto;
            }

            .ecc-cart-thumb img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .ecc-cart-item-title {
                font-size: clamp(1.25rem, 4vw, 1.65rem);
                font-weight: 800;
                letter-spacing: -0.03em;
                color: var(--ecc-text-primary);
                margin: 0;
            }

            .ecc-cart-meta {
                color: var(--ecc-text-muted);
                font-size: 0.98rem;
                font-weight: 500;
            }

            .ecc-cart-price {
                color: var(--ecc-primary);
                font-size: clamp(1.4rem, 4.5vw, 1.85rem);
                font-weight: 900;
                letter-spacing: -0.03em;
                white-space: nowrap;
            }

            .ecc-qty-pill {
                display: inline-flex;
                align-items: center;
                background: var(--ecc-bg-page);
                border: 1px solid var(--ecc-border);
                border-radius: 999px;
                overflow: hidden;
            }

            .ecc-qty-btn {
                width: 40px;
                height: 40px;
                border: 0;
                background: transparent;
                color: var(--ecc-text-primary);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.15rem;
                line-height: 1;
                transition: color .2s ease;
            }

            .ecc-qty-btn:hover {
                color: var(--ecc-primary);
            }

            .ecc-qty-value {
                min-width: 40px;
                text-align: center;
                color: var(--ecc-text-primary);
                font-weight: 800;
                font-size: 0.95rem;
            }

            .ecc-remove-btn {
                border: 0;
                background: transparent;
                color: var(--ecc-danger);
                font-size: 0.75rem;
                font-weight: 800;
                letter-spacing: 0.18em;
                text-transform: uppercase;
                transition: color .2s ease;
            }

            .ecc-remove-btn:hover {
                color: #ffb4ab;
            }

            .ecc-summary-card {
                background: var(--ecc-bg-surface);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid var(--ecc-border-soft);
                border-radius: 1rem;
                padding: 1.75rem;
            }

            @media (min-width: 992px) {
                .ecc-summary-card {
                    position: sticky;
                    top: 120px;
                }
            }

            .ecc-summary-title {
                font-size: 2rem;
                font-weight: 900;
                letter-spacing: -0.03em;
                color: var(--ecc-text-primary);
                margin-bottom: 1.75rem;
            }

            .ecc-summary-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                color: var(--ecc-text-muted);
                font-size: 1rem;
                margin-bottom: 1rem;
            }

            .ecc-summary-row strong {
                color: var(--ecc-text-primary);
                font-weight: 800;
            }

            .ecc-summary-free {
                color: var(--ecc-primary);
                font-size: 0.82rem;
                font-weight: 900;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .ecc-summary-total {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding-top: 1.25rem;
                margin-top: 1.25rem;
                border-top: 1px solid var(--ecc-text-primary);
            }

            .ecc-summary-total .label {
                color: var(--ecc-text-primary);
                font-size: clamp(1.4rem, 4vw, 1.7rem);
                font-weight: 800;
            }

            .ecc-summary-total .amount {
                color: var(--ecc-primary);
                font-size: clamp(1.4rem, 6vw, 3rem);
                line-height: 1;
                font-weight: 900;
                letter-spacing: -0.04em;
                word-break: break-all;
                overflow-wrap: break-word;
            }

            .ecc-checkout-btn {
                width: 100%;
                min-height: 58px;
                border: 0;
                border-radius: 0.9rem;
                background: var(--ecc-primary);
                color: #120f08;
                font-size: 1.1rem;
                font-weight: 900;
                padding: 0 1.5rem;
                margin-top: 1.5rem;
                box-shadow: 0 12px 28px rgba(212,175,53,0.14);
                transition: transform .2s ease, filter .2s ease;
            }

            .ecc-checkout-btn:hover {
                transform: translateY(-2px);
                filter: brightness(1.1);
            }

            .ecc-checkout-btn:disabled {
                background: #3d392f;
                color: var(--ecc-text-muted);
                box-shadow: none;
                cursor: not-allowed;
            }

            .ecc-checkout-note {
                color: rgba(195, 185, 152, 0.55);
                font-size: 0.72rem;
                text-align: center;
                margin-top: 0.9rem;
                line-height: 1.5;
            }

            .ecc-secure-note {
                display: flex;
                align-items: center;
                gap: 0.7rem;
                padding-top: 1.5rem;
                margin-top: 1.5rem;
                border-top: 1px solid var(--ecc-text-primary);
                color: var(--ecc-text-muted);
                font-size: 0.8rem;
                font-weight: 900;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            .ecc-secure-note i {
                color: var(--ecc-primary);
            }

            /* Toast/Alert Styles consistency */
            .alert-gold {
                background: rgba(212, 175, 53, 0.1);
                border: 1px solid rgba(212, 175, 53, 0.2);
                color: var(--ecc-primary);
                border-radius: 12px;
            }
        </style>
    @endpush

    <div class="ecc-cart-shell">
        <header class="ecc-cart-hero">
            <h1 class="ecc-cart-title">My Cart</h1>
            <p class="ecc-cart-subtitle">Reviewing your curated selection from the 2024 Archive.</p>
        </header>

        @if(session()->has('error'))
            <div class="alert alert-gold mb-4" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="row g-4 g-xl-5 align-items-start">
            <section class="col-12 col-lg-8">
                <div class="d-flex flex-column gap-4">
                    @forelse(($cartItems ?? []) as $item)
                        @php
                            $image = $item['image_url'] ?? null;
                            $title = $item['title'] ?? 'Item';
                            $meta = $item['variant_summary'] ?? '';
                            $linePrice = $item['line_total_display'] ?? '';
                            $qty = $item['quantity'] ?? 1;
                            $id = $item['id'] ?? null;
                        @endphp

                        <article class="ecc-cart-item" wire:key="cart-item-{{ $id }}">
                            <div class="d-flex flex-column flex-sm-row align-items-center gap-4 gap-lg-5">
                                <div class="ecc-cart-thumb">
                                    @if($image)
                                        <img src="{{ $image }}" alt="{{ $title }}">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-secondary">
                                            <i class="mdi mdi-image-outline fs-1"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-grow-1 text-center text-sm-start w-100">
                                    <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start justify-content-between gap-2 mb-2">
                                        <h3 class="ecc-cart-item-title">{{ $title }}</h3>
                                        <div class="ecc-cart-price">{{ $linePrice }}</div>
                                    </div>

                                    @if(!empty($meta))
                                        <div class="ecc-cart-meta mb-2">{{ $meta }}</div>
                                    @endif

                                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-3 mt-4">
                                        <div class="ecc-qty-pill">
                                            <button 
                                                type="button" 
                                                class="ecc-qty-btn" 
                                                wire:click="decrementQuantity({{ $id }})"
                                                wire:loading.attr="disabled"
                                            >−</button>
                                            <span class="ecc-qty-value">{{ $qty }}</span>
                                            <button 
                                                type="button" 
                                                class="ecc-qty-btn" 
                                                wire:click="incrementQuantity({{ $id }})"
                                                wire:loading.attr="disabled"
                                            >+</button>
                                        </div>

                                        <button 
                                            type="button" 
                                            class="ecc-remove-btn" 
                                            wire:click="removeItem({{ $id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="ecc-cart-item text-center py-5">
                            <h3 class="mb-2 ecc-text-primary fw-bold">Your cart is empty.</h3>
                            <p class="mb-4 ecc-text-muted-emphasis">Start adding curated items from the club store.</p>
                            <a href="{{ route('shop.index') }}" class="shop-card-view-btn d-inline-flex px-4" style="text-decoration: none;">
                                Continue Shopping
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="col-12 col-lg-4">
                <div class="ecc-summary-card">
                    <h2 class="ecc-summary-title">Summary</h2>

                    <div class="ecc-summary-row">
                        <span>Subtotal</span>
                        <strong>{{ $summary['subtotal_display'] ?? '₹0.00' }}</strong>
                    </div>

                    <div class="ecc-summary-row">
                        <span>Estimated Tax</span>
                        <strong>{{ $summary['tax_display'] ?? '₹0.00' }}</strong>
                    </div>

                    <div class="ecc-summary-total">
                        <span class="label">Total</span>
                        <span class="amount">{{ $summary['total_display'] ?? '₹0.00' }}</span>
                    </div>

                    <button 
                        type="button" 
                        class="ecc-checkout-btn" 
                        wire:click="proceedToCheckout"
                        @disabled(empty($cartItems))
                    >
                        Proceed to Checkout
                    </button>

                    <p class="ecc-checkout-note">
                        By clicking checkout, you agree to our heritage preservation terms and premium shipping conditions.
                    </p>

                    <div class="ecc-secure-note">
                        <i class="mdi mdi-shield-check"></i>
                        <span>Secure Archive Checkout</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
