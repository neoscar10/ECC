<div>
    <div class="card border-warning border-start border-start-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title text-warning mb-1"><i class="ri-alert-fill align-bottom me-1"></i> Decision Required</h5>
                    <p class="text-muted mb-0">This auction ended manually. Review the results and determine the outcome.</p>
                </div>
                <div class="d-flex gap-2">
                     <button class="btn btn-warning" wire:click="openDecisionModal">
                        <i class="ri-hammer-line align-bottom me-1"></i> Make Decision
                    </button>
                </div>
            </div>
            
            <div class="row mt-3 g-3">
                <div class="col-sm-6 col-md-6">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted fs-11 text-uppercase">Ended At</div>
                        <div class="fw-bold">{{ $lot->ended_at->format('d M, h:i A') }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted fs-11 text-uppercase">Highest Bid</div>
                        <div class="fw-bold text-primary">
                            @if($outcomeComparison['highest_bid_amount'])
                                {{ number_format($outcomeComparison['highest_bid_amount']) }} USD
                            @else
                                <span class="text-muted">No Bids</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted fs-11 text-uppercase">Reserve Price</div>
                        <div class="fw-bold">
                             @if($lot->min_selling_price)
                                {{ number_format($lot->min_selling_price) }} USD
                             @else
                                <span class="text-muted">None</span>
                             @endif
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="p-2 border rounded bg-light">
                        <div class="text-muted fs-11 text-uppercase">Recommendation</div>
                        <div>
                            @if($outcomeComparison['is_sold'])
                                <span class="badge bg-success-subtle text-success">SOLD</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">UNSOLD</span>
                                @if($outcomeComparison['reason'])
                                    <small class="d-block text-muted fs-10 mt-1">{{ $outcomeComparison['reason'] }}</small>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Decision Modal -->
    <div class="modal fade" id="decisionModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Finalize Auction Outcome</h5>
                    <button type="button" class="btn-close" wire:click="$set('showDecisionModal', false)" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 text-center mb-4">
                        <h6 class="mb-1">Proposed Outcome: 
                            @if($outcomeComparison['is_sold'])
                                <span class="text-success fw-bold">SOLD</span>
                            @else
                                <span class="text-danger fw-bold">UNSOLD</span>
                            @endif
                        </h6>
                        <div class="fs-12">{{ $outcomeComparison['reason'] }}</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <!-- Option A: Declare Winner -->
                        <button type="button" class="btn btn-outline-success d-flex justify-content-between align-items-center p-3 {{ !$outcomeComparison['winner_user_id'] ? 'opacity-50' : '' }}"
                            @if($outcomeComparison['winner_user_id']) wire:click="declareWinner" @else disabled title="No highest bidder found" @endif>
                            <span class="d-flex align-items-center gap-3">
                                <i class="ri-trophy-line fs-20"></i>
                                <span class="text-start">
                                    <span class="d-block fw-semibold">Declare Highest Bidder Winner</span>
                                    <span class="fs-11 text-muted">Finalizes auction as Sold</span>
                                </span>
                            </span>
                            <i class="ri-arrow-right-line"></i>
                        </button>

                        <!-- Option B: Mark Unsold -->
                        <button type="button" class="btn btn-outline-danger d-flex justify-content-between align-items-center p-3"
                            wire:click="$dispatch('confirm-mark-unsold')">
                            <span class="d-flex align-items-center gap-3">
                                <i class="ri-close-circle-line fs-20"></i>
                                <span class="text-start">
                                    <span class="d-block fw-semibold">Mark Unsold</span>
                                    <span class="fs-11 text-muted">Finalizes auction as Unsold</span>
                                </span>
                            </span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                        
                        <!-- Option C: Re-auction -->
                        <button type="button" class="btn btn-outline-secondary d-flex justify-content-between align-items-center p-3"
                            wire:click="reauction">
                            <span class="d-flex align-items-center gap-3">
                                <i class="ri-refresh-line fs-20"></i>
                                <span class="text-start">
                                    <span class="d-block fw-semibold">Re-auction this Lot</span>
                                    <span class="fs-11 text-muted">Creates a new copy to run later</span>
                                </span>
                            </span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('livewire:initialized', () => {
        var decisionModal = new bootstrap.Modal(document.getElementById('decisionModal'));
        Livewire.on('show-decision-modal', () => { decisionModal.show(); });
        Livewire.on('hide-decision-modal', () => { decisionModal.hide(); });

        Livewire.on('confirm-mark-unsold', () => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to mark this auction as UNSOLD. This cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonClass: 'btn btn-primary w-xs me-2 mt-2',
                cancelButtonClass: 'btn btn-danger w-xs mt-2',
                confirmButtonText: 'Yes, mark as Unsold!',
                buttonsStyling: false,
                showCloseButton: true
            }).then(function (result) {
                if (result.value) {
                    Livewire.dispatch('mark-unsold-confirmed');
                }
            });
        });
    });
    </script>
</div>
