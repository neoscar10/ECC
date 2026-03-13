<div class="container-xxl py-5 ecc-order-detail-page">
    @push('styles')
    <style>
        .ecc-order-detail-page {
            color: #f5efe1;
        }

        .ecc-return-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: #d4af37;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            text-decoration: none;
        }

        .ecc-return-link:hover {
            color: #e6c45b;
        }

        .ecc-order-title {
            font-size: clamp(2.2rem, 4vw, 4.5rem);
            line-height: .95;
            font-weight: 900;
            letter-spacing: -.05em;
            color: #fff;
            margin: 0;
            text-transform: uppercase;
        }

        .ecc-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .7rem 1rem;
            border-radius: .8rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(245,239,225,.08);
            color: #f5efe1;
            font-size: .92rem;
        }

        .ecc-status-pill {
            display: inline-flex;
            align-items: center;
            padding: .7rem 1rem;
            border-radius: .8rem;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .14em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .ecc-status-pill.status-processing {
            color: #f0c859;
            background: rgba(212,175,55,.10);
            border-color: rgba(212,175,55,.18);
        }

        .ecc-status-pill.status-shipped {
            color: #72a8ff;
            background: rgba(62,110,189,.14);
            border-color: rgba(62,110,189,.22);
        }

        .ecc-status-pill.status-delivered {
            color: #35d29a;
            background: rgba(53,210,154,.10);
            border-color: rgba(53,210,154,.18);
        }

        .ecc-status-pill.status-default {
            color: #d4af37;
            background: rgba(212,175,55,.08);
            border-color: rgba(212,175,55,.16);
        }

        .ecc-panel,
        .ecc-summary-card,
        .ecc-support-card {
            background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
            border: 1px solid rgba(212,175,55,.12);
            border-radius: 1.2rem;
            box-shadow: 0 12px 32px rgba(0,0,0,.16);
            color: #f5efe1;
        }

        .ecc-panel {
            overflow: hidden;
        }

        .ecc-panel-header {
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid rgba(245,239,225,.08);
        }

        .ecc-panel-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
        }

        .ecc-panel-subtitle {
            color: rgba(245,239,225,.58);
            font-size: .92rem;
        }

        .ecc-item-list {
            display: flex;
            flex-direction: column;
        }

        .ecc-item-row {
            display: flex;
            gap: 1.25rem;
            padding: 1.5rem 1.75rem;
            border-bottom: 1px solid rgba(245,239,225,.06);
        }

        .ecc-item-row:last-child {
            border-bottom: none;
        }

        .ecc-item-thumb {
            width: 96px;
            min-width: 96px;
            height: 128px;
            border-radius: .9rem;
            overflow: hidden;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(245,239,225,.06);
        }

        .ecc-item-label {
            color: #d4af37;
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
            margin-bottom: .35rem;
        }

        .ecc-item-title {
            color: #fff;
            font-size: 1.08rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .ecc-item-subtitle {
            color: rgba(245,239,225,.62);
            font-size: .9rem;
        }

        .ecc-item-price {
            color: #d4af37;
            font-size: 1.25rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ecc-item-meta {
            color: rgba(245,239,225,.55);
            font-size: .78rem;
            font-weight: 600;
        }

        .ecc-section-icon {
            color: #d4af37;
            font-size: 1.2rem;
        }

        .ecc-section-heading {
            color: #fff;
            font-size: 1.08rem;
            font-weight: 800;
        }

        .ecc-address-body,
        .ecc-payment-body {
            padding: 1.75rem;
        }

        .ecc-muted {
            color: rgba(245,239,225,.66);
        }

        .ecc-payment-card-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: .9rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(245,239,225,.08);
        }

        .ecc-payment-brand-box {
            width: 48px;
            height: 32px;
            border-radius: .6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #181818;
            color: #fff;
            font-size: .7rem;
            font-weight: 900;
            letter-spacing: .04em;
            flex-shrink: 0;
        }

        .ecc-order-summary-wrap {
            position: sticky;
            top: 7rem;
        }

        .ecc-summary-card {
            padding: 1.6rem;
        }

        .ecc-summary-title {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .ecc-summary-total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(245,239,225,.10);
        }

        .ecc-total-label {
            color: #fff;
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: .03em;
        }

        .ecc-total-amount {
            color: #d4af37;
            font-size: clamp(1.8rem, 2vw, 2.4rem);
            font-weight: 900;
            line-height: 1;
        }

        .ecc-currency-note {
            color: rgba(245,239,225,.50);
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
            margin-top: .4rem;
        }

        .ecc-btn-gold {
            background: linear-gradient(180deg, #e0be52, #cfa52b);
            border: 1px solid #d4af37;
            color: #16110a;
            font-weight: 800;
            border-radius: .95rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ecc-btn-gold:hover,
        .ecc-btn-gold:focus {
            background: linear-gradient(180deg, #e7c75c, #d7ad35);
            color: #16110a;
        }

        .ecc-btn-outline-light {
            background: transparent;
            border: 1px solid rgba(245,239,225,.14);
            color: #f5efe1;
            font-weight: 800;
            border-radius: .95rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .ecc-btn-outline-light:hover,
        .ecc-btn-outline-light:focus {
            background: rgba(255,255,255,.04);
            color: #fff;
            border-color: rgba(212,175,55,.28);
        }

        .ecc-summary-note {
            color: rgba(245,239,225,.58);
            font-size: .78rem;
            line-height: 1.8;
            text-align: center;
            font-style: italic;
        }

        .ecc-support-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
        }

        .ecc-support-icon {
            width: 42px;
            height: 42px;
            border-radius: .8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(212,175,55,.10);
            color: #d4af37;
            flex-shrink: 0;
        }

        .ecc-support-link {
            color: #d4af37;
            font-size: .8rem;
            font-weight: 800;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        @media (max-width: 1199.98px) {
            .ecc-order-summary-wrap {
                position: static;
            }
        }

        @media (max-width: 767.98px) {
            .ecc-item-row {
                flex-direction: column;
            }

            .ecc-item-thumb {
                width: 100%;
                height: 220px;
            }
        }
    </style>
    @endpush



    <div class="row justify-content-center">
        <div class="col-12 col-xxl-11">

            <!-- Return -->
            <div class="mb-4 mb-lg-5">
                <a href="{{ $ordersIndexUrl }}"
                   class="ecc-return-link">
                    <span class="material-symbols-outlined fs-6">arrow_back</span>
                    <span>RETURN TO ORDERS</span>
                </a>
            </div>

            <!-- Header -->
            <section class="mb-4 mb-xl-5">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-4">
                    <div>
                        <h1 class="ecc-order-title mb-3">
                            ORDER {{ $displayOrderReference }}
                        </h1>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="ecc-meta-pill">
                                <span class="material-symbols-outlined fs-5">calendar_month</span>
                                <span>Placed on {{ $placedAtLabel }}</span>
                            </div>

                            @if($statusBadgeLabel)
                                <div class="ecc-status-pill {{ $statusBadgeClass }}">
                                    {{ $statusBadgeLabel }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 gap-md-3">
                        @if($invoiceUrl)
                            <a href="{{ $invoiceUrl }}"
                               class="btn ecc-btn-outline-light px-4 py-3">
                                DOWNLOAD INVOICE
                            </a>
                        @endif

                        @if($trackingUrl)
                            <a href="{{ $trackingUrl }}"
                               class="btn ecc-btn-gold px-4 py-3">
                                TRACK SHIPMENT
                            </a>
                        @elseif($canTrackShipment)
                            <button type="button"
                                    class="btn ecc-btn-gold px-4 py-3">
                                TRACK SHIPMENT
                            </button>
                        @endif
                    </div>
                </div>
            </section>

            <!-- Main Layout -->
            <div class="row g-4 g-xl-5">
                <!-- Left Column -->
                <div class="col-12 col-xl-8">
                    <div class="d-flex flex-column gap-4 gap-xl-5">

                        <!-- Curated Items -->
                        <section class="ecc-panel overflow-hidden shadow-lg">
                            <div class="ecc-panel-header d-flex justify-content-between align-items-center gap-3">
                                <h3 class="ecc-panel-title mb-0">Curated Items</h3>
                                <span class="ecc-panel-subtitle">{{ $itemsCount }} {{ Str::plural('Item', $itemsCount) }}</span>
                            </div>

                            <div class="ecc-item-list">
                                @foreach($items as $item)
                                    <div class="ecc-item-row">
                                        <div class="ecc-item-thumb">
                                            <img src="{{ $item->image_url }}"
                                                 alt="{{ $item->title }}"
                                                 class="w-100 h-100 object-fit-cover">
                                        </div>

                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                                <div class="min-w-0">
                                                    @if($item->type_label)
                                                        <div class="ecc-item-label">{{ $item->type_label }}</div>
                                                    @endif

                                                    <h4 class="ecc-item-title mb-1">{{ $item->title }}</h4>

                                                    @if($item->subtitle)
                                                        <p class="ecc-item-subtitle mb-0 small">{{ $item->subtitle }}</p>
                                                    @endif
                                                </div>

                                                <div class="ecc-item-price text-end">
                                                    {{ $item->formatted_total }}
                                                </div>
                                            </div>

                                            <div class="d-flex flex-wrap align-items-center gap-3 ecc-item-meta">
                                                <span>Qty: {{ $item->quantity }}</span>

                                                @if($item->sku)
                                                    <span>SKU: {{ $item->sku }}</span>
                                                @endif

                                                @if($item->variant_summary)
                                                    <span>{{ $item->variant_summary }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <!-- Logistics -->
                        <section>
                            <div class="row g-4">
                                <!-- Shipping -->
                                <div class="col-12 col-md-6">
                                    <div class="ecc-panel h-100 shadow-lg">
                                        <div class="ecc-address-body">
                                            <div class="d-flex align-items-center gap-2 mb-4">
                                                <span class="material-symbols-outlined ecc-section-icon">local_shipping</span>
                                                <h3 class="ecc-section-heading mb-0 text-uppercase">Shipping Address</h3>
                                            </div>

                                            <div class="ecc-address-block">
                                                <div class="fw-bold text-white mb-2 fs-5 text-uppercase-less">{{ $shippingName }}</div>
                                                <div class="ecc-muted lh-lg small">
                                                    {!! nl2br(e($shippingAddressBlock)) !!}
                                                </div>

                                                @if($shippingPhone)
                                                    <div class="mt-3 small text-white-50"><i class="material-symbols-outlined fs-6 align-middle me-1">call</i>{{ $shippingPhone }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment -->
                                <div class="col-12 col-md-6">
                                    <div class="ecc-panel h-100 shadow-lg">
                                        <div class="ecc-payment-body">
                                            <div class="d-flex align-items-center gap-2 mb-4">
                                                <span class="material-symbols-outlined ecc-section-icon">credit_card</span>
                                                <h3 class="ecc-section-heading mb-0 text-uppercase">Payment Method</h3>
                                            </div>

                                            <div class="ecc-payment-card-row mb-3">
                                                <div class="ecc-payment-brand-box">
                                                    {{ $paymentBrandLabel }}
                                                </div>

                                                <div>
                                                    <div class="fw-bold text-white small text-uppercase">
                                                        {{ $paymentMethodLabel }}
                                                    </div>

                                                    @if($paymentExpiryLabel)
                                                        <div class="ecc-muted small">
                                                            Expires {{ $paymentExpiryLabel }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($billingSameAsShipping)
                                                <div class="mt-3 ecc-muted small d-flex align-items-center gap-2">
                                                    <span class="material-symbols-outlined fs-6 text-success">check_circle</span>
                                                    <span>Billing address same as shipping</span>
                                                </div>
                                            @elseif($billingAddressBlock)
                                                <div class="mt-4 ecc-muted small lh-lg">
                                                    {!! nl2br(e($billingAddressBlock)) !!}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-12 col-xl-4">
                    <div class="ecc-order-summary-wrap">
                        <div class="ecc-summary-card shadow-lg">
                            <h3 class="ecc-summary-title text-uppercase">Order Summary</h3>

                            <div class="d-flex flex-column gap-3 mb-4">
                                <div class="d-flex justify-content-between gap-3">
                                    <span class="ecc-muted fw-bold small text-uppercase">Subtotal</span>
                                    <strong class="text-white">{{ $formattedSubtotal }}</strong>
                                </div>

                                <div class="d-flex justify-content-between gap-3">
                                    <span class="ecc-muted fw-bold small text-uppercase">{{ $shippingLabel }}</span>
                                    <strong class="text-white">{{ $formattedShipping }}</strong>
                                </div>

                                <div class="d-flex justify-content-between gap-3">
                                    <span class="ecc-muted fw-bold small text-uppercase">{{ $taxLabel }}</span>
                                    <strong class="text-white">{{ $formattedTax }}</strong>
                                </div>

                                @if($discountAmount > 0)
                                    <div class="d-flex justify-content-between gap-3">
                                        <span class="ecc-muted fw-bold small text-uppercase">Discount Applied</span>
                                        <strong class="ecc-text-gold">-{{ $formattedDiscount }}</strong>
                                    </div>
                                @endif
                            </div>

                            <div class="ecc-summary-total-row">
                                <div>
                                    <div class="ecc-total-label">TOTAL</div>
                                </div>

                                <div class="text-end">
                                    <div class="ecc-total-amount fw-900">{{ $formattedGrandTotal }}</div>

                                    @if($currencyCode)
                                        <div class="ecc-currency-note">
                                            CURRENCY: {{ $currencyCode }}
                                        </div>
                                    @endif
                                </div>
                            </div>



                                <p class="ecc-summary-note mt-4 mb-0">
                                    All qualifying items include digital provenance and authenticity support where applicable.
                                </p>
                            </div>
                        </div>

                        <!-- Support -->
                        <div class="ecc-support-card mt-4 shadow-lg">
                            <div class="ecc-support-icon">
                                <span class="material-symbols-outlined">headset_mic</span>
                            </div>

                            <div>
                                <h4 class="fw-bold text-white small mb-1">Need assistance?</h4>
                                <p class="ecc-muted small mb-2">
                                    Our concierge team is available to assist with delivery, archive care, and premium order support.
                                </p>

                                <a href="{{ $conciergeUrl }}"
                                   class="ecc-support-link fw-bold text-uppercase-less">
                                    Contact Concierge
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
