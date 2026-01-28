@php
    // expects: $hasBids, $highestBid, $bidCount, $docsCount
@endphp

<div class="card">
    <div class="card-body">
        <div class="text-center mb-4">
            <h6 class="text-uppercase text-muted mb-2">Highest Bid</h6>

            @if($hasBids)
                <h2 class="text-success mb-2">{{ $lot->currency }} {{ number_format($highestBid) }}</h2>
                
                @if(isset($highestBidder) && $highestBidder)
                    <div class="mt-4 mb-3 p-3 bg-light rounded border border-dashed">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-start">
                                <span class="badge bg-success-subtle text-success mb-1">Highest Bidder</span>
                                <h6 class="fs-14 fw-bold mb-0 text-dark">{{ $highestBidder->name }}</h6>
                                @if($highestBidder->email)
                                    <div class="text-muted fs-11 text-truncate" style="max-width: 150px;">{{ $highestBidder->email }}</div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-sm btn-subtle-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#highestBidderModal">
                                <i class="ri-eye-line align-middle me-1"></i> View
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mt-3 mb-3 text-muted fst-italic fs-12">
                        No bidder info available
                    </div>
                @endif
                
                <div class="text-muted fs-12 mb-3">Based on {{ $bidCount }} bid{{ $bidCount==1?'':'s' }}</div>
            @else
                <h2 class="text-muted mb-2">No bids yet</h2>
                <div class="text-muted fs-12">Starting price: {{ $lot->currency }} {{ number_format($lot->starting_price) }}</div>
            @endif

            @if($lot->status === 'live')
                <div class="mt-3">
                    <small class="text-muted text-uppercase fw-bold mb-2 d-block">Time Remaining</small>
                    <div class="d-inline-block">
                        <div class="badge bg-danger-subtle text-danger fs-15 py-2 px-4 shadow-sm" wire:ignore>
                            <div id="auctionCountdown" class="d-flex align-items-center justify-content-center" data-end-at="{{ $lot->ends_at?->toIso8601String() }}">
                                <i class="ri-time-line align-middle me-2"></i>
                                <span class="fw-bold">Calculating...</span>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-3">
                    @if($lot->status == 'unsold')
                        <span class="badge bg-warning-subtle text-warning text-uppercase fs-12">Unsold (Reserve Not Met)</span>
                    @else
                        <span class="badge bg-light text-dark text-uppercase">{{ ucfirst($lot->status) }}</span>
                    @endif
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
