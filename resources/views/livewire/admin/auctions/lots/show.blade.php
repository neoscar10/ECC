<div>
    @include('livewire.admin.auctions.lots.partials._page-header', ['lot' => $lot])

    <div id="page-alerts" wire:ignore></div>

    <div class="row">
        <div class="col-lg-8">
            @include('livewire.admin.auctions.lots.partials._hero-gallery', ['lot' => $lot])
            @include('livewire.admin.auctions.lots.partials._details-card', ['lot' => $lot, 'accessSummary' => $this->accessSummary, 'timelineEvents' => $timelineEvents])
            @include('livewire.admin.auctions.lots.partials._timeline-card', ['timelineEvents' => $timelineEvents])
        </div>

        <div class="col-lg-4">
            {{-- Winner / Unsold Card --}}
            @include('livewire.admin.auctions.lots.partials._winner-card', ['lot' => $lot])
            
            {{-- Poll only if LIVE --}}
            <div @if($lot->status === 'live') wire:poll.30s="refreshPanels" @endif>
                @include('livewire.admin.auctions.lots.partials._summary-card', [
                    'lot' => $lot,
                    'bidCount' => $bidCount,
                    'docsCount' => $docsCount,
                    'highestBid' => $highestBid,
                    'hasBids' => $hasBids
                ])
                @include('livewire.admin.auctions.lots.partials._bids-card', [
                    'lot' => $lot,
                    'lastBids' => $lastBids
                ])
            </div>
        </div>
    </div>

    @include('livewire.admin.auctions.lots.partials.modals._extend-modal')
    @include('livewire.admin.auctions.lots.partials.modals._bids-modal', ['lot' => $lot, 'allBids' => $allBids])
    @include('livewire.admin.auctions.lots.partials.modals._winner-modal', ['lot' => $lot])

    {{-- reusable edit modal --}}
    <livewire:admin.auctions.lots.lot-form-modal :key="'auction-lot-edit-modal'" />
    <livewire:admin.auctions.orders.record-sale-modal :key="'record-sale-modal'" />

    @include('livewire.admin.auctions.lots.partials._scripts', ['lot' => $lot])

    <script>
        document.addEventListener('livewire:initialized', () => {
             Livewire.on('confirm-cancel-auction', () => {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to cancel this auction!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonClass: 'btn btn-primary w-xs me-2 mt-2',
                    cancelButtonClass: 'btn btn-danger w-xs mt-2',
                    confirmButtonText: 'Yes, cancel it!',
                    buttonsStyling: false,
                    showCloseButton: true
                }).then(function (result) {
                    if (result.value) {
                        Livewire.dispatch('cancel-auction-confirmed');
                    }
                });
             });
        });
    </script>
</div>
