@php
    // expects: $hasBids, $highestBid, $bidCount, $docsCount
@endphp

<div class="card">
    <div class="card-body">
        <div class="text-center mb-4">
            <h6 class="text-uppercase text-muted mb-2">Highest Bid</h6>

            @if($hasBids)
                <h2 class="text-success mb-2">{{ $lot->currency }} {{ number_format($highestBid) }}</h2>
                <div class="text-muted fs-12">Based on {{ $bidCount }} bid{{ $bidCount==1?'':'s' }}</div>
            @else
                <h2 class="text-muted mb-2">No bids yet</h2>
                <div class="text-muted fs-12">Starting price: {{ $lot->currency }} {{ number_format($lot->starting_price) }}</div>
            @endif

            @if($lot->status === 'live')
                <div class="mt-3">
                    <h5 class="text-danger mb-0" id="countdown_timer" data-ends-at="{{ $lot->ends_at?->toIso8601String() }}">--:--:--</h5>
                    <small class="text-muted">Time Remaining</small>
                </div>
            @else
                <div class="mt-3">
                    <span class="badge bg-light text-dark text-uppercase">{{ ucfirst($lot->status) }}</span>
                </div>
            @endif
        </div>

        <div class="d-flex justify-content-between border-top border-bottom py-3 mb-4">
            <div class="text-center">
                <h5 class="fs-15 mb-0">{{ $bidCount }}</h5>
                <p class="text-muted mb-0">Bids</p>
            </div>
            <div class="text-center">
                <h5 class="fs-15 mb-0">{{ $lot->images->count() }}</h5>
                <p class="text-muted mb-0">Images</p>
            </div>
            <div class="text-center">
                <h5 class="fs-15 mb-0">{{ $docsCount }}</h5>
                <p class="text-muted mb-0">Docs</p>
            </div>
        </div>

        <div class="d-grid gap-2">
            <a href="{{ route('admin.auctions.lots.index') }}" class="btn btn-soft-dark waves-effect waves-light">
                <i class="ri-arrow-left-line me-1"></i> Back to Lots
            </a>

            {{-- Must open the SAME edit modal used on index --}}
            <button type="button" class="btn btn-soft-primary waves-effect waves-light" wire:click="requestEdit">
                <i class="ri-pencil-line me-1"></i> Edit
            </button>

            @if($lot->status === 'live')
                <button type="button" class="btn btn-soft-warning waves-effect waves-light"
                        wire:click="prepareExtend"
                        data-bs-toggle="modal" data-bs-target="#extendModal">
                    <i class="ri-time-line me-1"></i> Extend
                </button>

                <button type="button" class="btn btn-soft-danger waves-effect waves-light" wire:click="confirmCancel">
                    <i class="ri-close-circle-line me-1"></i> Cancel
                </button>
            @endif
        </div>
    </div>
</div>
