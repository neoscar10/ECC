<div>
    @include('livewire.admin.auctions.lots.partials._page-header', ['lot' => $lot])

    @include('livewire.admin.partials._alerts')
    
    @if ($successMessage)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ $successMessage }}
            <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    <div id="page-alerts" wire:ignore></div>

    <div class="row">
        <div class="col-lg-8">
            @include('livewire.admin.auctions.lots.partials._hero-gallery', ['lot' => $lot])
            
          

            @include('livewire.admin.auctions.lots.partials._details-card', ['lot' => $lot, 'accessSummary' => $this->accessSummary, 'timelineEvents' => $timelineEvents])
            @include('livewire.admin.auctions.lots.partials._timeline-card', ['timelineEvents' => $timelineEvents])
        </div>

        <div class="col-lg-4">
            <!-- Decision Manager -->
            @if($lot->status === 'pending_decision' && $lot->outcome_decision_mode === 'admin')
                <div class="mb-3">
                    <livewire:admin.auctions.lots.decision-manager :lot="$lot" :key="'decision-manager-'.$lot->id" />
                </div>
            @endif
            
            {{-- Winner / Unsold Card --}}
            @include('livewire.admin.auctions.lots.partials._winner-card', ['lot' => $lot])
            
            {{-- Poll only if LIVE --}}
            <div @if($lot->status === 'live') wire:poll.30s="refreshPanels" @endif>
                @include('livewire.admin.auctions.lots.partials._summary-card', [
                    'lot' => $lot,
                    'bidCount' => $bidCount,
                    'docsCount' => $docsCount,
                    'highestBid' => $highestBid,
                    'hasBids' => $hasBids,
                    'highestBidder' => $highestBidder
                ])
                @include('livewire.admin.auctions.lots.partials._bids-card', [
                    'lot' => $lot,
                    'lastBids' => $lastBids
                ])

                {{-- Auto-Bids Card --}}
                <div class="card" style="min-height: 200px;">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">Auto-Bids</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-nowrap table-sm align-middle mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th>User</th>
                                        <th class="text-end">Max</th>
                                        <th class="text-end">Inc</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end">Set At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($autoBids as $autoBid)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-13 mb-0">{{ $autoBid->user ? $autoBid->user->name : 'Unknown User' }}</h6>
                                                        @if($autoBid->user && $autoBid->user->email)
                                                            <span class="text-muted fs-11">{{ $autoBid->user->email }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-medium">{{ $lot->currency }} {{ number_format($autoBid->max_bid) }}</td>
                                            <td class="text-end text-muted">{{ $lot->currency }} {{ number_format($autoBid->increment_amount) }}</td>
                                            <td class="text-center">
                                                @if($autoBid->is_enabled)
                                                    <span class="badge bg-success-subtle text-success">Enabled</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Disabled</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-muted fs-11">
                                                {{ $autoBid->created_at->format('d M, H:i') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No auto-bids configured for this lot yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.admin.auctions.lots.partials.modals._extend-modal')
    @include('livewire.admin.auctions.lots.partials.modals._bids-modal', ['lot' => $lot, 'allBids' => $allBids])
    @include('livewire.admin.auctions.lots.partials.modals._winner-modal', ['lot' => $lot])

    <!-- Highest Bidder Modal -->
    <div class="modal fade" id="highestBidderModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 overflow-hidden">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title">Highest Bidder Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @if($highestBidder)
                        {{-- Profile Header --}}
                        <div class="text-center mb-4">
                            <div class="avatar-lg mx-auto mb-3">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24 fw-bold">
                                    {{ strtoupper(substr($highestBidder->name, 0, 1)) }}
                                </div>
                            </div>
                            <h5 class="mb-1">{{ $highestBidder->name }}</h5>
                            <p class="text-muted mb-0">{{ $highestBidder->email }}</p>
                        </div>

                        {{-- Details Grid --}}
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <p class="text-uppercase fw-medium text-muted fs-11 mb-2">Phone</p>
                                    <h6 class="mb-0">{{ $highestBidder->phone ?? 'N/A' }}</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <p class="text-uppercase fw-medium text-muted fs-11 mb-2">Membership</p>
                                    @if($highestBidder->currentMembership && $highestBidder->currentMembership->membershipTier)
                                        <span class="badge bg-primary-subtle text-primary">{{ $highestBidder->currentMembership->membershipTier->name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <p class="text-uppercase fw-medium text-muted fs-11 mb-2">Joined Date</p>
                                    <h6 class="mb-0">{{ $highestBidder->created_at->format('d M Y') }}</h6>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 border rounded bg-light-subtle h-100">
                                    <p class="text-uppercase fw-medium text-muted fs-11 mb-2">Status</p>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="avatar-lg mx-auto mb-3">
                                <div class="avatar-title bg-light text-muted rounded-circle fs-24">
                                    <i class="ri-user-unfollow-line"></i>
                                </div>
                            </div>
                            <h5 class="text-muted">No Bidder Information</h5>
                        </div>
                    @endif
                </div>
                <div class="modal-footer p-3 bg-light-subtle">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Close</button>
                    <!-- Check route existence for functionality: route('admin.users.show', $highestBidder->id) -->
                </div>
            </div>
        </div>
    </div>

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
