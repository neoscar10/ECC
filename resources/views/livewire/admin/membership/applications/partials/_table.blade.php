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
