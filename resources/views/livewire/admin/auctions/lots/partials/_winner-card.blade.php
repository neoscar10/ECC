@if($lot->status === 'ended' && $lot->winner_user_id)
    <div class="card bg-success-subtle border-success">
        <div class="card-header bg-transparent border-bottom border-success-subtle">
            <h5 class="card-title text-success mb-0">
                <i class="ri-trophy-line me-1"></i> Auction Winner
            </h5>
        </div>
        <div class="card-body">
            @php 
                $winner = $lot->winner; 
                $initials = strtoupper(substr($winner->first_name, 0, 1) . substr($winner->last_name, 0, 1));
            @endphp
            
            <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0">
                     <div class="avatar-sm rounded-circle bg-success text-white fs-16 d-flex align-items-center justify-content-center">
                        {{ $initials }}
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h5 class="fs-15 mb-1 text-dark">{{ $winner->name }}</h5>
                    <p class="text-muted mb-0 fs-13">{{ $winner->email }}</p>
                    @if($winner->currentMembership?->membershipTier)
                        <span class="badge bg-white text-success border border-success-subtle mt-1">
                            {{ $winner->currentMembership->membershipTier->name }}
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="bg-white bg-opacity-50 rounded p-3 mb-3 border border-success-subtle">
                <div class="row text-center">
                    <div class="col-6 border-end border-success-subtle">
                        <p class="text-muted mb-1 text-uppercase fs-11">Winning Bid</p>
                        <h5 class="fs-16 mb-0 text-success fw-bold">{{ $lot->currency }} {{ number_format($lot->current_highest_bid) }}</h5>
                    </div>
                    <div class="col-6">
                        <p class="text-muted mb-1 text-uppercase fs-11">Won At</p>
                        <h5 class="fs-14 mb-0 text-dark">{{ $lot->ends_at?->format('d M, h:i A') }}</h5>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-soft-success waves-effect waves-light btn-sm"
                        wire:click="openWinnerModal">
                    View Winner Details
                </button>
                
                @if($lot->order)
                    <div class="alert alert-success border-0 py-2 mb-0 d-flex align-items-center justify-content-center">
                        <i class="ri-checkbox-circle-line me-1"></i> Sale Recorded (#{{ $lot->order->order_number }})
                        {{-- Optional: <button class="btn btn-link p-0 ms-2 text-success">View</button> --}}
                    </div>
                @else
                    <button type="button" class="btn btn-success waves-effect waves-light btn-sm shadow-none"
                            wire:click="$dispatch('open-record-sale-modal', { lotId: {{ $lot->id }} })">
                        <i class="ri-shopping-cart-2-line align-bottom me-1"></i> Record Sale
                    </button>
                @endif
            </div>
        </div>
    </div>
@elseif($lot->status == 'unsold')
    <div class="card bg-warning-subtle border-warning mb-3">
        <div class="card-body text-center">
            <div class="avatar-sm mx-auto mb-3">
                <div class="avatar-title bg-warning-subtle text-warning fs-20 rounded-circle border border-warning">
                    <i class="ri-error-warning-line"></i>
                </div>
            </div>
            <h5 class="text-warning">Unsold</h5>
            <p class="text-muted mb-0">Reserve price was not met.</p>
        </div>
    </div>
@endif
