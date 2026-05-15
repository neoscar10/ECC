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
        @include('livewire.club.partials.hero')

        <!-- Privileges -->
        @include('livewire.club.partials.privileges')

        <!-- Ledger + Dossier -->
        <section>
            <div class="row g-4">
                <!-- Concierge Ledger -->
                @include('livewire.club.partials.concierge-ledger')

                <!-- Auction Dossier -->
                @include('livewire.club.partials.auction-dossier')
            </div>
        </section>

       

    </div>

    <!-- Contact Concierge Modal -->
    @include('livewire.club.partials.modals.concierge-modal')
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
        border: 1px solid rgba(199, 167, 90,.12);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
    }

    .ecc-club-hero-card {
        padding: 1.75rem;
        background-image: linear-gradient(90deg, rgba(199, 167, 90,.10), rgba(199, 167, 90,.02));
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
        border: 4px solid var(--ecc-primary);
        padding: 4px;
        background: rgba(199, 167, 90,.06);
        box-shadow: 0 0 24px rgba(199, 167, 90,.12);
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
        background: var(--ecc-primary);
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
        color: var(--ecc-primary);
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
        color: var(--ecc-primary);
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
        border-color: rgba(199, 167, 90,.32);
        transform: translateY(-1px);
    }

    .ecc-privilege-icon {
        width: 52px;
        height: 52px;
        border-radius: .9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(199, 167, 90,.12);
        color: var(--ecc-primary);
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
        border-top: 1px solid rgba(199, 167, 90,.08);
        border-bottom: 1px solid rgba(199, 167, 90,.08);
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
        border-bottom: 1px solid rgba(199, 167, 90,.08);
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
        color: rgba(199, 167, 90,.70);
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
        background: rgba(199, 167, 90,.10);
        color: var(--ecc-primary);
        border-color: rgba(199, 167, 90,.16);
    }

    .ecc-dossier-thumb {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: .75rem;
        overflow: hidden;
        border: 1px solid rgba(199, 167, 90,.18);
        background: rgba(255,255,255,.05);
    }

    .ecc-list-amount {
        color: #f5efe1;
        font-size: .82rem;
        font-weight: 700;
    }

    .ecc-panel-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(199, 167, 90,.08);
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
        border-top: 1px solid rgba(199, 167, 90,.08);
    }

    .ecc-kicker {
        color: var(--ecc-primary);
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
        background: rgba(199, 167, 90,.15);
    }

    .ecc-btn-primary {
        background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
        border: 1px solid var(--ecc-primary);
        color: #16110a;
        font-weight: 800;
        border-radius: .95rem;
    }

    .ecc-btn-primary:hover,
    .ecc-btn-primary:focus {
        background: var(--ecc-primary-gradient-dark);
        color: #16110a;
    }

    /* Concierge Modal Styles */
    .ecc-concierge-modal {
        background: linear-gradient(180deg, rgba(24,19,10,.98), rgba(17,13,7,.99));
        border: 1px solid rgba(199, 167, 90,.16);
        border-radius: 1.25rem;
        color: #f5efe1;
        box-shadow: 0 24px 60px rgba(0,0,0,.32);
    }

    .ecc-modal-kicker {
        color: var(--ecc-primary);
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
        border: 1px solid rgba(199, 167, 90,.15) !important;
        color: #f5efe1 !important;
        border-radius: .9rem !important;
        min-height: 48px;
        box-shadow: none !important;
        padding: .75rem 1rem;
    }

    .ecc-form-control:focus {
        background: rgba(255,255,255,.06) !important;
        border-color: rgba(199, 167, 90,.45) !important;
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
        border-color: rgba(199, 167, 90,.25);
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
