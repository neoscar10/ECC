<!-- Concierge Ledger -->
<div class="col-12 col-xl-6">
    <div class="ecc-club-panel h-100">
        <div class="ecc-club-panel-header">
            <h2 class="ecc-section-title mb-0">
                <span class="material-symbols-outlined me-2">receipt_long</span>
                Concierge Ledger
            </h2>
        </div>

        <div class="ecc-table-head d-flex justify-content-between">
            <span>Request Details</span>
            <span>Status</span>
        </div>

        <div class="ecc-club-list">
            @forelse($concierge as $c)
                <div class="ecc-club-list-row">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                        <div class="ecc-list-icon">
                            <span class="material-symbols-outlined">{{ $c['icon'] ?? 'assignment' }}</span>
                        </div>

                        <div class="min-w-0">
                            <div class="ecc-list-title">{{ $c['title'] }}</div>
                            @if(!empty($c['meta']))
                                <div class="ecc-list-subtitle">{{ $c['meta'] }}</div>
                            @endif
                        </div>
                    </div>

                    @php
                        $st = strtolower($c['status'] ?? '');
                        $badgeClass = $st === 'completed' ? 'status-success' : ($st === 'processing' ? 'status-warning' : 'status-default');
                    @endphp
                    <span class="ecc-status-pill {{ $badgeClass }}">
                        {{ $c['status_label'] }}
                    </span>
                </div>
            @empty
                <div class="ecc-empty-inline p-4 text-center">
                    No concierge requests found.
                </div>
            @endforelse
        </div>

        @if($newConciergeRequestUrl)
            <div class="ecc-panel-footer">
                <a href="{{ $newConciergeRequestUrl }}" class="ecc-footer-link">
                    Request New Concierge Service
                </a>
            </div>
        @endif
    </div>
</div>
