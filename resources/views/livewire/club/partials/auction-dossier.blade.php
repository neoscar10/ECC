<!-- Auction Dossier -->
<div class="col-12 col-xl-6">
    <div class="ecc-club-panel h-100">
        <div class="ecc-club-panel-header">
            <h2 class="ecc-section-title mb-0">
                <span class="material-symbols-outlined me-2">inventory_2</span>
                Auction Dossier
            </h2>
        </div>

        <div class="ecc-table-head d-flex justify-content-between">
            <span>Item / Lot</span>
            <span>Result</span>
        </div>

        <div class="ecc-club-list">
            @forelse($dossier as $item)
                <div class="ecc-club-list-row">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <div class="ecc-dossier-thumb">
                            <img src="{{ $item['thumb_url'] }}"
                                 alt="{{ $item['title'] }}"
                                 class="w-100 h-100 object-fit-cover">
                        </div>

                        <div class="min-w-0">
                            <div class="ecc-list-title">{{ $item['title'] }}</div>
                            @if(!empty($item['meta']))
                                <div class="ecc-list-subtitle">{{ $item['meta'] }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="text-end">
                        @php
                            $badge = strtolower($item['badge'] ?? '');
                            $badgeClass = $badge === 'won' ? 'status-success' : ($badge === 'outbid' ? 'status-danger' : 'status-default');
                        @endphp
                        <span class="ecc-status-pill {{ $badgeClass }}">
                            {{ $item['badge_label'] }}
                        </span>

                        @if(!empty($item['substatus_label']))
                            <div class="ecc-list-amount mt-1">{{ $item['substatus_label'] }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="ecc-empty-inline p-4 text-center">
                    No auction dossier entries found.
                </div>
            @endforelse
        </div>

        @if($auctionHistoryUrl)
            <div class="ecc-panel-footer">
                <a href="{{ $auctionHistoryUrl }}" class="ecc-footer-link ecc-footer-link-muted">
                    View Auction History
                </a>
            </div>
        @endif
    </div>
</div>
