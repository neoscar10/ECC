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
                                    <li><a class="dropdown-item" href="#" wire:click.prevent="openUpdateTierModal({{ $member->id }})"><i class="mdi mdi-swap-vertical align-bottom me-2 text-muted"></i> Update Tier</a></li>
                                    
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
