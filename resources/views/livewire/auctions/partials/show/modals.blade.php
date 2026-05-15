<!-- Bid Confirmation Modal -->
<div
    class="modal fade @if($showBidConfirmModal) show @endif"
    tabindex="-1"
    @if($showBidConfirmModal)
        style="display:block; background: rgba(0,0,0,0.85);"
        aria-modal="true"
        role="dialog"
    @endif
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light" style="background: var(--luxe-surface); border: 1px solid rgba(199, 167, 90,.14); border-radius: 20px; box-shadow: 0 24px 60px rgba(0,0,0,.6);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--luxe-gold);">Confirm Bid</h5>
                <button type="button" class="btn-close btn-close-white" wire:click="closeBidConfirmModal"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-white mb-4">
                    Please confirm your bid before submitting.
                </p>

                <div class="p-3 mb-3" style="background: rgba(199, 167, 90,.04); border: 1px solid rgba(199, 167, 90,.2); border-radius: 16px;">
                    <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Current Highest Bid</div>
                    <div class="fs-4 fw-bold text-white">{{ $currentHighestBidDisplay }}</div>
                </div>

                <div class="p-3 bg-black" style="background: #000; border: 1px solid rgba(255,255,255,.12); border-radius: 16px;">
                    <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Your Bid</div>
                    <div class="fs-3 fw-black" style="color: var(--luxe-gold);">{{ $userBidDisplay }}</div>
                </div>

                <div class="small text-white mt-4 text-center opacity-75">
                    <i class="mdi mdi-information-outline me-1"></i> Bids are binding once submitted.
                </div>

                @error('bidAmount')
                    <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220,53,69,.1); border-color: rgba(220,53,69,.2); color: #ff6b6b; border-radius: 12px;">
                        <i class="mdi mdi-alert-circle-outline me-2"></i> {{ $message }}
                    </div>
                @enderror

                @if(!empty($bidErrorMessage))
                    <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220,53,69,.1); border-color: rgba(220,53,69,.2); color: #ff6b6b; border-radius: 12px;">
                        <i class="mdi mdi-alert-circle-outline me-2"></i> {{ $bidErrorMessage }}
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 p-4 pt-0 gap-2 flex-nowrap">
                <button type="button" class="btn w-50" style="border-radius: 14px; background: rgba(255,255,255,.05); color: #fff; font-weight: 700; min-height: 52px;" wire:click="closeBidConfirmModal">
                    Cancel
                </button>
                <button type="button" class="btn w-50" style="border-radius: 14px; background: var(--luxe-gold); color: #111; font-weight: 800; min-height: 52px;" wire:click="confirmBidSubmission" wire:loading.attr="disabled" wire:target="confirmBidSubmission">
                    <span wire:loading.remove wire:target="confirmBidSubmission">Place Bid</span>
                    <span wire:loading wire:target="confirmBidSubmission">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="border-color: #111; border-right-color: transparent;"></span>
                        Placing...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Auto Bid Configuration Modal -->
<div
    class="modal fade @if($showAutoBidModal) show @endif"
    tabindex="-1"
    @if($showAutoBidModal)
        style="display: block; background: rgba(0,0,0,0.85);"
        aria-modal="true"
        role="dialog"
    @endif
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light" style="background: var(--luxe-surface); border: 1px solid rgba(199, 167, 90,.14); border-radius: 20px; box-shadow: 0 24px 60px rgba(0,0,0,.6);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--luxe-gold);">
                    {{ $hasAutoBidConfigured ? 'Update Auto Bid' : 'Configure Auto Bid' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="closeAutoBidModal"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-white mb-4">
                    Set your maximum bid limit and increment amount.
                </p>

                <div class="p-3 mb-3" style="background: rgba(199, 167, 90,.03); border: 1px solid rgba(199, 167, 90,.1); border-radius: 16px;">
                    <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Current Highest Bid</div>
                    <div class="fs-4 fw-bold text-white">{{ $currentHighestBidDisplay }}</div>
                </div>

                <div class="p-3 mb-4" style="background: rgba(199, 167, 90,.03); border: 1px solid rgba(199, 167, 90,.1); border-radius: 16px;">
                    <div class="small text-uppercase mb-1" style="color: var(--luxe-text-soft); letter-spacing: .08em; font-weight: 800;">Minimum Increment</div>
                    <div class="fs-4 fw-bold text-white">{{ $minIncrementDisplay }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Increment Amount (₹)</label>
                    <input type="text" class="form-control" style="background: #000; border: 1px solid rgba(255,255,255,.15); color: #fff; padding: .8rem 1rem; border-radius: 12px;" wire:model.defer="autoBidIncrementAmount" placeholder="e.g. 10000">
                    @error('autoBidIncrementAmount') <div class="text-danger small mt-2"><i class="mdi mdi-alert-circle-outline"></i> {{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-bold">Maximum Bid Limit (₹)</label>
                    <input type="text" class="form-control" style="background: #000; border: 1px solid var(--luxe-gold); color: #fff; padding: .8rem 1rem; border-radius: 12px; box-shadow: 0 0 0 1px rgba(199, 167, 90,.2);" wire:model.defer="autoBidMaxAmount" placeholder="e.g. 500000">
                    @error('autoBidMaxAmount') <div class="text-danger small mt-2"><i class="mdi mdi-alert-circle-outline"></i> {{ $message }}</div> @enderror
                </div>

                @if(!empty($autoBidErrorMessage))
                    <div class="alert alert-danger mt-3 mb-0" style="background: rgba(220,53,69,.1); border-color: rgba(220,53,69,.2); color: #ff6b6b; border-radius: 12px;">
                        {{ $autoBidErrorMessage }}
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between align-items-center">
                <div>
                    @if($hasAutoBidConfigured)
                        <button type="button" class="btn btn-link text-danger text-decoration-none px-0 fw-bold" wire:click="confirmCancelAutoBidModal">
                            Cancel Auto Bid
                        </button>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn px-4" style="border-radius: 12px; background: rgba(255,255,255,.05); color: #fff; font-weight: 700;" wire:click="closeAutoBidModal">
                        Close
                    </button>
                    <button type="button" class="btn px-4" style="border-radius: 12px; background: var(--luxe-gold); color: #111; font-weight: 800;" wire:click="saveAutoBid" wire:loading.attr="disabled" wire:target="saveAutoBid">
                        <span wire:loading.remove wire:target="saveAutoBid">Save Auto Bid</span>
                        <span wire:loading wire:target="saveAutoBid">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="border-color: #111; border-right-color: transparent;"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Auto Bid Confirm Modal -->
<div
    class="modal fade @if($showCancelAutoBidModal) show @endif"
    tabindex="-1"
    @if($showCancelAutoBidModal)
        style="display: block; background: rgba(0,0,0,0.85);"
        aria-modal="true"
        role="dialog"
    @endif
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-light border-0 shadow-lg" style="background: var(--luxe-surface); border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Cancel Auto Bid</h5>
                <button type="button" class="btn-close btn-close-white" wire:click="closeCancelAutoBidModal"></button>
            </div>

            <div class="modal-body p-4">
                <p class="text-white mb-0" style="font-size: 1.05rem;">
                    Are you sure you want to cancel your auto bid for this lot? This action cannot be undone.
                </p>
                
                @if(!empty($autoBidErrorMessage))
                    <div class="alert alert-danger mt-4 mb-0" style="border-radius: 12px;">
                        {{ $autoBidErrorMessage }}
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 p-4 pt-0 gap-2 flex-nowrap">
                <button type="button" class="btn w-50" style="border-radius: 14px; background: rgba(255,255,255,.05); color: #fff; font-weight: 700; min-height: 52px;" wire:click="closeCancelAutoBidModal">
                    Keep Auto Bid
                </button>
                <button type="button" class="btn w-50 btn-danger text-white fw-bold" style="border-radius: 14px; min-height: 52px;" wire:click="cancelAutoBid" wire:loading.attr="disabled" wire:target="cancelAutoBid">
                    <span wire:loading.remove wire:target="cancelAutoBid">Cancel Auto Bid</span>
                    <span wire:loading wire:target="cancelAutoBid">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Cancelling...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Premium Access Upgrade Modal --}}
@include('components.shared.premium-access-modal')
