<div class="container-xxl py-5 ecc-order-confirmation-page">
    @push('styles')
    <style>
        .ecc-order-confirmation-page {
            color: #f5efe1;
        }

        .ecc-order-confirmation-shell {
            position: relative;
        }

        .ecc-confirmation-glow {
            position: absolute;
            top: 8%;
            left: 50%;
            transform: translateX(-50%);
            width: 720px;
            height: 720px;
            border-radius: 50%;
            background: rgba(199, 167, 90, 0.06);
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
        }

        .ecc-success-icon-wrap {
            width: 88px;
            height: 88px;
            position: relative;
            z-index: 1;
        }

        .ecc-success-icon {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(199, 167, 90, 0.12);
            border: 1px solid rgba(199, 167, 90, 0.18);
            box-shadow: 0 0 40px rgba(199, 167, 90, 0.10);
            color: var(--ecc-primary);
            font-size: 2.25rem;
        }

        .ecc-confirmation-title {
            font-size: clamp(2.4rem, 5vw, 5rem);
            line-height: .95;
            font-weight: 900;
            letter-spacing: -.05em;
            color: #fff;
            position: relative;
            z-index: 1;
        }

        .ecc-confirmation-subtitle {
            max-width: 620px;
            color: rgba(245, 239, 225, .70);
            font-size: 1.05rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        .ecc-confirmation-card,
        .ecc-privilege-banner {
            background: linear-gradient(180deg, rgba(24,19,10,.92), rgba(17,13,7,.96));
            border: 1px solid rgba(199, 167, 90,.14);
            border-radius: 1rem;
            color: #f5efe1;
            box-shadow: 0 12px 32px rgba(0,0,0,.14);
        }

        .ecc-confirmation-card {
            padding: 1.5rem;
            height: 100%;
            transition: .25s ease;
        }

        .ecc-confirmation-card:hover {
            border-color: rgba(199, 167, 90,.28);
        }

        .ecc-confirmation-image {
            width: 100%;
            max-width: 180px;
            aspect-ratio: 1 / 1;
            border-radius: 1rem;
            overflow: hidden;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(245,239,225,.08);
        }

        .ecc-mini-label {
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--ecc-primary);
        }

        .text-muted-gold {
            color: rgba(245, 239, 225, .55);
        }

        .ecc-transaction-id {
            font-size: clamp(1.5rem, 2vw, 2.2rem);
            font-weight: 800;
            letter-spacing: -.03em;
            color: #fff;
        }

        .ecc-summary-divider {
            height: 1px;
            background: rgba(245,239,225,.08);
        }

        .ecc-muted {
            color: rgba(245,239,225,.66);
        }

        .ecc-total-line {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .ecc-total-amount,
        .ecc-text-primary {
            color: var(--ecc-primary);
        }

        .ecc-total-amount {
            font-size: clamp(1.5rem, 2vw, 2rem);
            line-height: 1;
        }

        .ecc-shipping-card {
            min-height: 100%;
        }

        .ecc-btn-primary {
            background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
            border: 1px solid var(--ecc-primary);
            color: #16110a;
            font-weight: 800;
            border-radius: .9rem;
            letter-spacing: .06em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ecc-btn-primary:hover,
        .ecc-btn-primary:focus {
            background: linear-gradient(180deg, #e7c65b, #d5ac34);
            color: #16110a;
        }

        .ecc-btn-dark-outline {
            background: transparent;
            border: 1px solid rgba(199, 167, 90,.22);
            color: #f5efe1;
            font-weight: 800;
            border-radius: .9rem;
            letter-spacing: .04em;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ecc-btn-dark-outline:hover,
        .ecc-btn-dark-outline:focus {
            background: rgba(199, 167, 90,.06);
            border-color: rgba(199, 167, 90,.4);
            color: #fff;
        }

        .ecc-privilege-banner {
            padding: 1.75rem;
        }

        .ecc-privilege-accent {
            position: absolute;
            top: 0;
            right: -80px;
            width: 260px;
            height: 100%;
            background: linear-gradient(180deg, rgba(199, 167, 90,.04), rgba(199, 167, 90,.08));
            transform: skewX(-18deg);
            pointer-events: none;
        }

        .ecc-privilege-title {
            font-size: 1.35rem;
            font-style: italic;
            font-weight: 800;
            color: #fff;
        }

        .ecc-privilege-link {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            color: var(--ecc-primary);
            font-size: .85rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-decoration: none;
            white-space: nowrap;
        }

        .ecc-privilege-link:hover {
            color: #e7c65b;
        }

        @media (max-width: 767.98px) {
            .ecc-confirmation-card,
            .ecc-privilege-banner {
                padding: 1.25rem;
            }

            .ecc-confirmation-image {
                max-width: 100%;
            }
        }
    </style>
    @endpush

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="ecc-order-confirmation-shell position-relative">

                <!-- Decorative glow -->
                <div class="ecc-confirmation-glow d-none d-md-block"></div>

                <!-- Success Header -->
                <section class="text-center mb-5 mb-xl-6 position-relative">
                    <div class="ecc-success-icon-wrap mx-auto mb-4">
                        <div class="ecc-success-icon">
                            <span class="material-symbols-outlined" style="font-size: 2.5rem;">check_circle</span>
                        </div>
                    </div>

                    <h1 class="ecc-confirmation-title mb-3 text-uppercase">Order Confirmed!</h1>

                    <p class="ecc-confirmation-subtitle mx-auto mb-0">
                        Your acquisition has been authenticated and secured successfully.
                        A confirmation email is on its way.
                    </p>
                </section>

                <!-- Main Content -->
                <section>
                    <div class="row g-4 align-items-stretch">

                        <!-- Main Order Card -->
                        <div class="col-12 col-lg-8">
                            <div class="ecc-confirmation-card h-100 shadow-lg">
                                <div class="row g-4 align-items-start">
                                    <div class="col-12 col-md-auto">
                                        <div class="ecc-confirmation-image">
                                            <img src="{{ $heroItemImageUrl }}"
                                                 alt="{{ $heroItemTitle }}"
                                                 class="w-100 h-100 object-fit-cover">
                                        </div>
                                    </div>

                                    <div class="col">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-4 mb-4">
                                            <div>
                                                <div class="ecc-mini-label mb-2">TRANSACTION ID</div>
                                                <h2 class="ecc-transaction-id mb-0">{{ $displayTransactionReference }}</h2>
                                            </div>

                                            <div class="text-md-end">
                                                <div class="ecc-mini-label text-muted-gold mb-2">EST. DELIVERY</div>
                                                <div class="fw-bold">
                                                    {{ $estimatedDeliveryLabel ?? 'To be confirmed' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ecc-summary-divider"></div>

                                        <div class="d-flex flex-column gap-3 pt-3">
                                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                                <span class="ecc-muted fw-bold">Order Item</span>
                                                <strong class="text-end text-uppercase-less">{{ $heroItemTitle }}</strong>
                                            </div>

                                            @if($discountAmount > 0)
                                                <div class="d-flex justify-content-between gap-3 flex-wrap">
                                                    <span class="ecc-muted fw-bold">Discount Applied</span>
                                                    <strong class="ecc-text-primary">-{{ $formattedDiscountAmount }}</strong>
                                                </div>
                                            @endif

                                            <div class="d-flex justify-content-between gap-3 flex-wrap pt-2 ecc-total-line">
                                                <span class="text-uppercase fw-bold">Total Secured</span>
                                                <span class="ecc-total-amount fw-900">{{ $formattedGrandTotal }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="d-flex flex-row gap-3 mt-4">
                                <a href="{{ $continueShoppingUrl }}"
                                   class="btn ecc-btn-primary flex-fill py-3 text-uppercase-less fw-bold">
                                    Continue Shopping
                                </a>

                                <a href="{{ route('shop.order-details', $order->id) }}"
                                   class="btn ecc-btn-dark-outline flex-fill py-3 text-uppercase-less fw-bold">
                                    View Order Details
                                </a>
                            </div>
                        </div>

                        <!-- Right Side Panel -->
                        <div class="col-12 col-lg-4">
                            <div class="d-flex flex-column gap-4 h-100">

                                <!-- Shipping Card -->
                                <div class="ecc-confirmation-card ecc-shipping-card shadow-lg">
                                    <div class="d-flex align-items-center gap-2 mb-3 ecc-text-primary">
                                        <span class="material-symbols-outlined fs-5">lock</span>
                                        <span class="ecc-mini-label mb-0">WHITE GLOVE DELIVERY</span>
                                    </div>

                                    <div class="fw-bold fs-5 mb-2 text-uppercase-less">
                                        {{ $shippingRecipientOrTitle }}
                                    </div>

                                    <div class="ecc-muted lh-lg small">
                                        {!! nl2br(e($shippingAddressBlock)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
