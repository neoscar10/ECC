<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Vault Removal Requests</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Vault Management</a></li>
                        <li class="breadcrumb-item active">Removal Requests</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
 
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-auto">
                            <h5 class="card-title mb-0">Manage Removal Requests</h5>
                        </div>
                        <div class="col-sm">
                            <div class="d-flex justify-content-sm-end gap-2">
                                <div class="search-box ms-2">
                                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search user or item reference...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                                <select wire:model.live="statusFilter" class="form-control" style="width: 240px;">
                                    <option value="">All Requests</option>
                                    <option value="pending">Pending Review (All)</option>
                                    <option value="pending_review">Paid — Pending Review</option>
                                    <option value="ready_for_fulfillment">Paid — Ready for Fulfillment</option>
                                    <option value="refund_required">Refund Required</option>
                                    <option value="approved">Approved (All)</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Member & Date</th>
                                    <th>Asset Details</th>
                                    <th>Delivery Destination</th>
                                    <th>Status & Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @forelse ($requests as $request)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1">{{ $request->user->name }}</h5>
                                                    <p class="text-muted mb-0 fs-11">{{ $request->requested_at->format('d M Y, h:i A') }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($request->vaultItem->display_image_url)
                                                    <img src="{{ $request->vaultItem->display_image_url }}" class="avatar-xs rounded me-2 object-fit-cover" alt="">
                                                @endif
                                                <div>
                                                    <h5 class="fs-13 mb-0">{{ $request->vaultItem->item_title }}</h5>
                                                    <p class="text-muted mb-0 fs-11">{{ $request->vaultItem->item_ref }} • {{ $request->vaultItem->quantity }} qty</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($request->delivery_name)
                                                <div class="fs-13 mb-1">{{ $request->delivery_name }}</div>
                                                <div class="text-muted fs-11 lh-1">
                                                    {{ $request->delivery_line1 }}<br>
                                                    {{ $request->delivery_city }}, {{ $request->delivery_state }} {{ $request->delivery_postal_code }}<br>
                                                    Ph: {{ $request->delivery_phone }}
                                                </div>
                                            @else
                                                <span class="text-muted fs-11 fst-italic">Legacy Removal / No Address</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- Request Status Badges --}}
                                            @if($request->isReadyForFulfillment())
                                                <span class="badge bg-warning text-dark text-uppercase">Ready for Fulfillment</span>
                                            @elseif($request->status === 'rejected' && $request->payment_status === 'refund_required')
                                                <span class="badge bg-danger text-white text-uppercase">Refund Required</span>
                                            @else
                                                @php
                                                    $badgeClass = match($request->status) {
                                                        'pending' => 'bg-warning-subtle text-warning',
                                                        'approved' => 'bg-info-subtle text-info',
                                                        'rejected' => 'bg-danger-subtle text-danger',
                                                        'completed' => 'bg-success-subtle text-success',
                                                        default => 'bg-light text-muted',
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }} text-uppercase">{{ $request->status }}</span>
                                            @endif

                                            {{-- Payment Status Badge (only when relevant) --}}
                                            @if($request->payment_status && $request->payment_status !== 'none')
                                                @php
                                                    $payBadge = match($request->payment_status) {
                                                        'pending_payment' => 'bg-warning-subtle text-warning',
                                                        'paid' => 'bg-success-subtle text-success',
                                                        'payment_failed' => 'bg-danger-subtle text-danger',
                                                        'refund_required' => 'bg-danger-subtle text-danger',
                                                        'refunded' => 'bg-secondary-subtle text-secondary',
                                                        default => 'bg-light text-muted',
                                                    };
                                                @endphp
                                                <span class="badge {{ $payBadge }} text-uppercase ms-1">{{ $request->payment_status_label }}</span>
                                            @endif

                                            {{-- Delivery Fee --}}
                                            @if($request->delivery_fee)
                                                <div class="text-muted fs-11 mt-1">
                                                    Fee: {{ $request->delivery_currency ?? 'INR' }} {{ number_format($request->delivery_fee, 2) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                {{-- View Details button --}}
                                                <button wire:click="showDetails({{ $request->id }})" class="btn btn-sm btn-soft-primary" title="View Details">
                                                    <i class="ri-eye-line"></i> View
                                                </button>

                                                @if($request->payment_status === 'pending_payment')
                                                    {{-- Awaiting Payment message --}}
                                                    <span class="text-muted fs-11 align-self-center fst-italic">Awaiting payment...</span>
                                                @elseif($request->payment_status === 'paid' && $request->status === 'pending')
                                                    <button onclick="confirmApproval({{ $request->id }}, '{{ addslashes($request->user->name) }}', '{{ addslashes($request->vaultItem->item_title) }}', '{{ $request->delivery_currency ?? 'INR' }} {{ number_format($request->delivery_fee, 2) }}', '{{ $request->selected_courier_name ?? 'Standard' }}', '{{ $request->delivery_postal_code }}', '{{ $request->chargeable_weight_kg ?? 0.0 }}')" class="btn btn-sm btn-soft-info" title="Approve for Fulfillment">
                                                        <i class="ri-check-line"></i> Approve
                                                    </button>
                                                    <button onclick="confirmRejection({{ $request->id }}, true)" class="btn btn-sm btn-soft-danger" title="Reject Request">
                                                        <i class="ri-close-line"></i> Reject
                                                    </button>
                                                @elseif($request->payment_status === 'refund_required' && $request->status === 'rejected')
                                                    <button onclick="confirmRefund({{ $request->id }})" class="btn btn-sm btn-soft-success" title="Mark Refund Handled">
                                                        <i class="ri-refund-2-line"></i> Refund
                                                    </button>
                                                @elseif($request->status === 'pending')
                                                    {{-- Legacy pending request action --}}
                                                    <button wire:click="approveRequest({{ $request->id }})" class="btn btn-sm btn-soft-info" title="Approve">
                                                        <i class="ri-check-line"></i> Approve
                                                    </button>
                                                    <button onclick="confirmRejection({{ $request->id }}, false)" class="btn btn-sm btn-soft-danger" title="Reject">
                                                        <i class="ri-close-line"></i> Reject
                                                    </button>
                                                @elseif($request->status === 'approved' && (!$request->delivery_fee || $request->payment_status === 'none'))
                                                    {{-- Legacy approved request can be completed --}}
                                                    <button wire:click="completeRequest({{ $request->id }})" class="btn btn-sm btn-success" title="Mark as Released">
                                                        <i class="ri-checkbox-circle-line"></i> Complete Release
                                                    </button>
                                                @elseif($request->status === 'approved' && $request->payment_status === 'paid')
                                                    {{-- Paid physical delivery approval --}}
                                                    <span class="text-success fs-11 align-self-center fst-italic"><i class="ri-checkbox-circle-line me-1"></i> Approved</span>
                                                @endif
                                                
                                                @if($request->message)
                                                    <button type="button" class="btn btn-sm btn-soft-dark" 
                                                            onclick="SwiperCustom.showInfo('User Message', '{{ addslashes($request->message) }}')">
                                                        <i class="ri-chat-1-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                             <i class="ri-safe-2-line fs-1 d-block mb-2"></i>
                                             No removal requests found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
 
                    <div class="d-flex justify-content-end mt-3">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Modal --}}
    <div wire:ignore.self class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light p-3 border-bottom">
                    <h5 class="modal-title" id="requestDetailsModalLabel">Request Details #{{ $selectedRequest?->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeDetails"></button>
                </div>
                @if($selectedRequest)
                    <div class="modal-body">
                        <div class="row">
                            <!-- Left Column: Asset & Customer Details -->
                            <div class="col-md-4 border-end">
                                <h6 class="text-primary text-uppercase fs-12 mb-3">Asset & Customer</h6>
                                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    @if($selectedRequest->vaultItem->display_image_url)
                                        <img src="{{ $selectedRequest->vaultItem->display_image_url }}" class="avatar-sm rounded me-3 object-fit-cover" alt="">
                                    @endif
                                    <div>
                                        <h5 class="fs-14 mb-1">{{ $selectedRequest->vaultItem->item_title }}</h5>
                                        <p class="text-muted mb-0 fs-12">Ref: {{ $selectedRequest->vaultItem->item_ref }}</p>
                                        <p class="text-muted mb-0 fs-12">Quantity: {{ $selectedRequest->vaultItem->quantity }} qty</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted d-block fs-11">Member Name:</span>
                                    <span class="fs-13 fw-semibold text-dark">{{ $selectedRequest->user->name }}</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted d-block fs-11">Member Email:</span>
                                    <span class="fs-13">{{ $selectedRequest->user->email }}</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted d-block fs-11">Requested On:</span>
                                    <span class="fs-13 text-dark">{{ $selectedRequest->requested_at->format('d M Y, h:i A') }}</span>
                                </div>
                                @if($selectedRequest->message)
                                    <div class="mb-3">
                                        <span class="text-muted d-block fs-11">User Message:</span>
                                        <div class="p-2 bg-light rounded fs-12 text-dark">{{ $selectedRequest->message }}</div>
                                    </div>
                                @endif
                            </div>

                            <!-- Middle Column: Destination & Payment Details -->
                            <div class="col-md-4 border-end">
                                <!-- Delivery Address -->
                                <h6 class="text-primary text-uppercase fs-12 mb-2">Delivery Destination</h6>
                                @if($selectedRequest->delivery_name)
                                    <div class="p-2 border rounded bg-white fs-12 mb-3 shadow-sm">
                                        <strong class="d-block mb-1 text-dark fs-13">{{ $selectedRequest->delivery_name }}</strong>
                                        {{ $selectedRequest->delivery_line1 }}<br>
                                        @if($selectedRequest->delivery_line2) {{ $selectedRequest->delivery_line2 }}<br> @endif
                                        {{ $selectedRequest->delivery_city }}, {{ $selectedRequest->delivery_state }} {{ $selectedRequest->delivery_postal_code }}<br>
                                        <span class="text-muted">Country:</span> {{ $selectedRequest->delivery_country }}<br>
                                        <span class="text-muted">Phone:</span> {{ $selectedRequest->delivery_phone }}
                                    </div>
                                @else
                                    <div class="text-muted fs-12 fst-italic mb-3">No delivery address provided (Legacy Removal).</div>
                                @endif

                                <!-- Payment Audit details -->
                                @if($selectedRequest->payment_status !== 'none')
                                    <h6 class="text-primary text-uppercase fs-12 mb-2">Payment Details</h6>
                                    <table class="table table-sm table-borderless fs-12 mb-3">
                                        <tr>
                                            <td class="text-muted p-0 py-1" style="width: 140px;">Status:</td>
                                            <td class="p-0 py-1"><span class="fw-semibold text-uppercase text-dark">{{ $selectedRequest->payment_status_label }}</span></td>
                                        </tr>
                                        @if($selectedRequest->payment_reference)
                                            <tr>
                                                <td class="text-muted p-0 py-1">Transaction Ref:</td>
                                                <td class="p-0 py-1"><code>{{ $selectedRequest->payment_reference }}</code></td>
                                            </tr>
                                        @endif
                                        @if($selectedRequest->paid_at)
                                            <tr>
                                                <td class="text-muted p-0 py-1">Paid At:</td>
                                                <td class="p-0 py-1">{{ $selectedRequest->paid_at->format('d M Y, h:i A') }}</td>
                                            </tr>
                                        @endif
                                        @if($selectedRequest->refunded_at)
                                            <tr>
                                                <td class="text-muted p-0 py-1">Refunded At:</td>
                                                <td class="p-0 py-1">{{ $selectedRequest->refunded_at->format('d M Y, h:i A') }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted p-0 py-1">Refund Ref:</td>
                                                <td class="p-0 py-1"><code>{{ $selectedRequest->refund_reference }}</code></td>
                                            </tr>
                                        @endif
                                    </table>
                                @endif

                                @if($selectedRequest->admin_note)
                                    <h6 class="text-primary text-uppercase fs-12 mb-2">Admin Notes & Logs</h6>
                                    <div class="p-2 border rounded bg-warning-subtle text-warning-emphasis fs-12" style="white-space: pre-wrap; max-height: 120px; overflow-y: auto;">{{ $selectedRequest->admin_note }}</div>
                                @endif
                            </div>

                            <!-- Right Column: Shipping & Fulfillment Dashboard -->
                            <div class="col-md-4">
                                <h6 class="text-primary text-uppercase fs-12 mb-3">Fulfillment & Shipment</h6>

                                @php
                                    $shipment = $selectedRequest->shippingShipment;
                                    
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

                                <div class="card shadow-none border mb-3">
                                    @if(config('shiprocket.test_mode'))
                                        <div class="card-header bg-warning-subtle text-warning border-bottom-0 py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="ri-alert-line fs-14 me-2"></i>
                                                <span class="fs-11 fw-medium">Test Mode Active — simulations enabled</span>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="card-header d-flex align-items-center py-2 bg-light">
                                        <span class="fs-12 fw-semibold text-dark flex-grow-1">Fulfillment Status</span>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="card-body p-2 fs-12">
                                        @if($selectedRequest->delivery_fee)
                                            <!-- Courier Quote Details -->
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Courier:</span>
                                                    <span class="fw-semibold text-dark">{{ $selectedRequest->selected_courier_name ?? 'Standard' }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Paid Shipping:</span>
                                                    <span class="fw-medium text-success">{{ $selectedRequest->delivery_currency ?? 'INR' }} {{ number_format($selectedRequest->delivery_fee, 2) }}</span>
                                                </div>
                                                @if($shipment && $shipment->courier_total_charge)
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Actual Quote:</span>
                                                        <span class="fw-medium text-primary">INR {{ number_format($shipment->courier_total_charge, 2) }}</span>
                                                    </div>
                                                @endif
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Package Weight:</span>
                                                    <span class="fw-medium">{{ number_format($selectedRequest->chargeable_weight_kg ?? 0.5, 3) }} kg</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Dimensions:</span>
                                                    <span class="fw-medium">{{ number_format($selectedRequest->package_length_cm ?? 10) }}x{{ number_format($selectedRequest->package_breadth_cm ?? 10) }}x{{ number_format($selectedRequest->package_height_cm ?? 10) }} cm</span>
                                                </div>
                                            </div>

                                            @if($shipment && $shipment->provider_order_id)
                                                <hr class="my-2 text-muted opacity-25">
                                                <div class="mb-2">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Order ID:</span>
                                                        <span class="fw-semibold">{{ $shipment->provider_order_id }}</span>
                                                    </div>
                                                    @if($shipment->provider_shipment_id)
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span class="text-muted">Shipment ID:</span>
                                                            <span class="fw-semibold">{{ $shipment->provider_shipment_id }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">AWB Code:</span>
                                                        <span class="fw-semibold text-success">{{ $shipment->awb_code ?? 'Pending AWB Assignment' }}</span>
                                                    </div>
                                                </div>

                                                <!-- Documents Download Panel -->
                                                <div class="d-flex gap-1 mb-2">
                                                    <!-- Label -->
                                                    @if($shipment->label_url)
                                                        <a href="{{ $shipment->label_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0 text-center">
                                                            <i class="ri-file-pdf-line me-1"></i>Label
                                                        </a>
                                                    @elseif(isset($shipment->metadata['documents']['label']['simulated']) && $shipment->metadata['documents']['label']['simulated'])
                                                        <button class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0" disabled data-bs-toggle="tooltip" title="Generated in test mode">
                                                            <i class="ri-file-pdf-line me-1"></i>Label
                                                        </button>
                                                    @elseif(isset($shipment->metadata['documents']['label']['generated']) && $shipment->metadata['documents']['label']['generated'])
                                                        <button class="btn btn-xs btn-soft-warning flex-grow-1 py-1 px-0" disabled data-bs-toggle="tooltip" title="Generated — download URL not returned. Check Shiprocket.">
                                                            <i class="ri-error-warning-line me-1"></i>Label
                                                        </button>
                                                    @else
                                                        <button class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0" wire:click="generateDocument({{ $selectedRequest->id }}, 'label')" wire:loading.attr="disabled">
                                                            <span wire:loading.remove wire:target="generateDocument({{ $selectedRequest->id }}, 'label')"><i class="ri-file-pdf-line me-1"></i>Label</span>
                                                            <span wire:loading wire:target="generateDocument({{ $selectedRequest->id }}, 'label')">...</span>
                                                        </button>
                                                    @endif

                                                    <!-- Invoice -->
                                                    @if($shipment->invoice_url)
                                                        <a href="{{ $shipment->invoice_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0 text-center">
                                                            <i class="ri-file-list-3-line me-1"></i>Invoice
                                                        </a>
                                                    @elseif(isset($shipment->metadata['documents']['invoice']['simulated']) && $shipment->metadata['documents']['invoice']['simulated'])
                                                        <button class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0" disabled data-bs-toggle="tooltip" title="Generated in test mode">
                                                            <i class="ri-file-list-3-line me-1"></i>Invoice
                                                        </button>
                                                    @elseif(isset($shipment->metadata['documents']['invoice']['generated']) && $shipment->metadata['documents']['invoice']['generated'])
                                                        <button class="btn btn-xs btn-soft-warning flex-grow-1 py-1 px-0" disabled data-bs-toggle="tooltip" title="Generated — download URL not returned. Check Shiprocket.">
                                                            <i class="ri-error-warning-line me-1"></i>Invoice
                                                        </button>
                                                    @else
                                                        <button class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0" wire:click="generateDocument({{ $selectedRequest->id }}, 'invoice')" wire:loading.attr="disabled">
                                                            <span wire:loading.remove wire:target="generateDocument({{ $selectedRequest->id }}, 'invoice')"><i class="ri-file-list-3-line me-1"></i>Invoice</span>
                                                            <span wire:loading wire:target="generateDocument({{ $selectedRequest->id }}, 'invoice')">...</span>
                                                        </button>
                                                    @endif

                                                    <!-- Manifest -->
                                                    @if($shipment->manifest_url)
                                                        <a href="{{ $shipment->manifest_url }}" target="_blank" rel="noopener" class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0 text-center">
                                                            <i class="ri-file-list-3-line me-1"></i>Manifest
                                                        </a>
                                                    @elseif(isset($shipment->metadata['documents']['manifest']['simulated']) && $shipment->metadata['documents']['manifest']['simulated'])
                                                        <button class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0" disabled data-bs-toggle="tooltip" title="Generated in test mode">
                                                            <i class="ri-file-list-3-line me-1"></i>Manifest
                                                        </button>
                                                    @elseif(isset($shipment->metadata['documents']['manifest']['generated']) && $shipment->metadata['documents']['manifest']['generated'])
                                                        <button class="btn btn-xs btn-soft-warning flex-grow-1 py-1 px-0" disabled data-bs-toggle="tooltip" title="Generated — download URL not returned. Check Shiprocket.">
                                                            <i class="ri-error-warning-line me-1"></i>Manifest
                                                        </button>
                                                    @else
                                                        <button class="btn btn-xs btn-soft-secondary flex-grow-1 py-1 px-0" wire:click="generateDocument({{ $selectedRequest->id }}, 'manifest')" wire:loading.attr="disabled">
                                                            <span wire:loading.remove wire:target="generateDocument({{ $selectedRequest->id }}, 'manifest')"><i class="ri-file-list-3-line me-1"></i>Manifest</span>
                                                            <span wire:loading wire:target="generateDocument({{ $selectedRequest->id }}, 'manifest')">...</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- Main Fulfillment Actions -->
                                            <div class="d-grid gap-1 mt-2">
                                                @if(!$shipment || !$shipment->provider_order_id)
                                                    @if($selectedRequest->status === 'approved' && $selectedRequest->payment_status === 'paid')
                                                        <button type="button" class="btn btn-sm btn-primary w-100 py-1.5"
                                                                onclick="confirmInitiateShipment({{ $selectedRequest->id }}, '{{ addslashes($selectedRequest->selected_courier_name ?? 'Standard') }}', '{{ addslashes($selectedRequest->delivery_name) }}', '{{ $selectedRequest->delivery_postal_code }}', '{{ $selectedRequest->delivery_currency ?? 'INR' }} {{ number_format($selectedRequest->delivery_fee, 2) }}')">
                                                            <i class="ri-truck-line me-1 align-middle"></i> Initiate Shipment
                                                        </button>
                                                    @else
                                                        <div class="alert alert-light border py-1.5 text-center text-muted mb-0 fs-11">
                                                            Awaiting request approval to initiate shipment.
                                                        </div>
                                                    @endif
                                                @else
                                                    @if(!$shipment->awb_code)
                                                        <button wire:click="retryAssignAwb({{ $selectedRequest->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-warning w-100 py-1">
                                                            <i class="ri-refresh-line me-1 align-middle"></i> Retry AWB Assignment
                                                        </button>
                                                    @endif
                                                    
                                                    <div class="row g-1">
                                                        <div class="col-6">
                                                            <button wire:click="refreshTracking({{ $selectedRequest->id }})" wire:loading.attr="disabled" class="btn btn-xs btn-soft-primary w-100 py-1.5">
                                                                <span wire:loading.remove wire:target="refreshTracking({{ $selectedRequest->id }})"><i class="ri-map-pin-line align-middle"></i> Refresh</span>
                                                                <span wire:loading wire:target="refreshTracking({{ $selectedRequest->id }})">...</span>
                                                            </button>
                                                        </div>
                                                        <div class="col-6">
                                                            @if($selectedRequest->status !== 'completed')
                                                                <button type="button" class="btn btn-xs btn-success w-100 py-1.5" onclick="confirmCompleteDelivery({{ $selectedRequest->id }}, '{{ $shipment->status }}')">
                                                                    <i class="ri-checkbox-circle-line align-middle"></i> Complete
                                                                </button>
                                                            @else
                                                                <button class="btn btn-xs btn-light w-100 py-1.5" disabled>
                                                                    <i class="ri-checkbox-circle-fill text-success align-middle"></i> Completed
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Timeline history -->
                                            @if($shipment && $shipment->provider_order_id)
                                                <hr class="my-2 text-muted opacity-25">
                                                <div class="mt-2" style="max-height: 140px; overflow-y: auto;">
                                                    <h6 class="fs-11 fw-semibold text-muted text-uppercase mb-2">Tracking History</h6>
                                                    @if($shipment->events && $shipment->events->count() > 0)
                                                        <ul class="list-unstyled mb-0" style="padding-left: 2px;">
                                                            @foreach($shipment->events->sortByDesc('event_time')->take(3) as $event)
                                                                <li class="mb-2 border-bottom pb-1">
                                                                    <span class="fw-semibold text-primary fs-11 d-block">{{ $event->event_status }}</span>
                                                                    @if($event->event_description)
                                                                        <span class="text-muted d-block" style="font-size: 10px; line-height: 1.2;">{{ $event->event_description }}</span>
                                                                    @endif
                                                                    <span class="text-muted" style="font-size: 9px;"><i class="ri-map-pin-2-line"></i>{{ $event->location ?? 'Unknown' }} | {{ $event->event_time ? $event->event_time->format('d M, H:i') : 'N/A' }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted fs-11 fst-italic">No tracking events logged yet.</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            <!-- Non-delivery / legacy -->
                                            <div class="text-center py-3 text-muted">
                                                <i class="ri-coupon-2-line fs-20 d-block mb-1"></i>
                                                <span class="d-block mb-2">Legacy request / Manual Release</span>
                                                @if($selectedRequest->status === 'approved')
                                                    <button wire:click="completeRequest({{ $selectedRequest->id }})" class="btn btn-sm btn-success w-100">
                                                        <i class="ri-checkbox-circle-line align-middle me-1"></i> Complete Release
                                                    </button>
                                                @else
                                                    <span class="fs-11 fst-italic">Approve request to finalize release.</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <div class="d-flex justify-content-between w-100 align-items-center">
                            <div>
                                @if($selectedRequest->status === 'approved' && $selectedRequest->payment_status === 'paid')
                                    @if($shipment && $shipment->provider_order_id)
                                        <span class="text-success fs-12 fw-semibold"><i class="ri-checkbox-circle-fill me-1"></i> Shipment Fulfillments in Progress</span>
                                    @else
                                        <span class="text-warning fs-12 fw-semibold"><i class="ri-truck-fill me-1"></i> Paid & Approved — Initiate shipment below</span>
                                    @endif
                                @elseif($selectedRequest->status === 'rejected' && $selectedRequest->payment_status === 'refund_required')
                                    <span class="text-danger fs-12 fw-semibold"><i class="ri-error-warning-fill me-1"></i> Awaiting Manual Refund</span>
                                @elseif($selectedRequest->status === 'pending' && $selectedRequest->payment_status === 'paid')
                                    <span class="text-primary fs-12 fw-semibold"><i class="ri-time-line me-1"></i> Awaiting Admin Review</span>
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                {{-- Actions inside modal --}}
                                @if($selectedRequest->payment_status === 'paid' && $selectedRequest->status === 'pending')
                                    <button type="button" class="btn btn-soft-success btn-sm"
                                            onclick="confirmApproval({{ $selectedRequest->id }}, '{{ addslashes($selectedRequest->user->name) }}', '{{ addslashes($selectedRequest->vaultItem->item_title) }}', '{{ $selectedRequest->delivery_currency ?? 'INR' }} {{ number_format($selectedRequest->delivery_fee, 2) }}', '{{ $selectedRequest->selected_courier_name ?? 'Standard' }}', '{{ $selectedRequest->delivery_postal_code }}', '{{ $selectedRequest->chargeable_weight_kg ?? 0.0 }}')">
                                        <i class="ri-check-line"></i> Approve for Fulfillment
                                    </button>
                                    <button type="button" class="btn btn-soft-danger btn-sm" onclick="confirmRejection({{ $selectedRequest->id }}, true)">
                                        <i class="ri-close-line"></i> Reject Request
                                    </button>
                                @elseif($selectedRequest->payment_status === 'refund_required' && $selectedRequest->status === 'rejected')
                                    <button type="button" class="btn btn-soft-success btn-sm" onclick="confirmRefund({{ $selectedRequest->id }})">
                                        <i class="ri-refund-2-line"></i> Mark Refund Handled
                                    </button>
                                @endif

                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" wire:click="closeDetails">Close</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.isTestMode = {{ config('shiprocket.test_mode') ? 'true' : 'false' }};

    document.addEventListener('livewire:init', () => {
        let detailsModal = null;
        
        Livewire.on('open-details-modal', () => {
            if (!detailsModal) {
                detailsModal = new bootstrap.Modal(document.getElementById('requestDetailsModal'));
            }
            detailsModal.show();
        });

        Livewire.on('close-details-modal', () => {
            if (detailsModal) {
                detailsModal.hide();
            }
        });
    });

    function confirmApproval(id, userName, itemTitle, fee, courier, pincode, weight) {
        Swal.fire({
            title: 'Approve Delivery Request?',
            html: `
                <div class="text-start">
                    <p class="mb-2"><strong>Member:</strong> ${userName}</p>
                    <p class="mb-2"><strong>Item:</strong> ${itemTitle}</p>
                    <p class="mb-2"><strong>Courier:</strong> ${courier}</p>
                    <p class="mb-2"><strong>Destination Pincode:</strong> ${pincode}</p>
                    <p class="mb-2"><strong>Chargeable Weight:</strong> ${weight} kg</p>
                    <p class="mb-2"><strong>Delivery Fee Paid:</strong> <span class="text-success">${fee}</span></p>
                    <hr>
                    <div class="alert alert-warning py-2 fs-12 mb-0 mt-2">
                        <i class="ri-alert-line me-1"></i> This will mark the request as **Ready for Fulfillment**. Shipment will still need to be initiated separately.
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve for Fulfillment',
            confirmButtonColor: '#0ab39c',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('approveRequest', id);
            }
        });
    }

    function confirmRejection(id, isPaid) {
        Swal.fire({
            title: isPaid ? 'Reject Paid Delivery Request?' : 'Reject Request?',
            html: `
                <div class="text-start">
                    <p class="mb-3">${isPaid ? '<strong>Warning:</strong> This request has already been paid. Rejecting it will flag the payment as **Refund Required**.' : 'Are you sure you want to reject this request?'}</p>
                    <label class="form-label fs-13 mb-1">Please provide a reason for rejection:</label>
                    <textarea id="rejection-reason" class="form-control" placeholder="Reason..." rows="3"></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Confirm Rejection',
            confirmButtonColor: '#f06548',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const reason = document.getElementById('rejection-reason').value;
                if (!reason || reason.trim() === '') {
                    Swal.showValidationMessage('Rejection reason is required');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                @this.call('rejectRequest', id, result.value);
            }
        });
    }

    function confirmRefund(id) {
        Swal.fire({
            title: 'Mark Refund Handled?',
            html: `
                <div class="text-start">
                    <p class="mb-2 text-muted">Confirm that the delivery fee refund has been manually processed.</p>
                    <label class="form-label fs-13 mb-1 fw-semibold">Refund Reference / Transaction ID:</label>
                    <input id="refund-ref" class="form-control mb-2" placeholder="e.g. RFD_TXN_998877">
                    <label class="form-label fs-13 mb-1">Internal Audit Note (Optional):</label>
                    <textarea id="refund-note" class="form-control" placeholder="Optional notes..."></textarea>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Mark Refunded',
            confirmButtonColor: '#0ab39c',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const ref = document.getElementById('refund-ref').value;
                const note = document.getElementById('refund-note').value;
                if (!ref || ref.trim() === '') {
                    Swal.showValidationMessage('Refund reference is required');
                    return false;
                }
                return { ref: ref, note: note };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('markRefundHandled', id, result.value.ref, result.value.note);
            }
        });
    }

    function confirmInitiateShipment(id, courierName, addressName, pincode, fee) {
        Swal.fire({
            title: 'Initiate Shiprocket Shipment?',
            html: `
                <div class="text-start">
                    <p class="mb-2"><strong>Courier:</strong> ${courierName}</p>
                    <p class="mb-2"><strong>Deliver To:</strong> ${addressName}</p>
                    <p class="mb-2"><strong>Pincode:</strong> ${pincode}</p>
                    <p class="mb-2"><strong>Fee Paid:</strong> <span class="text-success">${fee}</span></p>
                    <hr>
                    <div class="alert alert-info py-2 fs-12 mb-0 mt-2">
                        <i class="ri-information-line me-1"></i> ${window.isTestMode ? "<strong>Test Mode Active:</strong> A mock Shiprocket order and AWB code will be simulated." : "This will create a real order inside your Shiprocket panel and assign an AWB."}
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Initiate Shipment',
            confirmButtonColor: '#405189',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('initiateShipment', id);
            }
        });
    }

    function confirmCompleteDelivery(id, shipmentStatus) {
        let warningHtml = '';
        if (shipmentStatus !== 'delivered') {
            warningHtml = `
                <div class="alert alert-warning py-2 fs-12 mb-2 mt-2">
                    <i class="ri-alert-line me-1"></i> **Warning:** The shipment status is currently in **${shipmentStatus.toUpperCase()}** state, not **DELIVERED**.
                </div>
            `;
        }
        Swal.fire({
            title: 'Complete Vault Release & Delivery?',
            html: `
                <div class="text-start">
                    <p class="mb-2 text-muted">Confirm that this Vault item has been successfully hand-delivered or fulfilled to the customer. This will release the item from the vault.</p>
                    ${warningHtml}
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Complete Release',
            confirmButtonColor: '#0ab39c',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('completeDelivery', id);
            }
        });
    }

    const SwiperCustom = {
        showInfo: function(title, text) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'info',
                confirmButtonColor: '#405189'
            });
        }
    };
</script>
@endpush

