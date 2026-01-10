<div>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Membership Applications</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Membership</a></li>
                        <li class="breadcrumb-item active">Applications</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-12">
            
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

            <div class="card" id="applicationList">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search applicant...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-sm-auto ms-auto">
                            <div class="hstack gap-2">
                                <select class="form-select" wire:model.live="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-card mb-4">
                        <table class="table align-middle table-nowrap mb-0" id="tasksTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>ID</th>
                                    <th>Applicant</th>
                                    <th>Tier</th>
                                    <th>Status</th>
                                    <th>Submitted At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse ($applications as $app)
                                    <tr>
                                        <td><a href="#" class="fw-medium link-primary">#APP-{{ $app->id }}</a></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1"><a href="#" class="item-title link-body-emphasis">{{ $app->user->name ?? 'Unknown' }}</a></h5>
                                                    <p class="text-muted mb-0 small">{{ $app->user->email ?? '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $app->membershipTier->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if($app->status == 'approved')
                                                <span class="badge bg-success-subtle text-success text-uppercase">Approved</span>
                                            @elseif($app->status == 'rejected')
                                                <span class="badge bg-danger-subtle text-danger text-uppercase">Rejected</span>
                                            @elseif($app->status == 'submitted')
                                                <span class="badge bg-warning-subtle text-warning text-uppercase">Submitted</span>
                                            @elseif($app->status == 'draft')
                                                <span class="badge bg-light text-body text-uppercase">Draft</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $app->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $app->submitted_at ? $app->submitted_at->format('d M, Y H:i') : '-' }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#" role="button" id="dropdownMenuLink{{ $app->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-2-fill"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink{{ $app->id }}">
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="view({{ $app->id }})"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View</a></li>
                                                    @if(!in_array($app->status, ['approved', 'rejected']))
                                                        <li><a class="dropdown-item" href="#" wire:click.prevent="confirmApprove({{ $app->id }})"><i class="ri-check-double-fill align-bottom me-2 text-muted"></i> Approve</a></li>
                                                        <li><a class="dropdown-item" href="#" wire:click.prevent="confirmReject({{ $app->id }})"><i class="ri-close-circle-fill align-bottom me-2 text-muted"></i> Reject</a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="noresult">
                                                <div class="text-center">
                                                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                    <h5 class="mt-2">No applications found</h5>
                                                    <p class="text-muted mb-0">Try adjusting your search or filters.</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        {{ $applications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Approve Modal -->
    <div wire:ignore.self class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                   <p>Are you sure you want to approve this application?</p>
                   <p class="text-muted">This will grant <strong>{{ $selectedApplication->selectedTier->name ?? 'Membership' }}</strong> to {{ $selectedApplication->user->name ?? 'User' }}.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="approve" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="approve">Confirm Approval</span>
                        <span wire:loading wire:target="approve">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div wire:ignore.self class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                   <div class="mb-3">
                       <label class="form-label">Rejection Reason</label>
                       <textarea class="form-control" wire:model="rejectionReason" rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                       @error('rejectionReason') <span class="text-danger small">{{ $message }}</span> @enderror
                   </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="reject" wire:loading.attr="disabled">
                         <span wire:loading.remove wire:target="reject">Confirm Rejection</span>
                         <span wire:loading wire:target="reject">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-view-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('viewModal'));
                modal.show();
            });
            Livewire.on('open-approve-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('approveModal'));
                modal.show();
            });
            Livewire.on('open-reject-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
                modal.show();
            });
            Livewire.on('close-modals', () => {
                // Close all known modals
                var modals = document.querySelectorAll('.modal.show');
                modals.forEach(function(modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                });
            });
        });
    </script>
</div>
