<!-- View Modal -->
<div wire:ignore.self class="modal fade zoomIn" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @if($selectedApplication)
                    <div class="row">
                        <!-- Applicant Summary -->
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
                                            <h5 class="fs-15 mb-1">{{ $selectedApplication->user->name ?? 'Unknown Applicant' }}</h5>
                                            <p class="text-muted mb-0">{{ $selectedApplication->user->email ?? '' }}</p>
                                        </div>
                                        <div class="text-end">
                                            <div class="mb-1">
                                                <span class="badge bg-primary-subtle text-primary badge-border">
                                                    Requested: {{ $selectedApplication->membershipTier->name ?? 'N/A' }}
                                                </span>
                                            </div>
                                            <div>
                                                @if($selectedApplication->status == 'approved')
                                                    <span class="badge bg-success-subtle text-success text-uppercase">Approved</span>
                                                @elseif($selectedApplication->status == 'rejected')
                                                    <span class="badge bg-danger-subtle text-danger text-uppercase">Rejected</span>
                                                @elseif($selectedApplication->status == 'submitted')
                                                    <span class="badge bg-warning-subtle text-warning text-uppercase">Submitted</span>
                                                @elseif($selectedApplication->status == 'draft')
                                                    <span class="badge bg-light text-body text-uppercase">Draft</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $selectedApplication->status }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Personal Details -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header bg-light-subtle border-bottom-0">
                                    <h6 class="card-title mb-0"><i class="ri-file-user-line align-middle me-1"></i> Personal Details</h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($selectedApplication->personal_details_json) && is_array($selectedApplication->personal_details_json))
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tbody>
                                                    @foreach($selectedApplication->personal_details_json as $key => $value)
                                                        <tr>
                                                            <th class="ps-0" scope="row" width="40%">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                            <td class="text-muted text-break">
                                                                @if(is_array($value))
                                                                    @foreach($value as $v)
                                                                        <span class="badge bg-light text-body">{{ $v }}</span>
                                                                    @endforeach
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No personal details provided.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Cricket Profile -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header bg-light-subtle border-bottom-0">
                                    <h6 class="card-title mb-0"><i class="ri-trophy-line align-middle me-1"></i> Cricket Profile</h6>
                                </div>
                                <div class="card-body">
                                    @if(!empty($selectedApplication->cricket_profile_json) && is_array($selectedApplication->cricket_profile_json))
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tbody>
                                                    @foreach($selectedApplication->cricket_profile_json as $key => $value)
                                                        <tr>
                                                            <th class="ps-0" scope="row" width="40%">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                            <td class="text-muted text-break">
                                                                @if(is_array($value))
                                                                        <div class="d-flex flex-wrap gap-1">
                                                                        @foreach($value as $v)
                                                                            <span class="badge bg-info-subtle text-info">{{ ucwords(str_replace('_', ' ', $v)) }}</span>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No cricket profile provided.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Metadata/Timestamps -->
                            <div class="col-12 mt-3 text-muted text-center small">
                            <p class="mb-0">
                                Application submitted on {{ $selectedApplication->submitted_at ? $selectedApplication->submitted_at->format('d M, Y \a\t H:i') : 'N/A' }}
                                @if($selectedApplication->reviewed_at)
                                    <br>Processed by Admin on {{ $selectedApplication->reviewed_at->format('d M, Y') }}
                                @endif
                            </p>
                        </div>

                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                @if($selectedApplication && !in_array($selectedApplication->status, ['approved', 'rejected']))
                        <button type="button" class="btn btn-danger" wire:click="confirmReject({{ $selectedApplication->id }})">Reject</button>
                        <button type="button" class="btn btn-success" wire:click="confirmApprove({{ $selectedApplication->id }})">Approve</button>
                @endif
            </div>
        </div>
    </div>
</div>
