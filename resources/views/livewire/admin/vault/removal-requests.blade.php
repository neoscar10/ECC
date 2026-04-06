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
                                <select wire:model.live="statusFilter" class="form-control" style="width: 180px;">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending Review</option>
                                    <option value="approved">Approved</option>
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
                                    <th>Status</th>
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
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @if($request->status === 'pending')
                                                    <button wire:click="approveRequest({{ $request->id }})" class="btn btn-sm btn-soft-info" title="Approve">
                                                        <i class="ri-check-line"></i> Approve
                                                    </button>
                                                    <button onclick="confirmRejection({{ $request->id }})" class="btn btn-sm btn-soft-danger" title="Reject">
                                                        <i class="ri-close-line"></i> Reject
                                                    </button>
                                                @elseif($request->status === 'approved')
                                                    <button wire:click="completeRequest({{ $request->id }})" class="btn btn-sm btn-success" title="Mark as Released">
                                                        <i class="ri-checkbox-circle-line"></i> Complete Release
                                                    </button>
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
                                        <td colspan="4" class="text-center py-5 text-muted">
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
</div>
 
@push('scripts')
<script>
    function confirmRejection(id) {
        Swal.fire({
            title: 'Reject Request?',
            text: "Please provide a reason for rejection:",
            input: 'textarea',
            inputPlaceholder: 'Reason...',
            showCancelButton: true,
            confirmButtonText: 'Confirm Rejection',
            confirmButtonColor: '#f06548',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                @this.call('rejectRequest', id, result.value);
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
