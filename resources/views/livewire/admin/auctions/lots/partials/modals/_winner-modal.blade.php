<div wire:ignore.self class="modal fade" id="winnerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Winner Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($lot->winner)
                    <div class="text-center mb-4">
                        @php 
                            $winner = $lot->winner;
                            $initials = strtoupper(substr($winner->first_name ?? '', 0, 1) . substr($winner->last_name ?? '', 0, 1));
                        @endphp
                        <div class="avatar-md mx-auto mb-3">
                            <div class="avatar-title bg-primary-subtle text-primary fs-24 rounded-circle">
                                {{ $initials ?: 'U' }}
                            </div>
                        </div>
                        <h5 class="fs-16 mb-1">{{ $winner->name }}</h5>
                        <p class="text-muted mb-0">User ID: #{{ $winner->id }}</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <th class="ps-0" scope="row">Email :</th>
                                    <td class="text-muted">{{ $winner->email }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Phone :</th>
                                    <td class="text-muted">{{ $winner->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Membership :</th>
                                    <td class="text-muted">
                                        @if($winner->currentMembership?->membershipTier)
                                            <span class="badge bg-info-subtle text-info">{{ $winner->currentMembership->membershipTier->name }}</span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Join Date :</th>
                                    <td class="text-muted">{{ $winner->created_at->format('d M, Y') }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0" scope="row">Status :</th>
                                    <td class="text-muted">
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="closeWinnerModal">Done</button>
            </div>
        </div>
    </div>
</div>
