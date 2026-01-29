<div class="card" style="min-height: 420px;">
    <div class="card-header align-items-center d-flex">
        <h4 class="card-title mb-0 flex-grow-1">Last 10 Bids</h4>
        <div class="flex-shrink-0">
            <button type="button" class="btn btn-soft-info btn-sm waves-effect waves-light"
                    wire:click="prepareAllBids"
                    data-bs-toggle="modal" data-bs-target="#bidsModal">
                View All
            </button>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-borderless table-nowrap table-sm align-middle mb-0">
                <thead class="table-light text-muted">
                    <tr>
                        <th>User</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lastBids as $bid)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="fs-13 mb-0">{{ $bid->user->name }}</h6>
                                        @if($bid->is_auto)
                                            <span class="badge bg-info-subtle text-info fs-10">Auto</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-end fw-medium">{{ $lot->currency }} {{ number_format($bid->amount) }}</td>
                            <td class="text-end text-muted fs-11" title="{{ $bid->placed_at }}">
                                {{ $bid->placed_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No bids placed yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
