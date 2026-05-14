@php
    $shipment = $this->order->shippingShipment;
    
    $statusBadges = [
        'draft' => 'bg-secondary-subtle text-secondary',
        'courier_selected' => 'bg-info-subtle text-info',
        'ready_to_ship' => 'bg-primary-subtle text-primary',
        'created' => 'bg-success-subtle text-success',
        'awb_assigned' => 'bg-success-subtle text-success',
        'in_transit' => 'bg-primary-subtle text-primary',
        'delivered' => 'bg-success',
        'failed' => 'bg-danger-subtle text-danger',
        'cancelled' => 'bg-danger-subtle text-danger',
        'rto' => 'bg-warning-subtle text-warning',
    ];

    $badgeClass = $statusBadges[$shipment->status ?? ''] ?? 'bg-secondary-subtle text-secondary';
    $statusLabel = $shipment ? str_replace('_', ' ', ucfirst($shipment->status)) : 'Not Prepared';
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center">
        <h5 class="card-title flex-grow-1 mb-0">Shipping & Fulfillment</h5>
        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
    </div>
    <div class="card-body">
        @if(!$shipment)
            <div class="text-center py-3">
                <i class="ri-truck-line fs-24 text-muted mb-2 d-block"></i>
                <p class="text-muted mb-3">No shipping draft has been prepared for this order yet.</p>
                <button wire:click="refreshCourierSelection" wire:loading.attr="disabled" class="btn btn-sm btn-soft-primary">
                    <span wire:loading.remove wire:target="refreshCourierSelection">Prepare Courier Selection</span>
                    <span wire:loading wire:target="refreshCourierSelection">Preparing...</span>
                </button>
            </div>
        @else
            {{-- Courier Info --}}
            <div class="mb-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-grow-1">
                        <h6 class="fs-13 mb-1">Selected Courier</h6>
                        <p class="text-muted mb-0">{{ $shipment->courier_name ?? 'Not Selected' }}</p>
                    </div>
                    @if($shipment->courier_rating)
                        <div class="text-end">
                            <span class="badge bg-warning-subtle text-warning">
                                <i class="ri-star-fill me-1"></i>{{ $shipment->courier_rating }}
                            </span>
                        </div>
                    @endif
                </div>
                
                @if($shipment->courier_total_charge)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Est. Charge:</span>
                        <span class="fw-medium text-primary">INR {{ number_format($shipment->courier_total_charge, 2) }}</span>
                    </div>
                @endif
                
                @if($shipment->courier_etd)
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Est. Delivery:</span>
                        <span class="fw-medium">{{ $shipment->courier_etd }}</span>
                    </div>
                @endif
            </div>

            <hr class="text-muted opacity-25">

            {{-- Package Details --}}
            <div class="mb-3">
                <h6 class="fs-13 mb-2">Package Details</h6>
                <div class="row text-center g-2">
                    <div class="col-6">
                        <div class="p-2 border border-dashed rounded">
                            <h5 class="fs-12 mb-1">{{ $shipment->chargeable_weight_kg ?? '0.000' }} kg</h5>
                            <p class="text-muted fs-11 mb-0">Chargeable</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border border-dashed rounded">
                            <h5 class="fs-12 mb-1">{{ $shipment->length_cm }}x{{ $shipment->breadth_cm }}x{{ $shipment->height_cm }}</h5>
                            <p class="text-muted fs-11 mb-0">Size (cm)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between fs-12 mb-1">
                <span class="text-muted">Pickup Pincode:</span>
                <span class="fw-medium">{{ $shipment->pickup_pincode ?? 'N/A' }}</span>
            </div>
            <div class="d-flex justify-content-between fs-12 mb-3">
                <span class="text-muted">Delivery Pincode:</span>
                <span class="fw-medium">{{ $shipment->delivery_pincode ?? 'N/A' }}</span>
            </div>

            @if($shipment->provider_order_id)
                <hr class="text-muted opacity-25">
                <div class="mb-3">
                    <h6 class="fs-13 mb-2">Shiprocket Details</h6>
                    <div class="d-flex justify-content-between fs-12 mb-1">
                        <span class="text-muted">Order ID:</span>
                        <span class="fw-medium">{{ $shipment->provider_order_id }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-12 mb-1">
                        <span class="text-muted">AWB Code:</span>
                        <span class="fw-medium text-success">{{ $shipment->awb_code ?? 'Pending' }}</span>
                    </div>
                </div>
            @endif

            {{-- Documents (Phase 5) --}}
            @if($shipment->provider_order_id)
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" @if(!$shipment->label_url) disabled @endif>
                        <i class="ri-file-pdf-line me-1"></i> Label
                    </button>
                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" @if(!$shipment->invoice_url) disabled @endif>
                        <i class="ri-file-list-3-line me-1"></i> Invoice
                    </button>
                </div>
            @endif

            {{-- Actions --}}
            <div class="d-grid gap-2 mt-2">
                @if(!$shipment->provider_order_id)
                    <button wire:click="refreshCourierSelection" wire:loading.attr="disabled" class="btn btn-sm btn-soft-info">
                        <i class="ri-refresh-line align-middle me-1"></i> Refresh Courier
                    </button>
                    
                    <button class="btn btn-sm btn-primary" disabled data-bs-toggle="tooltip" title="Shipment initiation will be enabled in Phase 5">
                        Initiate Shipment
                    </button>
                @else
                    <button class="btn btn-sm btn-soft-primary" disabled>
                        <i class="ri-map-pin-line align-middle me-1"></i> Refresh Tracking
                    </button>
                @endif
            </div>

            {{-- Tracking History (Small section) --}}
            @if($shipment->events && $shipment->events->count() > 0)
                <div class="mt-3">
                    <h6 class="fs-13 mb-2">Tracking History</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach($shipment->events->take(3) as $event)
                            <li class="mb-2">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <h6 class="fs-12 mb-0">{{ $event->event_status }}</h6>
                                        <p class="text-muted fs-11 mb-0">{{ $event->location }} | {{ $event->event_time->format('d M, H:i') }}</p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </div>
</div>
