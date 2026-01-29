<!-- View Modal -->
<div wire:ignore.self class="modal fade zoomIn" id="viewMemberModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @if($selectedMembership)
                    <div class="row">
                        <!-- Member Summary -->
                        <div class="col-12 mb-3">
                            <div class="card border shadow-none mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm flex-shrink-0">
                                            <div class="avatar-title bg-light text-primary rounded-circle fs-2">
                                                <i class="ri-user-line"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="fs-15 mb-1">{{ $selectedMembership->user->name ?? 'Unknown' }}</h5>
                                            <p class="text-muted mb-0">{{ $selectedMembership->user->email ?? '' }}</p>
                                            <p class="text-muted mb-0 small">{{ $selectedMembership->user->phone ?? '' }}</p>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-1">
                                                <span class="badge bg-primary-subtle text-primary badge-border">
                                                    {{ $selectedMembership->membershipTier->name ?? 'N/A' }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="badge bg-success-subtle text-success">
                                                    Since {{ $selectedMembership->started_at ? $selectedMembership->started_at->format('M Y') : 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Membership Info -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header bg-light-subtle border-bottom-0">
                                    <h6 class="card-title mb-0">Membership Info</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Status:</strong> <span class="text-uppercase">{{ $selectedMembership->status }}</span></p>
                                    <p class="mb-1"><strong>Start Date:</strong> {{ $selectedMembership->started_at ? $selectedMembership->started_at->format('d M, Y') : '-' }}</p>
                                    <p class="mb-1"><strong>Expiry Date:</strong> {{ $selectedMembership->expires_at ? $selectedMembership->expires_at->format('d M, Y') : 'Lifetime' }}</p>
                                    <p class="mb-0"><strong>Approved By:</strong> {{ $selectedMembership->approved_by ? 'Admin #' . $selectedMembership->approved_by : 'System' }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Application Data (If available) -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header bg-light-subtle border-bottom-0">
                                    <h6 class="card-title mb-0">Source Application</h6>
                                </div>
                                <div class="card-body">
                                    @if($selectedMembership->sourceApplication)
                                        <p class="mb-1"><strong>App ID:</strong> #APP-{{ $selectedMembership->sourceApplication->id }}</p>
                                        <p class="mb-1"><strong>Submitted:</strong> {{ $selectedMembership->sourceApplication->submitted_at ? $selectedMembership->sourceApplication->submitted_at->format('d M, Y') : '-' }}</p>
                                        <a href="{{ route('admin.membership.applications', ['search' => $selectedMembership->user->email]) }}" class="btn btn-sm btn-link px-0">View Full Application</a>
                                    @else
                                        <p class="text-muted">No source application linked.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                @if($selectedMembership && $selectedMembership->status == 'active')
                        <button type="button" class="btn btn-danger" wire:click="confirmDeactivate({{ $selectedMembership->id }})">Deactivate Member</button>
                @elseif($selectedMembership && $selectedMembership->status == 'cancelled')
                        <button type="button" class="btn btn-success" wire:click="confirmActivate({{ $selectedMembership->id }})">Activate Member</button>
                @endif
            </div>
        </div>
    </div>
</div>
