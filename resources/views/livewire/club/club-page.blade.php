@php
  $h = $vm['header'] ?? [];
  $priv = $vm['privileges'] ?? [];
  $concierge = $vm['concierge'] ?? [];
  $dossier = $vm['auction_dossier'] ?? [];
  $urls = $vm['urls'] ?? [];
  
  $memberName = $h['member_name'] ?? 'Member';
  $memberAvatarUrl = $h['avatar_url'] ?? '';
  $memberTierName = $h['tier_name'] ?? 'Membership Tier';
  $memberIdLabel = $h['member_id'] ?? null;
  $memberSinceLabel = $h['member_since'] ?? null;
  $isVerified = $h['is_verified'] ?? false;
  
  $contactConciergeUrl = $urls['contact_concierge'] ?? '#';
  $benefitsUrl = $urls['privileges_all'] ?? null;
  $newConciergeRequestUrl = $urls['new_concierge'] ?? null;
  $auctionHistoryUrl = $urls['auction_history'] ?? null;
  
  $tierHeadline = $vm['tier_headline'] ?? null;
  $tierQuote = $vm['tier_quote'] ?? null;
  $clubStats = $vm['club_stats'] ?? [];
@endphp

<div class="ecc-club-page">
    @if (session()->has('concierge_success'))
        <div class="alert ecc-alert-success mb-4 d-flex align-items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <div>{{ session('concierge_success') }}</div>
        </div>
    @endif

    <div class="d-flex flex-column gap-4 gap-xl-5">

        <!-- Member Profile Hero -->
        <section class="ecc-club-hero-card">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-lg">
                    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
                        <div class="ecc-club-avatar-wrap position-relative">
                            <div class="ecc-club-avatar">
                                <img src="{{ $memberAvatarUrl }}"
                                     alt="{{ $memberName }}"
                                     class="w-100 h-100 object-fit-cover">
                            </div>

                            @if($isVerified)
                                <span class="ecc-verified-badge">Verified</span>
                            @endif
                        </div>

                        <div class="text-center text-md-start">
                            <h1 class="ecc-club-member-name mb-2">{{ $memberName }}</h1>

                            <div class="d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2 gap-md-3 ecc-club-member-meta">
                                <span class="ecc-tier-pill">
                                    <i class="mdi mdi-shield-check-outline"></i>
                                    <span>{{ $memberTierName }}</span>
                                </span>

                                @if($memberIdLabel)
                                    <span class="ecc-meta-dot d-none d-md-inline">•</span>
                                    <span>Member ID: <strong>{{ $memberIdLabel }}</strong></span>
                                @endif

                                @if($memberSinceLabel)
                                    <span class="ecc-meta-dot d-none d-md-inline">•</span>
                                    <span>Since: <strong>{{ $memberSinceLabel }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-auto">
                    <div class="d-flex justify-content-center justify-content-lg-end">
                        <button type="button"
                                wire:click="openConciergeModal"
                                class="btn ecc-btn-gold px-4 px-lg-5 py-3">
                            <i class="mdi mdi-lifebuoy me-2"></i>
                            Contact Concierge
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Privileges -->
        <section>
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 mb-lg-4">
                <h2 class="ecc-section-title mb-0">
                    <span class="material-symbols-outlined me-2">stars</span>
                    Your Privileges
                </h2>

                @if($benefitsUrl)
                    <a href="{{ $benefitsUrl }}" class="ecc-inline-link">
                        View All Benefits
                    </a>
                @endif
            </div>

            <div class="row g-3 g-lg-4">
                @foreach($priv as $p)
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="ecc-privilege-card h-100">
                            <div class="ecc-privilege-icon">
                                <span class="material-symbols-outlined">{{ $p['icon'] ?? 'stars' }}</span>
                            </div>

                            <h3 class="ecc-privilege-title">{{ $p['title'] }}</h3>

                            @if(!empty($p['subtitle']))
                                <p class="ecc-privilege-text mb-0">{{ $p['subtitle'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Ledger + Dossier -->
        <section>
            <div class="row g-4">
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
            </div>
        </section>

       

    </div>

    <!-- Contact Concierge Modal -->
    <div class="modal fade {{ $showConciergeModal ? 'show d-block' : '' }}"
         tabindex="-1"
         role="dialog"
         @if($showConciergeModal) style="background: rgba(0,0,0,.75); z-index: 1060;" @else style="display:none;" @endif>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content ecc-concierge-modal">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="ecc-modal-kicker mb-2">PRIVATE MEMBER ENQUIRY</div>
                        <h5 class="ecc-modal-title mb-1">Contact Concierge</h5>
                        <p class="ecc-modal-subtitle mb-0">
                            Submit a premium support or acquisition enquiry and our concierge team will respond promptly.
                        </p>
                    </div>

                    <button type="button"
                            class="btn-close btn-close-white"
                            aria-label="Close"
                            wire:click="closeConciergeModal"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label ecc-form-label">Enquiry Subject</label>
                            <select class="form-select ecc-form-control @error('conciergeForm.subject') is-invalid @enderror"
                                    wire:model="conciergeForm.subject">
                                <option value="membership_upgrade">Membership Upgrade</option>
                                <option value="dining_reservations">Dining & Event Reservations</option>
                                <option value="general_feedback">General Feedback</option>
                                <option value="other">Other Inquiry</option>
                            </select>
                            @error('conciergeForm.subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label ecc-form-label">Message</label>
                            <textarea rows="5"
                                      placeholder="How can we assist you today?"
                                      class="form-control ecc-form-control @error('conciergeForm.message') is-invalid @enderror"
                                      wire:model="conciergeForm.message"></textarea>
                            @error('conciergeForm.message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    @if ($conciergeSubmissionError)
                        <div class="alert alert-danger mt-4 mb-0">
                             {{ $conciergeSubmissionError }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 pt-3 pb-4">
                    <button type="button"
                            class="btn ecc-btn-outline-light px-4 py-2"
                            wire:click="closeConciergeModal">
                        Cancel
                    </button>

                    <button type="button"
                            class="btn ecc-btn-gold px-5 py-2"
                            wire:click="submitConciergeEnquiry"
                            wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submitConciergeEnquiry">Submit Enquiry</span>
                        <div wire:loading wire:target="submitConciergeEnquiry" class="spinner-border spinner-border-sm text-dark" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .ecc-club-page {
        color: #f5efe1;
    }

    .ecc-club-hero-card,
    .ecc-privilege-card,
    .ecc-club-panel,
    .ecc-club-quote-section {
        background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
        border: 1px solid rgba(212,175,55,.12);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
    }

    .ecc-club-hero-card {
        padding: 1.75rem;
        background-image: linear-gradient(90deg, rgba(212,175,55,.10), rgba(212,175,55,.02));
    }

    .ecc-club-avatar-wrap {
        width: 132px;
        min-width: 132px;
    }

    .ecc-club-avatar {
        width: 132px;
        height: 132px;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid #d4af37;
        padding: 4px;
        background: rgba(212,175,55,.06);
        box-shadow: 0 0 24px rgba(212,175,55,.12);
    }

    .ecc-club-avatar img {
        border-radius: 50%;
    }

    .ecc-verified-badge {
        position: absolute;
        right: 0;
        bottom: -6px;
        display: inline-flex;
        align-items: center;
        padding: .28rem .7rem;
        border-radius: 999px;
        background: #d4af37;
        color: #151109;
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .ecc-club-member-name {
        color: #fff;
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        letter-spacing: -.04em;
    }

    .ecc-club-member-meta {
        color: rgba(245,239,225,.72);
        font-size: .95rem;
    }

    .ecc-tier-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        color: #d4af37;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .ecc-section-title {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -.02em;
        display: inline-flex;
        align-items: center;
    }

    .ecc-inline-link,
    .ecc-footer-link {
        color: #d4af37;
        text-decoration: none;
        font-weight: 700;
    }

    .ecc-inline-link:hover,
    .ecc-footer-link:hover {
        color: #e7c75c;
    }

    .ecc-privilege-card {
        padding: 1.5rem;
        height: 100%;
        transition: .25s ease;
    }

    .ecc-privilege-card:hover {
        border-color: rgba(212,175,55,.32);
        transform: translateY(-1px);
    }

    .ecc-privilege-icon {
        width: 52px;
        height: 52px;
        border-radius: .9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(212,175,55,.12);
        color: #d4af37;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .ecc-privilege-title {
        color: #fff;
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: .45rem;
    }

    .ecc-privilege-text,
    .ecc-list-subtitle,
    .ecc-empty-inline {
        color: rgba(245,239,225,.60);
        font-size: .92rem;
        line-height: 1.7;
    }

    .ecc-club-panel {
        overflow: hidden;
    }

    .ecc-club-panel-header {
        padding: 1.4rem 1.5rem 1rem;
    }

    .ecc-table-head {
        padding: .85rem 1.5rem;
        border-top: 1px solid rgba(212,175,55,.08);
        border-bottom: 1px solid rgba(212,175,55,.08);
        color: rgba(245,239,225,.45);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .ecc-club-list {
        display: flex;
        flex-direction: column;
    }

    .ecc-club-list-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(212,175,55,.08);
    }

    .ecc-club-list-row:last-child {
        border-bottom: none;
    }

    .ecc-list-icon {
        width: 42px;
        height: 42px;
        border-radius: .75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.05);
        color: rgba(212,175,55,.70);
        flex-shrink: 0;
    }

    .ecc-list-title {
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
    }

    .ecc-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .36rem .8rem;
        border-radius: 999px;
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .ecc-status-pill.status-success {
        background: rgba(16,185,129,.10);
        color: #34d399;
        border-color: rgba(16,185,129,.18);
    }

    .ecc-status-pill.status-warning {
        background: rgba(245,158,11,.10);
        color: #fbbf24;
        border-color: rgba(245,158,11,.18);
    }

    .ecc-status-pill.status-info {
        background: rgba(59,130,246,.10);
        color: #60a5fa;
        border-color: rgba(59,130,246,.18);
    }

    .ecc-status-pill.status-danger {
        background: rgba(239,68,68,.10);
        color: #f87171;
        border-color: rgba(239,68,68,.18);
    }

    .ecc-status-pill.status-muted {
        background: rgba(148,163,184,.10);
        color: #94a3b8;
        border-color: rgba(148,163,184,.18);
    }

    .ecc-status-pill.status-default {
        background: rgba(212,175,55,.10);
        color: #d4af37;
        border-color: rgba(212,175,55,.16);
    }

    .ecc-dossier-thumb {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: .75rem;
        overflow: hidden;
        border: 1px solid rgba(212,175,55,.18);
        background: rgba(255,255,255,.05);
    }

    .ecc-list-amount {
        color: #f5efe1;
        font-size: .82rem;
        font-weight: 700;
    }

    .ecc-panel-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(212,175,55,.08);
        text-align: center;
    }

    .ecc-footer-link-muted {
        color: rgba(245,239,225,.62);
    }

    .ecc-footer-link-muted:hover {
        color: #fff;
    }

    .ecc-club-quote-section {
        padding: 2rem 1.5rem;
        border-top: 1px solid rgba(212,175,55,.08);
    }

    .ecc-kicker {
        color: #d4af37;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .32em;
        text-transform: uppercase;
    }

    .ecc-club-quote {
        color: #fff;
        font-size: clamp(1.3rem, 3vw, 2rem);
        font-style: italic;
        font-weight: 300;
        line-height: 1.6;
    }

    .ecc-club-stat-value {
        color: #fff;
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
    }

    .ecc-club-stat-label {
        color: rgba(245,239,225,.46);
        font-size: .66rem;
        font-weight: 800;
        letter-spacing: .18em;
        text-transform: uppercase;
        margin-top: .45rem;
    }

    .ecc-club-stat-divider {
        width: 1px;
        height: 40px;
        background: rgba(212,175,55,.15);
    }

    .ecc-btn-gold {
        background: linear-gradient(180deg, #e0be52, #cfa52b);
        border: 1px solid #d4af37;
        color: #16110a;
        font-weight: 800;
        border-radius: .95rem;
    }

    .ecc-btn-gold:hover,
    .ecc-btn-gold:focus {
        background: linear-gradient(180deg, #e7c75c, #d7ad35);
        color: #16110a;
    }

    /* Concierge Modal Styles */
    .ecc-concierge-modal {
        background: linear-gradient(180deg, rgba(24,19,10,.98), rgba(17,13,7,.99));
        border: 1px solid rgba(212,175,55,.16);
        border-radius: 1.25rem;
        color: #f5efe1;
        box-shadow: 0 24px 60px rgba(0,0,0,.32);
    }

    .ecc-modal-kicker {
        color: #d4af37;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .26em;
        text-transform: uppercase;
    }

    .ecc-modal-title {
        color: #fff;
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -.03em;
    }

    .ecc-modal-subtitle {
        color: rgba(245,239,225,.64);
        line-height: 1.7;
        max-width: 520px;
        font-size: .95rem;
    }

    .ecc-form-label {
        color: #f5efe1;
        font-size: .82rem;
        font-weight: 700;
        margin-bottom: .6rem;
    }

    .ecc-form-control {
        background: rgba(255,255,255,.04) !important;
        border: 1px solid rgba(212,175,55,.15) !important;
        color: #f5efe1 !important;
        border-radius: .9rem !important;
        min-height: 48px;
        box-shadow: none !important;
        padding: .75rem 1rem;
    }

    .ecc-form-control:focus {
        background: rgba(255,255,255,.06) !important;
        border-color: rgba(212,175,55,.45) !important;
        color: #fff !important;
    }

    .ecc-form-control option {
        background: #1d170d;
        color: #f5efe1;
    }

    .ecc-alert-success {
        background: rgba(16,185,129,.08);
        border: 1px solid rgba(16,185,129,.2);
        color: #34d399;
        border-radius: 1rem;
        padding: 1.25rem;
    }

    .ecc-btn-outline-light {
        background: transparent;
        border: 1px solid rgba(245,239,225,.16);
        color: #f5efe1;
        font-weight: 700;
        border-radius: .95rem;
        transition: .2s ease;
    }

    .ecc-btn-outline-light:hover {
        background: rgba(255,255,255,.05);
        color: #fff;
        border-color: rgba(212,175,55,.25);
    }

    @media (max-width: 767.98px) {
        .ecc-club-list-row {
            flex-direction: column;
            align-items: stretch;
        }

        .ecc-club-list-row > .text-end,
        .ecc-club-list-row > .text-md-end {
            text-align: left !important;
        }
    }
</style>
@endpush
