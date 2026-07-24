@php
    $shipment = $this->order->shippingShipment;
    
    $statusBadges = [
        'draft' => 'bg-secondary-subtle text-secondary',
        'courier_selected' => 'bg-info-subtle text-info',
        'ready_to_ship' => 'bg-primary-subtle text-primary',
        'created' => 'bg-success-subtle text-success',
        'awb_assigned' => 'bg-success-subtle text-success',
        'pickup_scheduled' => 'bg-primary-subtle text-primary',
        'in_transit' => 'bg-primary-subtle text-primary',
        'delivered' => 'bg-success text-white',
        'failed' => 'bg-danger-subtle text-danger',
        'cancelled' => 'bg-dark-subtle text-dark',
        'rto' => 'bg-danger-subtle text-danger',
    ];

    $badgeClass = $statusBadges[$shipment->status ?? ''] ?? 'bg-secondary-subtle text-secondary';
    $statusLabel = $shipment ? str_replace('_', ' ', ucfirst($shipment->status)) : 'Not Prepared';
@endphp

<div class="card mb-4">
    @if(config('shiprocket.test_mode'))
        <div class="card-header bg-warning-subtle text-warning border-bottom-0 py-2">
            <div class="d-flex align-items-center">
                <i class="ri-alert-line fs-16 me-2"></i>
                <span class="fs-12 fw-medium">Test Mode Active — shipment initiation will be simulated</span>
            </div>
        </div>
    @endif
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
            <div class="row g-3 mb-3">
                {{-- Left Side: Courier & Package Details --}}
                <div class="col-md-6 border-end-md">
                    <div class="pe-md-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h6 class="fs-13 mb-1">Selected Courier</h6>
                                <p class="text-muted mb-0 fw-medium">{{ $shipment->courier_name ?? 'Not Selected' }}</p>
                            </div>
                            @if($shipment->courier_rating)
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="ri-star-fill me-1"></i>{{ $shipment->courier_rating }}
                                </span>
                            @endif
                        </div>

                        <div class="row g-2 my-2">
                            @if($shipment->courier_total_charge)
                                <div class="col-6">
                                    <span class="text-muted fs-12 d-block">Est. Charge</span>
                                    <span class="fw-semibold text-primary fs-13">INR {{ number_format($shipment->courier_total_charge, 2) }}</span>
                                </div>
                            @endif
                            <div class="col-6">
                                <span class="text-muted fs-12 d-block">Shipping Paid</span>
                                <span class="fw-semibold text-success fs-13">INR {{ number_format($this->order->shipping_charge ?? 0, 2) }}</span>
                            </div>
                        </div>

                        @if($shipment->courier_etd)
                            <div class="mb-2">
                                <span class="text-muted fs-12">Est. Delivery: </span>
                                <span class="fw-medium fs-12">{{ $shipment->courier_etd }}</span>
                            </div>
                        @endif

                        <div class="mt-3 pt-2 border-top border-dashed">
                            <h6 class="fs-13 mb-2">Package Dimensions & Pincodes</h6>
                            <div class="row text-center g-2 mb-2">
                                <div class="col-6">
                                    <div class="p-2 border border-dashed rounded bg-light-subtle">
                                        <h6 class="fs-12 mb-1 fw-bold">{{ $shipment->chargeable_weight_kg ?? '0.000' }} kg</h6>
                                        <p class="text-muted fs-11 mb-0">Chargeable</p>
                                        @if($shipment->volumetric_weight_kg)
                                            <p class="text-muted fs-11 mb-0 mt-1 border-top pt-1 border-dashed">Vol: {{ $shipment->volumetric_weight_kg }} kg</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 border border-dashed rounded bg-light-subtle">
                                        <h6 class="fs-12 mb-1 fw-bold">{{ $shipment->length_cm }}×{{ $shipment->breadth_cm }}×{{ $shipment->height_cm }}</h6>
                                        <p class="text-muted fs-11 mb-0">Size (cm)</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row fs-12 g-1 mt-1">
                                <div class="col-6"><span class="text-muted">Pickup: </span><span class="fw-medium">{{ $shipment->pickup_pincode ?? 'N/A' }}</span></div>
                                <div class="col-6"><span class="text-muted">Delivery: </span><span class="fw-medium">{{ $shipment->delivery_pincode ?? 'N/A' }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Side: Shiprocket Identifiers & Quick Actions --}}
                <div class="col-md-6 ps-md-3">
                    @if($shipment->provider_order_id)
                        <div class="mb-3">
                            <h6 class="fs-13 mb-2">Shiprocket Details</h6>
                            <div class="bg-light-subtle p-2 rounded border border-dashed fs-12">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Order ID:</span>
                                    <span class="fw-medium">{{ $shipment->provider_order_id }}</span>
                                </div>
                                @if($shipment->provider_shipment_id)
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Shipment ID:</span>
                                        <span class="fw-medium">{{ $shipment->provider_shipment_id }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">AWB Code:</span>
                                    <span class="fw-semibold text-success">{{ $shipment->awb_code ?? 'Pending' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Documents --}}
                    @if($shipment->provider_order_id)
                        <div class="mb-3">
                            <h6 class="fs-13 mb-2">Fulfillment Documents</h6>
                            <div class="d-flex flex-wrap gap-2">
                                {{-- Label --}}
                                @if($shipment->label_url)
                                    <a href="{{ $shipment->label_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-soft-secondary flex-grow-1">
                                        <i class="ri-file-pdf-line me-1"></i> Label
                                    </a>
                                @elseif(isset($shipment->metadata['documents']['label']['simulated']) && $shipment->metadata['documents']['label']['simulated'])
                                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" disabled data-bs-toggle="tooltip" title="Generated in test mode">
                                        <i class="ri-file-pdf-line me-1"></i> Label
                                    </button>
                                @elseif(isset($shipment->metadata['documents']['label']['generated']) && $shipment->metadata['documents']['label']['generated'])
                                    <button class="btn btn-sm btn-soft-warning flex-grow-1" disabled data-bs-toggle="tooltip" title="Generated — check Shiprocket panel">
                                        <i class="ri-error-warning-line me-1"></i> Label
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" wire:click="generateDocument('label')" wire:loading.attr="disabled">
                                        <i class="ri-file-pdf-line me-1"></i> 
                                        <span wire:loading.remove wire:target="generateDocument('label')">Generate Label</span>
                                        <span wire:loading wire:target="generateDocument('label')">Generating...</span>
                                    </button>
                                @endif
                                
                                {{-- Invoice --}}
                                @if($shipment->invoice_url)
                                    <a href="{{ $shipment->invoice_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-soft-secondary flex-grow-1">
                                        <i class="ri-file-list-3-line me-1"></i> Invoice
                                    </a>
                                @elseif(isset($shipment->metadata['documents']['invoice']['simulated']) && $shipment->metadata['documents']['invoice']['simulated'])
                                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" disabled data-bs-toggle="tooltip" title="Generated in test mode">
                                        <i class="ri-file-list-3-line me-1"></i> Invoice
                                    </button>
                                @elseif(isset($shipment->metadata['documents']['invoice']['generated']) && $shipment->metadata['documents']['invoice']['generated'])
                                    <button class="btn btn-sm btn-soft-warning flex-grow-1" disabled data-bs-toggle="tooltip" title="Generated — check Shiprocket panel">
                                        <i class="ri-error-warning-line me-1"></i> Invoice
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" wire:click="generateDocument('invoice')" wire:loading.attr="disabled">
                                        <i class="ri-file-list-3-line me-1"></i>
                                        <span wire:loading.remove wire:target="generateDocument('invoice')">Generate Invoice</span>
                                        <span wire:loading wire:target="generateDocument('invoice')">Generating...</span>
                                    </button>
                                @endif
                                
                                {{-- Manifest --}}
                                @if($shipment->manifest_url)
                                    <a href="{{ $shipment->manifest_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-soft-secondary flex-grow-1">
                                        <i class="ri-file-list-3-line me-1"></i> Manifest
                                    </a>
                                @elseif(isset($shipment->metadata['documents']['manifest']['simulated']) && $shipment->metadata['documents']['manifest']['simulated'])
                                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" disabled data-bs-toggle="tooltip" title="Generated in test mode">
                                        <i class="ri-file-list-3-line me-1"></i> Manifest
                                    </button>
                                @elseif(isset($shipment->metadata['documents']['manifest']['generated']) && $shipment->metadata['documents']['manifest']['generated'])
                                    <button class="btn btn-sm btn-soft-warning flex-grow-1" disabled data-bs-toggle="tooltip" title="Generated — check Shiprocket panel">
                                        <i class="ri-error-warning-line me-1"></i> Manifest
                                    </button>
                                @else
                                    <button class="btn btn-sm btn-soft-secondary flex-grow-1" wire:click="generateDocument('manifest')" wire:loading.attr="disabled">
                                        <i class="ri-file-list-3-line me-1"></i>
                                        <span wire:loading.remove wire:target="generateDocument('manifest')">Generate Manifest</span>
                                        <span wire:loading wire:target="generateDocument('manifest')">Generating...</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Primary Actions --}}
                    <div class="d-flex flex-wrap gap-2">
                        @if(!$shipment->provider_order_id)
                            <button wire:click="refreshCourierSelection" wire:loading.attr="disabled" class="btn btn-sm btn-soft-info flex-grow-1">
                                <i class="ri-refresh-line align-middle me-1"></i> Refresh Courier
                            </button>
                            
                            <button wire:click="confirmInitiateShipment" wire:loading.attr="disabled" class="btn btn-sm btn-primary flex-grow-1">
                                Initiate Shipment
                            </button>
                        @else
                            @if(!$shipment->awb_code)
                                <button wire:click="retryAssignAwb" wire:loading.attr="disabled" class="btn btn-sm btn-warning flex-grow-1">
                                    <i class="ri-refresh-line align-middle me-1"></i> Retry AWB Assignment
                                </button>
                            @endif
                            <button wire:click="refreshTracking" wire:loading.attr="disabled" class="btn btn-sm btn-soft-primary flex-grow-1">
                                <i class="ri-map-pin-line align-middle me-1"></i> 
                                <span wire:loading.remove wire:target="refreshTracking">Refresh Tracking</span>
                                <span wire:loading wire:target="refreshTracking">Refreshing...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Tracking History --}}
            <div class="mt-3 pt-3 border-top">
                <h6 class="fs-13 mb-2">Tracking History</h6>
                @if($shipment->events && $shipment->events->count() > 0)
                    <div class="row g-2">
                        @foreach($shipment->events->sortByDesc('event_time')->take(6) as $event)
                            <div class="col-md-6 col-lg-4">
                                <div class="p-2 border rounded bg-light-subtle h-100">
                                    <h6 class="fs-12 mb-1 text-primary text-capitalize">{{ str_replace('_', ' ', $event->event_status) }}</h6>
                                    @if($event->event_description)
                                        <p class="text-muted fs-11 mb-1 text-truncate" title="{{ $event->event_description }}">{{ $event->event_description }}</p>
                                    @endif
                                    <div class="d-flex justify-content-between text-muted fs-11">
                                        <span class="text-truncate" style="max-width: 120px;"><i class="ri-map-pin-2-line me-1"></i>{{ $event->location ?? 'Unknown' }}</span>
                                        <span><i class="ri-time-line me-1"></i>{{ $event->event_time ? $event->event_time->format('d M, H:i') : 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted fs-12 mb-0">No tracking events recorded yet.</p>
                @endif
            </div>
        @endif
    </div>
</div>
