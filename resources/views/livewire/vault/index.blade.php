<div class="container-xxl py-4 py-lg-5 ecc-vault-page">
    <div class="d-flex flex-column gap-4 gap-xl-5">

        <!-- Header Section -->
        <section class="ecc-vault-header position-relative overflow-hidden">
            <div class="row g-4 align-items-end">
                <div class="col-12 col-lg-7">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="ecc-vault-header-line"></span>
                        <span class="ecc-vault-kicker">SECURITY PROTOCOL {{ $vaultProtocolVersion ?? 'V4.2' }}</span>
                    </div>

                    <h1 class="ecc-vault-title mb-3">THE VAULT</h1>

                    <p class="ecc-vault-subtitle mb-0">
                        {{ $vaultIntroText ?? 'Your digital stronghold for authenticated assets and secured certificates of provenance.' }}
                    </p>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="ecc-vault-standing-card">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="ecc-vault-standing-label">ACCOUNT STANDING</div>
                                    <div class="ecc-vault-standing-tier">{{ $vaultTierLabel }}</div>
                                </div>
                                <span class="ecc-vault-access-pill">
                                    <i class="mdi mdi-shield-check-outline me-2"></i>
                                    {{ $vaultAccessLabel ?? 'VAULT ACCESS: GRANTED' }}
                                </span>
                            </div>

                            <div class="row g-3 mt-1 pt-3 border-top border-white-5 border-opacity-10">
                                <div class="col-6">
                                    <div class="ecc-vault-mini-stat-label">TOTAL ASSETS</div>
                                    <div class="ecc-vault-mini-stat-value">{{ number_format($vaultSummary['total_items_count']) }}</div>
                                </div>
                                <div class="col-6 text-end">
                                    <div class="ecc-vault-mini-stat-label text-end">VALUATION (EST)</div>
                                    <div class="ecc-vault-mini-stat-value text-end">{{ number_format($vaultSummary['total_value']) }} <small class="fs-10 opacity-50">INR</small></div>
                                </div>
                            </div>
                            
                            @if($vaultSummary['pending_requests_count'] > 0)
                                <div class="ecc-vault-alert-pill mt-1">
                                    <i class="ri-history-line me-2"></i>
                                    {{ $vaultSummary['pending_requests_count'] }} PENDING REMOVAL REQUESTS
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section>
            <div class="row g-4 g-xl-5">
                <!-- Left Sidebar -->
                <div class="col-12 col-xl-3">
                    <div class="d-flex flex-column gap-4">

                        @if(!empty($vaultSecurityItems))
                            <div class="ecc-vault-sidebar-card">
                                <h2 class="ecc-vault-sidebar-title">Vault Security Protocols</h2>

                                <div class="d-flex flex-column gap-4">
                                    @foreach($vaultSecurityItems as $securityItem)
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="ecc-vault-sidebar-icon">
                                                <i class="{{ $securityItem['icon'] ?? 'mdi mdi-shield-lock-outline' }}"></i>
                                            </div>

                                            <div>
                                                <div class="ecc-vault-sidebar-item-title">{{ $securityItem['title'] }}</div>
                                                @if(!empty($securityItem['description']))
                                                    <div class="ecc-vault-sidebar-item-text">{{ $securityItem['description'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($insuredValueLabel) || !empty($policyStatusLabel))
                            <div class="ecc-vault-value-card">
                                <div class="ecc-vault-value-label">TOTAL INSURED VALUE</div>
                                <div class="ecc-vault-value-amount">{{ $insuredValueLabel }}</div>

                                <div class="ecc-vault-value-footer">
                                    <span>{{ $policyStatusLabel ?? 'POLICY ACTIVE' }}</span>
                                    <span class="ecc-vault-value-status-dot"></span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Artifact Grid -->
                <div class="col-12 col-xl-9">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                        <h2 class="ecc-vault-grid-title">
                            SECURED ARTIFACTS
                            @if(isset($vaultArtifactCount) && $vaultArtifactCount !== null)
                                ({{ str_pad((string) $vaultArtifactCount, 2, '0', STR_PAD_LEFT) }})
                            @endif
                        </h2>

                        @if($supportsVaultViewToggle)
                            <div class="d-flex align-items-center gap-2">
                                <button type="button"
                                        class="btn ecc-vault-toggle-btn {{ $vaultViewMode === 'grid' ? 'is-active' : '' }}"
                                        wire:click="setVaultView('grid')">
                                    <i class="mdi mdi-view-grid-outline"></i>
                                </button>

                                <button type="button"
                                        class="btn ecc-vault-toggle-btn {{ $vaultViewMode === 'list' ? 'is-active' : '' }}"
                                        wire:click="setVaultView('list')">
                                    <i class="mdi mdi-format-list-bulleted"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="row g-4">
                        @foreach($mappedArtifacts as $artifact)
                            <div class="{{ $vaultViewMode === 'list' ? 'col-12' : 'col-12 col-md-6' }}">
                                <article class="ecc-vault-artifact-card h-100 {{ $vaultViewMode === 'list' ? 'd-flex align-items-center' : '' }}" 
                                         wire:click="selectArtifact({{ $artifact->id }})" 
                                         style="cursor: pointer;">
                                    <div class="ecc-vault-artifact-media position-relative {{ $vaultViewMode === 'list' ? 'w-25 h-100 min-vh-25' : '' }}" @if($vaultViewMode === 'list') style="min-width: 250px" @endif>
                                        <img src="{{ $artifact->image_url }}"
                                             alt="{{ $artifact->title }}"
                                             class="w-100 h-100 object-fit-cover">

                                        @if($artifact->delivery_badge_label)
                                            <span class="position-absolute top-0 end-0 m-2 badge border {{ $artifact->delivery_badge_class }}" style="background: var(--ecc-surface); backdrop-filter: blur(4px); font-size: 0.65rem; padding: 0.4rem 0.6rem; letter-spacing: 0.5px;">
                                                {{ $artifact->delivery_badge_label }}
                                            </span>
                                        @elseif($artifact->status_badge_label)
                                            <span class="ecc-vault-artifact-badge">
                                                {{ $artifact->status_badge_label }}
                                            </span>
                                        @endif
                                        
                                        @if($artifact->has_pending_request)
                                            <div class="ecc-vault-pending-overlay">
                                                <div class="ecc-vault-pending-badge">REMOVAL PENDING</div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="ecc-vault-artifact-body {{ $vaultViewMode === 'list' ? 'flex-grow-1 p-4' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <h3 class="ecc-vault-artifact-title mb-0">{{ $artifact->title }}</h3>
                                            <span class="ecc-vault-artifact-price-mini">
                                                {{ number_format($artifact->total_value) }} {{ $artifact->currency }}
                                            </span>
                                        </div>

                                        @if($artifact->description)
                                            <p class="ecc-vault-artifact-text {{ $vaultViewMode === 'list' ? 'pe-lg-5' : '' }}">{{ \Illuminate\Support\Str::limit($artifact->description, 100) }}</p>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-auto pt-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <button type="button" class="ecc-vault-inline-link border-0 bg-transparent p-0">
                                                    <i class="mdi mdi-eye-outline"></i>
                                                    <span>VIEW DETAILS</span>
                                                </button>
                                            </div>

                                            @if($artifact->reference_label)
                                                <span class="ecc-vault-ref">{{ $artifact->reference_label }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach

                        @if(empty($mappedArtifacts) || count($mappedArtifacts) === 0)
                            <div class="col-12">
                                <div class="ecc-empty-state py-5 text-center">
                                    <div class="mb-3">
                                        <i class="mdi mdi-shield-lock-outline fs-1" style="color: var(--ecc-primary);"></i>
                                    </div>
                                    <h4 class="text-white fw-bold">
                                        {{ auth('web')->user()?->has_vault_access ? 'NO SECURED ARTIFACTS' : 'VAULT ACCESS RESTRICTED' }}
                                    </h4>
                                    <p class="text-white-50 mx-auto" style="max-width: 400px;">
                                        {{ auth('web')->user()?->has_vault_access 
                                            ? 'Your vault is currently empty. Acquire and secure premium assets to view them here.' 
                                            : 'Upgrade your membership to unlock full access to the Executive Vault.' }}
                                    </p>
                                    
                                    @if(!auth('web')->user()?->has_vault_access)
                                        <button class="btn ecc-btn-primary mt-3 px-4 py-2" wire:click="$set('showAccessModal', true)">
                                            UNLOCK ACCESS
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif


                    </div>
                </div>
            </div>
        </section>

    </div>

    {{-- Artifact Details Modal --}}
    @if($selectedArtifact)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(10px);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content ecc-vault-modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close btn-close-white ms-auto" wire:click="closeArtifactModal"></button>
                    </div>
                    <div class="modal-body p-4 p-lg-5 pt-0">
                        <div class="row g-4 g-lg-5">
                            <div class="col-12 col-lg-5">
                                <div class="ecc-vault-modal-media rounded-4 overflow-hidden border border-white-5">
                                    <img src="{{ $selectedArtifact['image_url'] }}" class="w-100 h-100 object-fit-cover shadow-lg" alt="">
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="ecc-vault-modal-header mb-4">
                                    <div class="ecc-vault-kicker mb-2">SECURED ASSET DEFINITION</div>
                                    <h2 class="ecc-vault-modal-title mb-1">{{ $selectedArtifact['title'] }}</h2>
                                    <div class="ecc-vault-ref fs-12">{{ $selectedArtifact['reference_label'] }}</div>
                                </div>

                                <div class="ecc-vault-modal-stats row g-3 mb-4">
                                    <div class="col-4">
                                        <div class="ecc-vault-mini-stat-label">QUANTITY</div>
                                        <div class="ecc-vault-mini-stat-value fs-18">{{ $selectedArtifact['quantity'] }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="ecc-vault-mini-stat-label">UNIT PRICE</div>
                                        <div class="ecc-vault-mini-stat-value fs-18">{{ number_format($selectedArtifact['unit_price']) }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="ecc-vault-mini-stat-label">LOCK DATE</div>
                                        <div class="ecc-vault-mini-stat-value fs-18" style="font-size: 0.9rem !important;">{{ $selectedArtifact['locked_at_human'] }}</div>
                                    </div>
                                </div>

                                <div class="ecc-vault-modal-desc mb-4">
                                    <div class="ecc-vault-standing-label mb-2" style="font-size: 0.6rem;">MANIFEST NOTES</div>
                                    <p class="text-white-50 fs-14 lh-lg">{{ $selectedArtifact['description'] ?: 'No additional manifest data available for this artifact.' }}</p>
                                </div>

                                <div class="ecc-vault-valuation-line mb-4 p-3 rounded-3 bg-white-5 border border-white-5">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="ecc-vault-standing-label">TOTAL VALUATION</span>
                                        <span class="ecc-vault-modal-total">
                                            {{ number_format($selectedArtifact['total_value']) }} <small class="fs-12 opacity-50">{{ $selectedArtifact['currency'] }}</small>
                                        </span>
                                    </div>
                                </div>

                                @if(isset($selectedArtifact['tracking']) && $selectedArtifact['tracking'])
                                    @php $track = $selectedArtifact['tracking']; @endphp
                                    <div class="ecc-vault-tracking-drawer mt-4 p-4 rounded-4 bg-white-5 border border-white-5">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <div class="ecc-vault-standing-label mb-1">PHYSICAL DELIVERY STATUS</div>
                                                <div class="fs-16 fw-bold ecc-text-primary">{{ $track['status_label'] }}</div>
                                            </div>
                                            @if($track['is_test_mode'])
                                                <span class="badge bg-warning text-dark opacity-75">Simulated</span>
                                            @endif
                                        </div>

                                        <div class="row g-3 mb-4 fs-13">
                                            @if($track['awb_code'])
                                                <div class="col-6">
                                                    <div class="text-white-50 mb-1" style="font-size: 10px;">AWB CODE</div>
                                                    <div class="fw-semibold text-white">{{ $track['awb_code'] }}</div>
                                                </div>
                                            @endif
                                            @if($track['courier_name'])
                                                <div class="col-6">
                                                    <div class="text-white-50 mb-1" style="font-size: 10px;">COURIER</div>
                                                    <div class="fw-semibold text-white">{{ $track['courier_name'] }}</div>
                                                </div>
                                            @endif
                                            <div class="col-12">
                                                <div class="text-white-50 mb-1" style="font-size: 10px;">DELIVERY FEE PAID</div>
                                                <div class="fw-semibold text-white">{{ number_format($track['delivery_fee'], 2) }} {{ $track['delivery_currency'] }}</div>
                                            </div>
                                        </div>

                                        @if(!empty($track['events']))
                                            <div class="ecc-vault-timeline mt-4 border-start border-white-10 ms-2 ps-3 position-relative">
                                                @foreach($track['events'] as $event)
                                                    <div class="mb-4 position-relative">
                                                        <span class="position-absolute translate-middle p-1 rounded-circle bg-primary" style="top: 10px; left: -18px; border: 2px solid var(--ecc-surface);"></span>
                                                        <div class="fs-14 fw-bold text-white mb-1">{{ $event['status_label'] }}</div>
                                                        <div class="fs-12 text-white-50 mb-1">{{ $event['description'] }}</div>
                                                        <div class="d-flex align-items-center gap-2 fs-10 opacity-50">
                                                            <span><i class="mdi mdi-clock-outline me-1"></i>{{ \Carbon\Carbon::parse($event['event_time'])->format('M d, Y h:i A') }}</span>
                                                            @if(!empty($event['location']))
                                                                <span><i class="mdi mdi-map-marker-outline mx-1"></i>{{ $event['location'] }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if($track['payment_status'] === 'pending_payment' || $track['payment_status'] === 'payment_failed')
                                            <div class="mt-4 text-center">
                                                <a href="{{ route('shop.checkout', ['vault_request_id' => $track['id']]) }}" class="btn btn-warning w-100 fw-bold">
                                                    <i class="mdi mdi-credit-card-outline me-2"></i> PAY DELIVERY FEE
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="d-flex gap-3">
                                        @if($selectedArtifact['has_pending_request'])
                                            <div class="flex-grow-1 p-3 rounded-3 bg-warning-subtle border border-warning-subtle text-warning text-center fw-bold fs-12">
                                               <i class="ri-time-line me-2"></i> REMOVAL REQUEST PENDING REVIEW
                                            </div>
                                        @else
                                            <button class="btn ecc-vault-btn-outline w-100" wire:click="openRemovalModal">
                                                REQUEST PHYSICAL DELIVERY
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Request Removal Modal --}}
    @if($showRemovalModal && $selectedArtifact)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); z-index: 1060;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content ecc-vault-modal-content border-warning-subtle">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-white fw-bold">REQUEST PHYSICAL DELIVERY</h5>
                        <button type="button" class="btn-close btn-close-white ms-auto" wire:click="$set('showRemovalModal', false)"></button>
                    </div>
                    <div class="modal-body p-4 pt-1">
                        <div class="alert alert-warning-subtle border-0 rounded-3 mb-4 fs-13 lh-base">
                            <i class="ri-error-warning-line me-2 fs-16 align-middle"></i>
                            Delivery requests are subject to review by the ECC administration. Once approved, the item will be prepared for physical dispatch to your designated address.
                        </div>

                        <!-- Address Section -->
                        @if (session()->has('error'))
                            <div class="alert alert-danger fs-13 lh-base border-0 rounded-3 mb-4">
                                <i class="ri-error-warning-fill me-2 fs-16 align-middle"></i>
                                {{ session('error') }}
                            </div>
                        @endif
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-end mb-3">
                                <label class="ecc-vault-standing-label mb-0">DELIVERY ADDRESS</label>
                                @if(count($addresses) > 0)
                                    <button type="button" class="btn btn-link p-0 text-white-50 fs-12 text-decoration-none fw-semibold" wire:click="toggleAddressForm">
                                        {{ $showAddressForm ? 'Choose Saved Address' : '+ Add New Address' }}
                                    </button>
                                @endif
                            </div>

                            @if(!$showAddressForm && count($addresses) > 0)
                                <div class="ecc-address-selector">
                                    <div class="row g-2">
                                        @foreach($addresses as $addr)
                                            <div class="col-12">
                                                <label class="ecc-address-card w-100 m-0">
                                                    <input type="radio" wire:model.live="selectedAddressId" value="{{ $addr->id }}" class="btn-check">
                                                    <div class="ecc-address-card-inner p-3 rounded-3 border border-white-5 bg-white-5 position-relative c-pointer transition-all">
                                                        <div class="d-flex align-items-start gap-3">
                                                            <div class="pt-1">
                                                                <div class="custom-radio-dot {{ $selectedAddressId == $addr->id ? 'is-checked' : '' }}"></div>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                                    <h6 class="text-white mb-0 fs-14 fw-bold">{{ $addr->full_name }}</h6>
                                                                    <span class="badge bg-white-5 text-white-50 fw-semibold px-2 py-0 fs-10 border border-white-5">{{ $addr->label }}</span>
                                                                    @if($addr->is_default)
                                                                        <span class="badge ecc-bg-primary-subtle text-white fw-semibold px-2 py-0 fs-10">Default</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-white-50 fs-13 lh-sm">
                                                                    {{ $addr->line1 }}{{ $addr->line2 ? ', '.$addr->line2 : '' }}<br>
                                                                    {{ $addr->city }}, {{ $addr->state }} {{ $addr->postal_code }}<br>
                                                                    Ph: {{ $addr->phone }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="ecc-address-form row g-3 bg-white-5 border border-white-5 rounded-3 p-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-white-50 mb-1">Full Name</label>
                                        <input type="text" class="form-control ecc-vault-input form-control-sm" wire:model.blur="addressForm.full_name">
                                        @error('addressForm.full_name') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-white-50 mb-1">Phone</label>
                                        <input type="text" class="form-control ecc-vault-input form-control-sm" wire:model.blur="addressForm.phone">
                                        @error('addressForm.phone') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fs-12 text-white-50 mb-1">Address Line 1</label>
                                        <input type="text" class="form-control ecc-vault-input form-control-sm" wire:model.blur="addressForm.line1">
                                        @error('addressForm.line1') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-white-50 mb-1">City</label>
                                        <input type="text" class="form-control ecc-vault-input form-control-sm" wire:model.blur="addressForm.city">
                                        @error('addressForm.city') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-white-50 mb-1">State</label>
                                        <input type="text" class="form-control ecc-vault-input form-control-sm" wire:model.blur="addressForm.state">
                                        @error('addressForm.state') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-white-50 mb-1">Postal Code</label>
                                        <input type="text" class="form-control ecc-vault-input form-control-sm" wire:model.live.debounce.500ms="addressForm.postal_code">
                                        @error('addressForm.postal_code') <span class="text-danger fs-11">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-12 text-white-50 mb-1">Label</label>
                                        <select class="form-control ecc-vault-input form-control-sm" wire:model="addressForm.label">
                                            <option value="Home">Home</option>
                                            <option value="Office">Office</option>
                                            <option value="Courier">Delivery Center</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check custom-checkbox-white mt-1">
                                            <input class="form-check-input" type="checkbox" id="saveDef" wire:model="addressForm.is_default">
                                            <label class="form-check-label fs-13 text-white-50" for="saveDef">
                                                Save as default address
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Shipping Quote Section -->
                        <div class="mb-4">
                            <label class="ecc-vault-standing-label mb-2">SHIPPING & SERVICEABILITY</label>
                            
                            <div class="p-3 rounded-3 border border-white-5 bg-white-5 position-relative">
                                <!-- Loading State -->
                                <div wire:loading wire:target="calculateDeliveryQuote, selectedAddressId, addressForm.postal_code, toggleAddressForm" class="w-100 text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-white mb-2" role="status"></div>
                                    <div class="text-white-50 fs-12 fw-semibold">Calculating Shiprocket shipping rates...</div>
                                </div>

                                <!-- Actual Content (hidden when loading) -->
                                <div wire:loading.remove wire:target="calculateDeliveryQuote, selectedAddressId, addressForm.postal_code, toggleAddressForm">
                                    @if($deliveryQuoteLoading)
                                        <div class="w-100 text-center py-3">
                                            <div class="spinner-border spinner-border-sm text-white mb-2" role="status"></div>
                                            <div class="text-white-50 fs-12 fw-semibold">Calculating Shiprocket shipping rates...</div>
                                        </div>
                                    @elseif($deliveryQuoteError)
                                        <div class="d-flex align-items-center gap-2 text-danger">
                                            <i class="ri-error-warning-fill fs-16"></i>
                                            <span class="fs-13 fw-semibold">{{ $deliveryQuoteError }}</span>
                                        </div>
                                    @elseif($deliveryQuote && ($deliveryQuote['success'] ?? false))
                                        <div class="ecc-quote-details">
                                            <!-- Courier and Price -->
                                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-white-5">
                                                <div>
                                                    <div class="fs-11 text-white-50 fw-semibold text-uppercase tracking-wider">Courier Partner</div>
                                                    <div class="text-white fw-bold fs-15">{{ $selectedDeliveryCourier['courier_name'] }}</div>
                                                    <div class="fs-10 text-white opacity-50">
                                                        Rating: {{ number_format($selectedDeliveryCourier['rating'], 1) }} ★
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="fs-11 text-white-50 fw-semibold text-uppercase tracking-wider">Estimated Fee</div>
                                                    <div class="text-white fw-extrabold fs-18">INR {{ number_format($deliveryFee, 2) }}</div>
                                                </div>
                                            </div>

                                            <!-- Package Metrics and ETA -->
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="fs-10 text-white-50 text-uppercase">Chargeable Weight</div>
                                                    <div class="fs-12 fw-semibold text-white">
                                                        {{ number_format($deliveryMeasurement['chargeable_weight_kg'], 3) }} kg
                                                        <span class="fs-9 text-white opacity-50 d-block">(Volumetric: {{ number_format($deliveryMeasurement['volumetric_weight_kg'], 3) }} kg)</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="fs-10 text-white-50 text-uppercase">Estimated Delivery</div>
                                                    <div class="fs-12 fw-semibold text-white">
                                                        {{ $selectedDeliveryCourier['etd'] ? \Carbon\Carbon::parse($selectedDeliveryCourier['etd'])->format('M d, Y') : $selectedDeliveryCourier['estimated_delivery_days'] . ' Days' }}
                                                        <span class="fs-9 text-white opacity-50 d-block">(Transit duration)</span>
                                                    </div>
                                                </div>
                                                <div class="col-12 mt-2 pt-2 border-top border-white-5 text-end fs-11 text-white opacity-75">
                                                    Delivery Pincode: <strong>{{ $deliveryQuote['delivery_pincode'] }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center gap-2 text-warning">
                                            <i class="ri-information-line fs-16"></i>
                                            <span class="fs-13 fw-semibold">Please select/provide an address to calculate delivery.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @error('deliveryQuote')
                                <div class="text-danger fs-11 mt-1 fw-semibold">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="ecc-vault-standing-label mb-2">ADDITIONAL INSTRUCTIONS (OPTIONAL)</label>
                            <textarea wire:model="removalMessage" class="form-control ecc-vault-input" rows="2" placeholder="Delivery notes or special instructions..."></textarea>
                        </div>

                        <div class="d-flex gap-3 mt-4">
                            <button class="btn btn-link text-white-50 text-decoration-none fw-bold fs-13" wire:click="closeArtifactModal">CANCEL</button>
                            <button class="btn ecc-btn-primary px-4 ms-auto" 
                                    wire:click="submitRemovalRequest"
                                    wire:loading.attr="disabled"
                                    wire:target="submitRemovalRequest, calculateDeliveryQuote"
                                    @if(!$this->canSubmitDeliveryRequest()) disabled @endif>
                                <span wire:loading wire:target="submitRemovalRequest" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                SUBMIT REQUEST
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Premium Access Upgrade Modal --}}
    @include('components.shared.premium-access-modal')
</div>

@push('styles')
<style>
    .ecc-vault-page {
        color: #fff;
    }

    .ecc-vault-header {
        padding-top: 1rem;
        padding-bottom: 1rem;
        position: relative;
    }

    .ecc-vault-header::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(199, 167, 90,.08) 0%, transparent 70%);
        pointer-events: none;
    }

    .ecc-vault-header-line {
        width: 48px;
        height: 1px;
        background: var(--ecc-primary);
        display: inline-block;
    }

    .ecc-vault-kicker {
        color: var(--ecc-primary);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .24em;
        text-transform: uppercase;
    }

    .ecc-vault-title {
        font-size: clamp(2.8rem, 7vw, 6rem);
        line-height: .92;
        font-weight: 900;
        letter-spacing: -.06em;
        color: #fff;
        text-transform: uppercase;
        margin: 0;
    }

    .ecc-vault-subtitle {
        color: rgba(245,239,225,.70);
        max-width: 620px;
        font-size: 1.05rem;
        line-height: 1.8;
    }

    .ecc-vault-standing-card,
    .ecc-vault-sidebar-card,
    .ecc-vault-artifact-card,
    .ecc-vault-appraisal-card {
        background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
        border: 1px solid rgba(199, 167, 90,.14);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
        color: #fff;
    }

    .ecc-vault-standing-card {
        padding: 1.5rem;
    }

    .ecc-vault-standing-label,
    .ecc-vault-grid-title {
        color: rgba(245,239,225,.56);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .20em;
        text-transform: uppercase;
    }

    .ecc-vault-standing-tier {
        color: var(--ecc-primary);
        font-size: 2rem;
        font-weight: 900;
        letter-spacing: -.03em;
        text-transform: uppercase;
    }

    .ecc-vault-access-pill {
        display: inline-flex;
        align-items: center;
        padding: .55rem 1rem;
        border-radius: 999px;
        background: rgba(199, 167, 90,.10);
        border: 1px solid rgba(199, 167, 90,.24);
        color: var(--ecc-primary);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .ecc-vault-standing-note {
        color: rgba(245,239,225,.42);
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .ecc-vault-sidebar-card {
        padding: 1.5rem;
    }

    .ecc-vault-sidebar-title {
        color: var(--ecc-primary);
        font-size: .85rem;
        font-weight: 900;
        letter-spacing: .18em;
        text-transform: uppercase;
        margin-bottom: 1.25rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid rgba(199, 167, 90,.08);
    }

    .ecc-vault-sidebar-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: .85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(199, 167, 90,.08);
        color: var(--ecc-primary);
    }

    .ecc-vault-sidebar-item-title {
        color: #fff;
        font-size: .98rem;
        font-weight: 800;
        margin-bottom: .15rem;
    }

    .ecc-vault-sidebar-item-text {
        color: rgba(245,239,225,.56);
        font-size: .84rem;
        line-height: 1.7;
    }

    .ecc-vault-value-card {
        padding: 1.5rem;
        border-left: 4px solid var(--ecc-primary);
        border-radius: 1.1rem;
        background: rgba(255,255,255,.03);
        border-top: 1px solid rgba(199, 167, 90,.08);
        border-right: 1px solid rgba(199, 167, 90,.08);
        border-bottom: 1px solid rgba(199, 167, 90,.08);
    }

    .ecc-vault-value-label {
        color: rgba(245,239,225,.56);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .18em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }

    .ecc-vault-value-amount {
        color: #fff;
        font-size: clamp(1.8rem, 3vw, 2.6rem);
        font-weight: 900;
        letter-spacing: -.04em;
    }

    .ecc-vault-value-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(245,239,225,.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: rgba(245,239,225,.52);
        font-size: .64rem;
        font-weight: 900;
        letter-spacing: .18em;
        text-transform: uppercase;
    }

    .ecc-vault-value-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 10px rgba(16,185,129,.45);
    }

    .ecc-vault-toggle-btn {
        width: 40px;
        height: 40px;
        border-radius: .85rem;
        border: 1px solid rgba(199, 167, 90,.08);
        background: rgba(255,255,255,.03);
        color: rgba(245,239,225,.56);
    }

    .ecc-vault-toggle-btn.is-active,
    .ecc-vault-toggle-btn:hover {
        color: var(--ecc-primary);
        border-color: rgba(199, 167, 90,.24);
        background: rgba(199, 167, 90,.06);
    }

    .ecc-vault-artifact-card,
    .ecc-vault-appraisal-card {
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .ecc-vault-artifact-media {
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: #121212;
    }

    .ecc-vault-artifact-media img {
        transition: .7s ease;
        filter: grayscale(.55);
        opacity: .78;
    }

    .ecc-vault-artifact-card:hover .ecc-vault-artifact-media img {
        transform: scale(1.08);
        filter: grayscale(0);
        opacity: 1;
    }

    .ecc-vault-artifact-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: .35rem .7rem;
        border-radius: .6rem;
        background: rgba(0,0,0,.62);
        border: 1px solid rgba(199, 167, 90,.28);
        color: var(--ecc-primary);
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .ecc-vault-artifact-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .ecc-vault-artifact-title {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -.03em;
        margin-bottom: .5rem;
    }

    .ecc-vault-artifact-text {
        color: rgba(245,239,225,.60);
        line-height: 1.75;
        margin-bottom: 1rem;
    }

    .ecc-vault-inline-link {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        color: var(--ecc-primary);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .16em;
        text-transform: uppercase;
        text-decoration: none;
    }

    .ecc-vault-inline-link:hover {
        color: #e7c75c;
    }

    .ecc-vault-ref {
        color: rgba(245,239,225,.35);
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .ecc-vault-appraisal-card {
        border-style: dashed;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
    }

    .ecc-vault-appraisal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(199, 167, 90,.08);
        color: var(--ecc-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1.25rem;
    }

    .ecc-vault-appraisal-title {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -.02em;
        margin-bottom: .5rem;
        text-transform: uppercase;
    }

    .ecc-vault-appraisal-text {
        color: rgba(245,239,225,.56);
        line-height: 1.75;
        max-width: 260px;
        margin-bottom: 1.5rem;
    }

    .ecc-btn-primary {
        background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
        border: 1px solid var(--ecc-primary);
        color: #16110a !important;
        font-weight: 800;
        border-radius: .95rem;
        text-transform: uppercase;
        letter-spacing: .12em;
        text-decoration: none;
        display: inline-block;
        transition: filter .2s ease;
    }

    .ecc-btn-primary:hover,
    .ecc-btn-primary:focus {
        filter: brightness(1.1);
        color: #16110a;
    }
    .ecc-vault-mini-stat-label {
        color: rgba(245,239,225,.35);
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: .25rem;
    }

    .ecc-vault-mini-stat-value {
        color: #fff;
        font-size: 1.25rem;
        font-weight: 900;
        letter-spacing: -.02em;
    }

    .ecc-vault-alert-pill {
        display: inline-flex;
        align-items: center;
        padding: .45rem .85rem;
        border-radius: .65rem;
        background: rgba(242,185,13,.10);
        border: 1px solid rgba(242,185,13,.20);
        color: #f2b90d;
        font-size: .64rem;
        font-weight: 800;
        letter-spacing: .08em;
    }

    .ecc-vault-pending-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.4);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }

    .ecc-vault-pending-badge {
        background: #f2b90d;
        color: #16110a;
        padding: .5rem 1rem;
        border-radius: .5rem;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .1em;
        box-shadow: 0 4px 15px rgba(242,185,13,0.3);
    }

    .ecc-vault-artifact-price-mini {
        font-size: .85rem;
        font-weight: 900;
        color: var(--ecc-primary);
        letter-spacing: .02em;
    }

    .ecc-vault-modal-content {
        background: #17130b !important;
        border: 1px solid rgba(199, 167, 90,0.2) !important;
        border-radius: 1.5rem !important;
        box-shadow: 0 25px 80px rgba(0,0,0,0.8) !important;
    }

    .ecc-vault-modal-title {
        font-size: 2.25rem;
        font-weight: 900;
        letter-spacing: -0.04em;
        color: #fff;
    }

    .ecc-vault-modal-total {
        font-size: 1.75rem;
        font-weight: 900;
        color: var(--ecc-primary);
        letter-spacing: -0.02em;
    }

    .ecc-vault-btn-outline {
        background: transparent;
        border: 1px solid rgba(199, 167, 90,0.4);
        color: var(--ecc-primary);
        font-weight: 800;
        padding: 0.85rem 2rem;
        border-radius: 1rem;
        letter-spacing: 0.1em;
        transition: all 0.2s ease;
    }

    .ecc-vault-btn-outline:hover {
        background: rgba(199, 167, 90,0.1);
        border-color: var(--ecc-primary);
        color: var(--ecc-primary);
    }

    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .border-white-5 { border-color: rgba(255,255,255,0.05) !important; }
    .ecc-vault-input {
        background: rgba(17,13,7,.98) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        color: #fff !important;
        border-radius: 0.75rem !important;
        padding: 0.75rem 1rem !important;
    }
    .ecc-vault-input:focus {
        border-color: var(--ecc-primary) !important;
        box-shadow: 0 0 0 0.25rem rgba(199, 167, 90,0.1) !important;
    }

    .ecc-vault-valuation-line {
        background: linear-gradient(90deg, rgba(199, 167, 90,0.05), transparent);
    }
    
    .c-pointer { cursor: pointer; }
    .transition-all { transition: all 0.2s ease; }
    
    .ecc-address-card input:checked + .ecc-address-card-inner {
        border-color: rgba(199, 167, 90,0.6) !important;
        background: rgba(199, 167, 90,0.05) !important;
    }
    .custom-radio-dot {
        width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.2);
        position: relative; transition: all 0.2s ease;
    }
    .custom-radio-dot.is-checked { border-color: var(--ecc-primary); }
    .custom-radio-dot.is-checked::after {
        content: ''; position: absolute; inset: 2px; border-radius: 50%; background: var(--ecc-primary);
    }
    .ecc-bg-primary-subtle { background: rgba(199, 167, 90,0.1) !important; }

    .custom-checkbox-white .form-check-input {
        background-color: transparent; border-color: rgba(255,255,255,0.2);
    }
    .custom-checkbox-white .form-check-input:checked {
        background-color: var(--ecc-primary); border-color: var(--ecc-primary);
    }
</style>
@endpush
