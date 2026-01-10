<div>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Members List</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Membership</a></li>
                        <li class="breadcrumb-item active">Members</li>
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

            <div class="card" id="membersList">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-3">
                            <div class="search-box">
                                <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search members...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-sm-auto ms-auto">
                            <div class="hstack gap-2">
                                <select class="form-select" wire:model.live="tierFilter">
                                    <option value="">All Tiers</option>
                                    @foreach($tiers as $tier)
                                        <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                    @endforeach
                                </select>
                                <select class="form-select" wire:model.live="statusFilter">
                                    <option value="active">Active Members</option>
                                    <option value="cancelled">Deactivated</option>
                                    <option value="expired">Expired</option>
                                    <option value="">All Statuses</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-card mb-4">
                        <table class="table align-middle table-nowrap mb-0" id="membersTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Member</th>
                                    <th>Tier</th>
                                    <th>Status</th>
                                    <th>Joined Date</th>
                                    <th>Expiry Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @forelse ($members as $member)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs flex-shrink-0 me-2">
                                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-13">
                                                        {{ substr($member->user->name ?? 'U', 0, 1) }}
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1"><a href="#" class="item-title link-body-emphasis">{{ $member->user->name ?? 'Unknown' }}</a></h5>
                                                    <p class="text-muted mb-0 small">{{ $member->user->email ?? '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">{{ $member->membershipTier->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if($member->status == 'active')
                                                <span class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                            @elseif($member->status == 'cancelled')
                                                <span class="badge bg-danger-subtle text-danger text-uppercase">Deactivated</span>
                                            @elseif($member->status == 'expired')
                                                <span class="badge bg-warning-subtle text-warning text-uppercase">Expired</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $member->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $member->started_at ? $member->started_at->format('d M, Y') : '-' }}</td>
                                        <td>{{ $member->expires_at ? $member->expires_at->format('d M, Y') : 'Lifetime' }}</td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#" role="button" id="dropdownMenuLink{{ $member->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-2-fill"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink{{ $member->id }}">
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="view({{ $member->id }})"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Details</a></li>
                                                    
                                                    @if($member->status == 'active')
                                                        <li><a class="dropdown-item text-danger" href="#" wire:click.prevent="confirmDeactivate({{ $member->id }})"><i class="ri-close-circle-fill align-bottom me-2"></i> Deactivate</a></li>
                                                    @elseif($member->status == 'cancelled')
                                                        <li><a class="dropdown-item text-success" href="#" wire:click.prevent="confirmActivate({{ $member->id }})"><i class="ri-check-double-fill align-bottom me-2"></i> Activate</a></li>
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
                                                    <h5 class="mt-2">No members found</h5>
                                                    <p class="text-muted mb-0">Try adjusting your filters.</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        {{ $members->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div wire:ignore.self class="modal fade zoomIn" id="viewMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
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

    <!-- Deactivate Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="deactivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deactivation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Are you sure?</h4>
                            <p class="text-muted mx-4 mb-0">This will deactivate the member account. They will lose access to member privileges.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deactivate" wire:loading.attr="disabled">
                        Yes, Deactivate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Activate Confirmation Modal -->
    <div wire:ignore.self class="modal fade" id="activateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Activation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                     <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Are you sure?</h4>
                            <p class="text-muted mx-4 mb-0">This will re-activate the member account.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="activate" wire:loading.attr="disabled">
                        Yes, Activate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-view-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
                modal.show();
            });
            Livewire.on('open-deactivate-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('deactivateModal'));
                modal.show();
            });
             Livewire.on('open-activate-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('activateModal'));
                modal.show();
            });
            Livewire.on('close-modals', () => {
                var modals = document.querySelectorAll('.modal.show');
                modals.forEach(function(modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                });
            });
        });
    </script>
</div>
