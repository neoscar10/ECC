<div class="w-100 pt-2 pb-5 ecc-checkout-page">
    @push('styles')
    <style>
        .ecc-checkout-page {
            color: var(--ecc-text-primary);
        }
        .ecc-section-title {
            font-size: clamp(1.75rem, 2vw, 2.75rem);
            font-weight: 900;
            letter-spacing: -.04em;
            color: var(--ecc-text-primary);
            text-transform: uppercase;
        }
        .ecc-section-label {
            font-size: .78rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--ecc-text-muted);
            font-weight: 800;
        }
        .ecc-text-primary {
            color: var(--ecc-primary);
        }
        .ecc-muted {
            color: var(--ecc-text-muted);
        }
        .ecc-address-card,
        .ecc-payment-card,
        .ecc-summary-card,
        .ecc-add-card-panel,
        .ecc-empty-panel,
        .ecc-form-panel {
            background: linear-gradient(180deg, var(--ecc-bg-surface), var(--ecc-bg-surface-2));
            border: 1px solid var(--ecc-primary-border);
            border-radius: 1rem;
            color: var(--ecc-text-primary);
            transition: .25s ease;
        }
        .ecc-address-card,
        .ecc-add-card-panel,
        .ecc-form-panel {
            padding: 1.5rem;
        }
        .ecc-address-card {
            border: 1px solid var(--ecc-primary-border);
            background: transparent;
            text-decoration: none;
            display: block;
            width: 100%;
            cursor: pointer;
        }
        .ecc-payment-card {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            cursor: pointer;
            margin-bottom: 0.75rem;
        }
        .ecc-address-card:hover,
        .ecc-payment-card:hover,
        .ecc-add-card-panel:hover {
            border-color: var(--ecc-primary-border);
            box-shadow: 0 12px 32px rgba(0,0,0,.18);
            transform: translateY(-1px);
        }
        .ecc-address-card.is-selected,
        .ecc-payment-card.is-selected {
            border-color: var(--ecc-primary);
            background: rgba(199, 167, 90, .05);
            box-shadow: 0 0 0 1px rgba(199, 167, 90,.2), 0 16px 40px var(--ecc-shadow-card);
        }
        .ecc-badge-gold {
            display: inline-flex;
            align-items: center;
            padding: .35rem .6rem;
            border-radius: 999px;
            background: var(--ecc-primary-soft);
            color: var(--ecc-primary);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .ecc-badge-gold.subtle {
            font-size: .55rem;
            padding: .2rem .45rem;
        }
        .ecc-selected-icon {
            color: var(--ecc-primary);
            font-size: 1.4rem;
        }
        .ecc-card-brand-box,
        .ecc-wallet-box {
            width: 56px;
            height: 38px;
            border-radius: .55rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--ecc-text-primary);
            color: var(--ecc-text-primary);
            font-size: .85rem;
            font-weight: 800;
        }
        .ecc-add-card-panel {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-style: dashed;
            background: transparent;
            margin-top: 1rem;
        }
        .ecc-add-icon {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ecc-border-soft);
            font-size: 1.15rem;
        }
        .ecc-radio {
            width: 1.15rem;
            height: 1.15rem;
            border: 2px solid var(--ecc-primary-border);
            box-shadow: none !important;
            background-color: transparent;
        }
        .ecc-radio:checked {
            background-color: var(--ecc-primary);
            border-color: var(--ecc-primary);
        }
        .ecc-checkout-summary-wrap {
            position: sticky;
            top: 7rem;
        }
        .ecc-summary-card {
            padding: 1.75rem;
            border-radius: 1.25rem;
        }
        .ecc-summary-title {
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--ecc-border-soft);
            font-size: 1.25rem;
            font-weight: 900;
            letter-spacing: -.03em;
            text-transform: uppercase;
            color: var(--ecc-text-primary);
        }
        .ecc-summary-thumb {
            width: 68px;
            height: 68px;
            border-radius: .75rem;
            overflow: hidden;
            background: var(--ecc-border-soft);
            flex-shrink: 0;
        }
        .ecc-summary-meta {
            font-size: .72rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--ecc-text-muted);
            font-weight: 800;
        }
        .ecc-summary-breakdown {
            border-top: 1px solid var(--ecc-border-soft);
            padding-top: 1rem;
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: .8rem;
        }
        .ecc-summary-total {
            border-top: 2px solid var(--ecc-primary-border);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        .ecc-total-amount {
            color: var(--ecc-primary);
            font-size: clamp(1.75rem, 2vw, 2.5rem);
            font-weight: 900;
            letter-spacing: -.04em;
            line-height: 1;
        }
        .ecc-summary-note {
            font-size: .75rem;
            line-height: 1.7;
            color: var(--ecc-text-muted);
            text-align: center;
        }
        .ecc-summary-note a {
            color: var(--ecc-text-primary);
            text-decoration: underline;
        }
        .ecc-btn-primary {
            background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
            border: 1px solid var(--ecc-primary);
            color: #16110a;
            border-radius: .9rem;
            letter-spacing: .18em;
            text-transform: uppercase;
        }
        .ecc-btn-primary:hover,
        .ecc-btn-primary:focus {
            background: linear-gradient(180deg, #e6c75c, #d6ad35);
            color: #16110a;
        }
        .ecc-btn-outline-gold {
            background: transparent;
            border: 1px solid rgba(199, 167, 90,.35);
            color: var(--ecc-primary);
            letter-spacing: .14em;
            text-transform: uppercase;
            font-weight: 800;
            font-size: .78rem;
        }
        .ecc-btn-outline-gold:hover,
        .ecc-btn-outline-gold:focus {
            background: var(--ecc-primary-soft);
            color: #e7c458;
            border-color: rgba(199, 167, 90,.6);
        }
        .ecc-trust-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            padding: 1rem .25rem 0;
            opacity: .65;
        }
        .ecc-trust-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .35rem;
            flex: 1 1 0;
            text-align: center;
            color: var(--ecc-text-muted);
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .1em;
        }
        .ecc-empty-panel {
            padding: 2.5rem;
            text-align: center;
        }
        .ecc-form-label {
            color: var(--ecc-primary);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: .5rem;
        }
        .ecc-input {
            background: var(--ecc-bg-input);
            border: 1px solid var(--ecc-primary-border);
            border-radius: .75rem;
            color: var(--ecc-text-primary);
            padding: .75rem 1rem;
        }
        .ecc-input:focus {
            background: var(--ecc-bg-input);
            border-color: var(--ecc-primary);
            box-shadow: 0 0 0 3px rgba(199, 167, 90,.1);
            color: var(--ecc-text-primary);
        }
        .ecc-security-note {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            opacity: .6;
        }
        @media (max-width: 1199.98px) {
            .ecc-checkout-summary-wrap {
                position: static;
            }
        }
    </style>
    @endpush

    <div class="row g-4 g-xl-5">
        <!-- Left Column -->
        <div class="col-12 col-xl-8">
            <div class="d-flex flex-column gap-5">

                @if (session()->has('error'))
                    <div class="alert alert-danger border-0 rounded-4" style="background: rgba(220, 53, 69, 0.1); color: #ff8e99;">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session()->has('info'))
                    <div class="alert alert-info border-0 rounded-4" style="background: rgba(13, 110, 253, 0.1); color: #8ec5ff;">
                        {{ session('info') }}
                    </div>
                @endif

                <!-- Shipping Section -->
                <section>
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                        <div>
                            <h1 class="ecc-section-title mb-0">SHIPPING DETAILS</h1>
                        </div>

                        @if(!$showAddressForm && !$isVaultDelivery)
                        <button type="button"
                                class="btn ecc-btn-outline-gold rounded-pill px-4 py-2"
                                wire:click="openAddressForm">
                            ADD NEW ADDRESS
                        </button>
                        @endif
                    </div>

                    @if($isVaultDelivery)
                        <!-- Vault Static Address View -->
                        @php
                            $vaultAddress = $addresses->firstWhere('id', $selectedAddressId);
                        @endphp
                        @if($vaultAddress)
                            <div class="ecc-address-card is-selected" style="cursor: default; pointer-events: none;">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <span class="ecc-badge-gold">VAULT DELIVERY ADDRESS</span>
                                    </div>
                                    <span class="material-symbols-outlined ecc-selected-icon">lock</span>
                                </div>
                                <div class="fw-bold fs-5 text-uppercase-less mb-2">{{ $vaultAddress->full_name }}</div>
                                <div class="ecc-muted small lh-lg">
                                    {{ $vaultAddress->line1 }}<br>
                                    @if($vaultAddress->line2) {{ $vaultAddress->line2 }}<br> @endif
                                    {{ $vaultAddress->city }}, {{ $vaultAddress->state }}<br>
                                    {{ $vaultAddress->country }} - {{ $vaultAddress->postal_code }}
                                </div>
                                <div class="mt-3 fw-semibold">{{ $vaultAddress->phone }}</div>
                            </div>
                        @else
                            <div class="alert alert-danger">Delivery address not found for this request.</div>
                        @endif
                    @else

                    @if($showAddressForm)
                    <div class="ecc-form-panel mb-4 shadow-lg">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0 fs-5 fw-bold text-uppercase-less">{{ $editingAddressId ? 'Edit Shipping Address' : 'Add New Shipping Address' }}</h4>
                            <button type="button" class="btn-close btn-close-white" wire:click="$set('showAddressForm', false)"></button>
                        </div>
                        
                        <form wire:submit.prevent="saveAddress">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="ecc-form-label">Full Name</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.full_name">
                                    @error('addressForm.full_name') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="ecc-form-label">Phone Number</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.phone">
                                    @error('addressForm.phone') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="ecc-form-label">Address Line 1</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.line1">
                                    @error('addressForm.line1') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="ecc-form-label">Address Line 2 (Optional)</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.line2">
                                </div>
                                <div class="col-md-4">
                                    <label class="ecc-form-label">City</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.city">
                                    @error('addressForm.city') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="ecc-form-label">State</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.state">
                                    @error('addressForm.state') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="ecc-form-label">Postal Code</label>
                                    <input type="text" class="form-control ecc-input" wire:model="addressForm.postal_code">
                                    @error('addressForm.postal_code') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input ecc-radio" type="checkbox" wire:model="addressForm.is_default" id="isDefaultCheck">
                                        <label class="form-check-label ms-2 small fw-bold" for="isDefaultCheck">
                                            SET AS DEFAULT ADDRESS
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn ecc-btn-primary px-5 py-2 fw-bold">{{ $editingAddressId ? 'UPDATE ADDRESS' : 'SAVE ADDRESS' }}</button>
                                    <button type="button" class="btn ecc-text-primary px-4 small" wire:click="$set('showAddressForm', false)">CANCEL</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif

                    <div class="row g-3">
                        @forelse($addresses as $address)
                            <div class="col-12 col-md-6">
                                <div role="button"
                                     class="w-100 text-start ecc-address-card {{ (string) $selectedAddressId === (string) $address->id ? 'is-selected' : '' }}"
                                     wire:click="selectAddress('{{ $address->id }}')">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div>
                                            @if($address->is_default)
                                                <span class="ecc-badge-gold">DEFAULT ADDRESS</span>
                                            @endif
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" 
                                                    class="btn btn-link p-0 text-decoration-none d-flex align-items-center justify-content-center" 
                                                    style="color: var(--ecc-primary); width: 28px; height: 28px; border-radius: 50%; background: rgba(199, 167, 90, 0.1);" 
                                                    wire:click.stop="editAddress('{{ $address->id }}')">
                                                <span class="material-symbols-outlined" style="font-size: 1.1rem;">edit</span>
                                            </button>
                                            @if((string) $selectedAddressId === (string) $address->id)
                                                <span class="material-symbols-outlined ecc-selected-icon">check_circle</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="fw-bold fs-5 text-uppercase-less mb-2">
                                        {{ $address->full_name }}
                                    </div>

                                    <div class="ecc-muted small lh-lg">
                                        {{ $address->line1 }}<br>
                                        @if($address->line2)
                                            {{ $address->line2 }}<br>
                                        @endif
                                        {{ $address->city }}, {{ $address->state }}<br>
                                        {{ $address->country }} - {{ $address->postal_code }}
                                    </div>

                                    <div class="mt-3 fw-semibold">
                                        {{ $address->phone }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            @if(!$showAddressForm)
                            <div class="col-12">
                                <div class="ecc-empty-panel">
                                    <div class="fw-bold fs-4 mb-2">No saved address found</div>
                                    <div class="ecc-muted small mb-4">Please add a shipping address to continue checkout.</div>
                                    <button type="button"
                                            class="btn ecc-btn-primary rounded-pill px-5 py-3 fw-bold"
                                            wire:click="openAddressForm">
                                        ADD NEW ADDRESS
                                    </button>
                                </div>
                            </div>
                            @endif
                        @endforelse
                    </div>
                    @endif
                </section>

                <!-- Payment Section -->
                <section>
                    <div class="mb-4">
                        <h2 class="ecc-section-title mb-0">PAYMENT METHOD</h2>
                    </div>

                    @php
                        $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
                        $gatewayOptions = array_filter($availabilityService->publicOptions(), function($opt) {
                            return $opt['enabled'];
                        });
                    @endphp

                    @if(count($gatewayOptions) > 1)
                        <div class="d-flex flex-column gap-3">
                            @foreach($gatewayOptions as $opt)
                                <div class="ecc-payment-card {{ $paymentGateway === $opt['key'] ? 'is-selected' : '' }}" 
                                     wire:click="$set('paymentGateway', '{{ $opt['key'] }}')"
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                                        @if($opt['key'] === 'razorpay')
                                            <!-- Razorpay Icon area -->
                                            <div style="width:56px; height:38px; border-radius:.55rem; background:rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                                <svg width="28" height="20" viewBox="0 0 38 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M18.8 0L0 14.4H8.4L12 6.4L10.8 14.4H19.6L22.4 0H18.8Z" fill="#2D81EF"/>
                                                    <path d="M20.4 9.6L17.6 24H21.2L24.4 9.6H20.4Z" fill="#A9CAFF"/>
                                                    <path d="M22 9.6L25.6 0H22L20.4 9.6H22Z" fill="#2D81EF"/>
                                                </svg>
                                            </div>
                                        @elseif($opt['key'] === 'cashfree')
                                            <!-- Cashfree Icon area -->
                                            <div style="width:56px; height:38px; border-radius:.55rem; background:rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight: bold; color: #fff; font-size: 0.8rem;">
                                                CF
                                            </div>
                                        @else
                                            <div style="width:56px; height:38px; border-radius:.55rem; background:rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight: bold; color: #fff; font-size: 0.8rem;">
                                                {{ strtoupper(substr($opt['key'], 0, 2)) }}
                                            </div>
                                        @endif

                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <div class="fw-bold">{{ $opt['label'] }}</div>
                                                @if($opt['key'] === 'razorpay')
                                                    <span class="ecc-badge-gold subtle" style="background: rgba(45,129,239,.15); color:#7db9ff; letter-spacing:.08em;">
                                                        TEST MODE
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ecc-muted small">
                                                {{ $opt['description'] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center" style="flex-shrink: 0;">
                                        <input class="form-check-input ecc-radio m-0" 
                                               type="radio" 
                                               name="paymentGatewaySelector" 
                                               value="{{ $opt['key'] }}"
                                               wire:model.live="paymentGateway"
                                               {{ $paymentGateway === $opt['key'] ? 'checked' : '' }}>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Only one option enabled (usually Razorpay) -->
                        @php
                            $opt = reset($gatewayOptions) ?: ['key' => 'razorpay', 'label' => 'Razorpay Secure Checkout', 'description' => 'UPI · Cards · Netbanking · Wallets & more'];
                        @endphp
                        <div class="ecc-payment-card is-selected" style="cursor: default; border-color: var(--ecc-primary); background: rgba(199, 167, 90, .05);">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                @if($opt['key'] === 'razorpay')
                                    <div style="width:56px; height:38px; border-radius:.55rem; background:rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <svg width="28" height="20" viewBox="0 0 38 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M18.8 0L0 14.4H8.4L12 6.4L10.8 14.4H19.6L22.4 0H18.8Z" fill="#2D81EF"/>
                                            <path d="M20.4 9.6L17.6 24H21.2L24.4 9.6H20.4Z" fill="#A9CAFF"/>
                                            <path d="M22 9.6L25.6 0H22L20.4 9.6H22Z" fill="#2D81EF"/>
                                        </svg>
                                    </div>
                                @else
                                    <div style="width:56px; height:38px; border-radius:.55rem; background:rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-weight: bold; color: #fff; font-size: 0.8rem;">
                                        {{ strtoupper(substr($opt['key'], 0, 2)) }}
                                    </div>
                                @endif

                                <div class="flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <div class="fw-bold">{{ $opt['key'] === 'razorpay' ? 'Razorpay Secure Checkout' : $opt['label'] }}</div>
                                        @if($opt['key'] === 'razorpay')
                                            <span class="ecc-badge-gold subtle" style="background: rgba(45,129,239,.15); color:#7db9ff; letter-spacing:.08em;">
                                                TEST MODE
                                            </span>
                                        @endif
                                    </div>
                                    <div class="ecc-muted small">
                                        {{ $opt['description'] }}
                                    </div>
                                </div>
                            </div>

                            <span class="material-symbols-outlined ecc-selected-icon" style="flex-shrink:0;">check_circle</span>
                        </div>
                    @endif

                    <div class="text-center pt-3">
                        <div class="d-inline-flex align-items-center gap-2 ecc-security-note">
                            <i class="mdi mdi-lock-outline"></i>
                            <span>PAYMENTS ARE SSL ENCRYPTED AND PROCESSED SECURELY</span>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        <!-- Right Column -->
        <div class="col-12 col-xl-4">
            <div class="ecc-checkout-summary-wrap">
                <div class="ecc-summary-card">
                    <div class="ecc-summary-title">
                        {{ $isVaultDelivery ? 'DELIVERY SUMMARY' : 'ORDER SUMMARY' }}
                    </div>

                    <div class="d-flex flex-column gap-3 mb-4">
                        @foreach($summaryItems as $item)
                            <div class="d-flex gap-3">
                                <div class="ecc-summary-thumb">
                                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="w-100 h-100 object-fit-cover">
                                </div>

                                <div class="flex-grow-1">
                                    <div class="fw-bold small lh-sm mb-1 text-uppercase-less">{{ $item->title }} ({{ $item->quantity }})</div>
                                    @if($item->meta)
                                        <div class="ecc-summary-meta mb-1">{{ $item->meta }}</div>
                                    @endif
                                    <div class="fw-bold ecc-text-primary">{{ $item->formatted_total }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="ecc-summary-breakdown">
                        @if(!$isVaultDelivery)
                        <div class="d-flex justify-content-between gap-3">
                            <span class="ecc-muted">Subtotal</span>
                            <strong>{{ $summary['formatted_subtotal'] }}</strong>
                        </div>
                        @endif

                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex justify-content-between gap-3">
                                <span class="ecc-muted">Shipping</span>
                                <strong class="{{ $summary['formatted_shipping_class'] ?? '' }}">{{ $summary['formatted_shipping'] }}</strong>
                            </div>
                            @if($shippingCourierName)
                                <div class="d-flex justify-content-between gap-3" style="font-size: 0.75rem;">
                                    <span class="ecc-muted">via {{ $shippingCourierName }}</span>
                                    @if($shippingEtd)
                                        <span class="ecc-muted">Est. {{ \Carbon\Carbon::parse($shippingEtd)->format('M d') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if(!$isVaultDelivery)
                        <div class="d-flex justify-content-between gap-3">
                            <span class="ecc-muted">Estimated Tax</span>
                            <strong>{{ $summary['formatted_tax'] }}</strong>
                        </div>
                        @endif

                        @if(($summary['discount_amount'] ?? 0) > 0)
                            <div class="d-flex justify-content-between gap-3">
                                <span class="ecc-muted">Discount</span>
                                <strong class="text-success">-{{ $summary['formatted_discount'] }}</strong>
                            </div>
                        @endif
                    </div>

                    <div class="ecc-summary-total">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                            <span class="ecc-section-label mb-0">TOTAL PAYABLE</span>
                            <span class="ecc-total-amount">{{ $summary['formatted_total'] }}</span>
                        </div>

                        @if($shippingError)
                            <div class="alert alert-danger border-0 rounded-3 py-2 px-3 mb-3 d-flex align-items-center" style="background: rgba(220, 53, 69, 0.1); color: #ff8e99; font-size: 0.8rem;">
                                <i class="mdi mdi-alert-circle-outline me-2 fs-5"></i> 
                                <div>{{ $shippingError }}</div>
                            </div>
                        @endif

                        <button type="button"
                                class="btn ecc-btn-primary w-100 py-3 fw-bold"
                                wire:click="placeOrder"
                                wire:loading.attr="disabled"
                                @if(!$canPlaceOrder) disabled @endif>
                            <span wire:loading.remove wire:target="placeOrder">{{ $isVaultDelivery ? 'PAY DELIVERY FEE' : 'PLACE ORDER' }}</span>
                            <span wire:loading wire:target="placeOrder">
                                <span class="spinner-border spinner-border-sm me-2"></span> PROCESSING...
                            </span>
                        </button>

                        <p class="ecc-summary-note mt-4 mb-0">
                            By placing your order, you agree to Cricket Luxe
                            <a href="#">Terms of Service</a>
                            and
                            <a href="#">Bidding Policy</a>.
                        </p>
                    </div>
                </div>

                <div class="ecc-trust-row">
                    <div class="ecc-trust-item">
                        <i class="mdi mdi-shield-lock-outline fs-4"></i>
                        <span>SECURE SSL</span>
                    </div>
                    <div class="ecc-trust-item">
                        <i class="mdi mdi-check-decagram-outline fs-4"></i>
                        <span>AUTHENTICATED</span>
                    </div>
                    <div class="ecc-trust-item">
                        <i class="mdi mdi-truck-fast-outline fs-4"></i>
                        <span>INSURED</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
