{{-- Initiate Shipment Modal --}}
@if($showInitiateShipmentModal)
<div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header p-3 {{ config('shiprocket.test_mode') ? 'bg-warning-subtle' : 'bg-soft-info' }}">
                <h5 class="modal-title">
                    <i class="ri-truck-line align-middle me-1"></i> 
                    {{ config('shiprocket.test_mode') ? 'Simulate Shipment Initiation?' : 'Initiate Real Shipment?' }}
                </h5>
                <button type="button" class="btn-close" wire:click="$set('showInitiateShipmentModal', false)"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <lord-icon src="https://cdn.lordicon.com/oaajtyjp.json" trigger="loop" colors="{{ config('shiprocket.test_mode') ? 'primary:#f7b84b,secondary:#f06548' : 'primary:#25a0e2,secondary:#00bd9d' }}" style="width:90px;height:90px"></lord-icon>
                    <div class="mt-2 fs-15">
                        <h4>Are you sure?</h4>
                        @if(config('shiprocket.test_mode'))
                            <p class="text-warning mb-0 fw-medium">Test Mode Active: No real Shiprocket shipment will be created.</p>
                            <p class="text-muted mt-2 mb-0">This will simulate Shiprocket order creation, AWB assignment, and document generation locally for development testing.</p>
                        @else
                            <p class="text-danger mb-0 fw-medium">Live Mode Active: This will create a real Shiprocket shipment.</p>
                            <p class="text-muted mt-2 mb-0">Continue only if the order, address, and package details are correct.</p>
                        @endif
                    </div>
                </div>
                
                <div class="mt-4">
                    <ul class="list-group list-group-flush border-top border-bottom">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Order Number</span>
                            <span class="fw-medium">{{ $this->order->order_number ?? 'ECC-'.$this->order->id }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Selected Courier</span>
                            <span class="fw-medium text-info">{{ $this->order->shippingShipment->courier_name ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Shipping Paid</span>
                            <span class="fw-medium text-success">INR {{ number_format($this->order->shipping_charge ?? 0, 2) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Chargeable Weight</span>
                            <span class="fw-medium">{{ $this->order->shippingShipment->chargeable_weight_kg ?? '0' }} kg</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 border-bottom-0">
                            <span class="text-muted">Route</span>
                            <span class="fw-medium">{{ $this->order->shippingShipment->pickup_pincode ?? 'N/A' }} &rarr; {{ $this->order->shippingShipment->delivery_pincode ?? 'N/A' }}</span>
                        </li>
                    </ul>
                </div>

                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                    <button type="button" class="btn w-sm btn-light" wire:click="$set('showInitiateShipmentModal', false)">Cancel</button>
                    @if(!config('shiprocket.test_mode') && !config('shiprocket.live_shipment_enabled'))
                        <div class="alert alert-danger mb-0">Live shipment creation is disabled by configuration.</div>
                    @else
                        <button type="button" class="btn w-sm {{ config('shiprocket.test_mode') ? 'btn-warning' : 'btn-danger' }}" wire:click="initiateShipment" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="initiateShipment">
                                {{ config('shiprocket.test_mode') ? 'Simulate Shipment' : 'Initiate Real Shipment' }}
                            </span>
                            <span wire:loading wire:target="initiateShipment">Processing...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
