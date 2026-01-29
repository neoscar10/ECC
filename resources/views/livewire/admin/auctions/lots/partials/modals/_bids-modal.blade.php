<div wire:ignore.self class="modal fade" id="bidsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bid History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetBidsModalState"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Placed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allBids as $bid)
                                <tr>
                                    <td>
                                        {{ $bid->user->name }}
                                        <br>
                                        <small class="text-muted">{{ $bid->user->email }}</small>
                                    </td>
                                    <td class="fw-bold fs-15">{{ $lot->currency }} {{ number_format($bid->amount) }}</td>
                                    <td>
                                        @if($bid->is_auto)
                                            <span class="badge bg-secondary">Auto</span>
                                        @else
                                            <span class="badge bg-light text-dark">Manual</span>
                                        @endif
                                    </td>
                                    <td>{{ $bid->placed_at->format('d M Y, H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No bids found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="resetBidsModalState">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
