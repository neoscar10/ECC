<div class="col-lg-4">
    <div class="auction-sticky-col">
        {{-- MAIN BIDDING CARD --}}
        <div class="auction-bid-card overflow-hidden mb-4">
            <div class="auction-bid-head p-4 p-lg-5">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                    <div>
                        <div class="auction-kicker mb-2">Current Bid</div>
                        <div class="auction-highest-bid">{{ $highestBid }}</div>
                    </div>

                    @if($lotPrepared->is_effectively_live ?? false)
                        <div class="auction-live-chip">
                            <span class="auction-live-chip-dot"></span>
                            <span>Live</span>
                        </div>
                    @endif
                </div>

                <div class="auction-bid-meta">
                    <div class="d-inline-flex align-items-center gap-2">
                        <i class="mdi mdi-timer-outline"></i>
                        <span><strong id="ecc-countdown-display" data-ends-at="{{ !empty($lotPrepared->ends_at_iso) ? $lotPrepared->ends_at_iso : '' }}">{{ $lotPrepared->time_remaining_display ?? '' }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="p-4 p-lg-5">
                @if($lotPrepared->is_early_access_active ?? false)
                    <div class="mb-4">
                        <span class="badge border-0 py-2 px-3 d-flex align-items-center gap-2" style="background: #e31837; color: white; border-radius: 10px; width: fit-content; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="mdi mdi-clock-fast fs-6"></i> Early Access Live
                        </span>
                    </div>
                @endif

                {{-- AUTO BID --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                        <div class="auction-section-label">
                            <i class="mdi mdi-robot-outline" style="color: var(--ecc-primary);"></i>
                            <span>Auto-Bid Settings</span>
                        </div>
                        <div class="form-check form-switch m-0">
                            <div style="font-size: .8rem; font-weight: 800; color: {{ $hasAutoBidConfigured ? 'var(--ecc-primary)' : 'var(--ecc-text-secondary)'}};">
                                {{ $hasAutoBidConfigured ? 'ON' : 'OFF' }}
                            </div>
                        </div>
                    </div>

                    <button type="button" class="auction-place-bid-btn w-100" style="min-height: 48px; font-size: 0.82rem; background: var(--ecc-primary-soft); border: 1px solid rgba(199, 167, 90,.2); color: var(--ecc-primary); box-shadow: none;" wire:click="openAutoBidModal" @if(empty($canAutoBid)) disabled @endif>
                        <i class="mdi mdi-flash me-2"></i>
                        {{ $hasAutoBidConfigured ? 'Update Auto Bid' : 'Set Auto Bid Limit' }}
                    </button>
                </div>

                {{-- PLACE BID --}}
                <div>
                    <div class="auction-section-label mb-3">
                        <i class="mdi mdi-cash-fast" style="color: var(--ecc-primary);"></i>
                        <span>Place New Bid</span>
                    </div>

                    {{-- QUICK INCREMENT BUTTONS --}}
                    @if(!empty($increments) && count($increments))
                        <div class="auction-quick-bids mb-4">
                            @foreach($increments as $increment)
                                @php
                                    $label = is_array($increment) ? ($increment['label'] ?? '') : ($increment->label ?? '');
                                    $isRecommended = (bool) (is_array($increment) ? ($increment['recommended'] ?? false) : ($increment->recommended ?? false));
                                @endphp
                                <button
                                    type="button"
                                    class="auction-quick-bid-btn {{ $isRecommended ? 'active' : '' }}"
                                    @if(!empty($label)) wire:click="applyIncrement('{{ $label }}')" @endif
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- BID FORM --}}
                    <div class="auction-bid-input-wrap mb-3">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="0"
                            wire:model.defer="bidAmount"
                        >
                        <span class="auction-bid-currency">{{ $currency }}</span>
                    </div>
                    
                    @error('bidAmount')
                        <div class="text-danger small mt-2 fw-medium mb-3 px-2">{{ $message }}</div>
                    @enderror

                    @php
                        $canBidDirectly = !empty($lotPrepared->can_bid);
                        $hasUpgradeAction = !empty($lotPrepared->access_actions);
                        $isLive = ($lotPrepared->is_effectively_live ?? false);
                        $isPast = in_array($lot->status, ['past', 'closed', 'ended']);
                        
                        // We only truly disable if it's past/closed. 
                        // If it's upcoming, we allow clicking if there's an upgrade path to trigger the modal.
                        $isButtonDisabled = $isPast || (!$isLive && !$hasUpgradeAction);
                    @endphp

                    <button type="button" class="auction-place-bid-btn mb-3" wire:click="reviewBid" @if($isButtonDisabled) disabled @endif>
                        <i class="mdi mdi-gavel me-2"></i>
                        @if($isPast)
                            Bidding Closed
                        @elseif(!$isLive && $hasUpgradeAction)
                            Unlock to Bid
                        @elseif(!$isLive)
                            Coming Soon
                        @else
                            Review Bid
                        @endif
                    </button>

                    @if(!$canBidDirectly && !empty($lotPrepared->access_message))
                        <div class="text-center mb-3 px-2">
                            <span class="small fw-bold" style="color: var(--ecc-text-secondary); font-size: 0.72rem; letter-spacing: 0.02em; line-height: 1.4;">
                                <i class="mdi mdi-information-outline me-1" style="color: var(--ecc-primary);"></i>
                                {{ $lotPrepared->access_message }}
                            </span>
                        </div>
                    @endif

                    <div class="auction-micro-copy text-center">
                        Highest bidder must pay within 24h.
                        <a href="" class="text-decoration-underline" style="color: var(--ecc-primary);">Terms apply</a>.
                    </div>
                </div>
            </div>
        </div>

        {{-- BID HISTORY --}}
        <div class="auction-history-card overflow-hidden mb-4" x-data="{ showAllBids: false }">
            <div class="auction-history-head px-4 px-lg-5 py-4 d-flex align-items-center justify-content-between gap-3">
                <div class="fw-black text-uppercase ecc-text-primary" style="letter-spacing: .08em; font-size: .84rem;">Bid History</div>
                <div class="small fw-bold text-uppercase" style="color: var(--ecc-text-muted); letter-spacing: .06em;">
                    {{ $bidHistory->count() }} Total Bids
                </div>
            </div>

            <div>
                @forelse($bidHistory as $index => $entry)
                    @php
                        $bidderLabel = $entry['bidder_label'] ?? $entry->bidder_label ?? 'User';
                        $amount = $entry['amount_display'] ?? $entry->amount_display ?? (isset($entry['amount']) ? '₹' . number_format((float) $entry['amount']) : '₹0');
                        $timeAgo = $entry['time_human'] ?? $entry->time_human ?? '--';
                        $isHighest = $entry['is_highest_bid'] ?? false;
                    @endphp

                    @if($index === 6)
                        <div x-show="showAllBids" style="display: none;">
                    @endif

                    <div class="auction-bid-row px-4 px-lg-5 py-3 d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <div class="auction-bid-row-index {{ $isHighest ? 'text-success' : '' }}">#{{ $bidHistory->count() - $index }}</div>
                            <div class="text-truncate {{ $index > 0 ? 'auction-bid-row-muted' : '' }}">
                                {{ $bidderLabel }}
                            </div>
                        </div>

                        <div class="text-end {{ $index > 0 ? 'auction-bid-row-muted' : '' }}">
                            <div class="fw-bold {{ $isHighest ? 'text-success' : 'ecc-text-primary' }}">{{ $amount }}</div>
                            <div class="small" style="color: var(--ecc-text-muted);">{{ $timeAgo }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 px-lg-5" style="color: var(--ecc-text-secondary);">
                        No bids yet.
                    </div>
                @endforelse

                @if($bidHistory->count() > 6)
                    </div>
                @endif
            </div>

            @if($bidHistory->count() > 6)
                <div class="px-4 px-lg-5 py-3 text-center" style="background: var(--ecc-bg-input); border-top: 1px solid rgba(199, 167, 90,.06);">
                    <a href="javascript:void(0)" @click.prevent="showAllBids = !showAllBids" class="btn btn-link p-0 text-decoration-none fw-black text-uppercase" style="letter-spacing: .08em; color: var(--ecc-primary); font-size: .72rem;">
                        <span x-text="showAllBids ? 'Show Less' : 'See All Bids'"></span>
                        <i class="mdi ms-1" :class="showAllBids ? 'mdi-chevron-up' : 'mdi-chevron-down'"></i>
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
