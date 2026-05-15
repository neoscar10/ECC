<div class="container-xxl py-4 py-lg-5 ecc-settings-page">
    <div class="row g-4 g-xl-5">
        <!-- Left Column -->
        <div class="col-12 col-xl-3">
            <div class="d-flex flex-column gap-4">
                <!-- Profile Card -->
                @include('livewire.settings.partials.profile-card')

                <!-- Side Nav -->
                @include('livewire.settings.partials.side-nav')
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-12 col-xl-9">
            <div class="d-flex flex-column gap-4 gap-xl-5">

                <!-- Heading & Action Cards -->
                @include('livewire.settings.partials.account-actions')

                <!-- App Information -->
                @include('livewire.settings.partials.app-information')

                
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('livewire.settings.partials.modals.edit-profile')
    @include('livewire.settings.partials.modals.change-password')
    @include('livewire.settings.partials.modals.membership-details')
</div>

@push('styles')
<style>
    .ecc-settings-page {
        color: #f5efe1;
    }

    .ecc-settings-profile-card,
    .ecc-settings-side-nav,
    .ecc-settings-action-card,
    .ecc-settings-info-link,
    .ecc-settings-modal {
        background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
        border: 1px solid rgba(199, 167, 90,.12);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
    }

    .ecc-settings-profile-card {
        padding: 1.5rem;
    }

    .ecc-settings-avatar-wrap {
        width: 96px;
        height: 96px;
        position: relative;
    }

    .ecc-settings-avatar-placeholder {
        width: 96px;
        height: 96px;
        background: rgba(255,255,255,.05);
        border: 2px solid var(--ecc-primary);
        color: var(--ecc-primary);
        font-size: 2rem;
    }

    .ecc-settings-avatar-upload-btn {
        position: absolute;
        right: 0px;
        bottom: 0px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--ecc-primary);
        color: #151109;
        cursor: pointer;
        border: 2px solid #0b0904;
        box-shadow: 0 8px 20px rgba(0,0,0,.18);
        z-index: 2;
        transition: .2s ease;
    }

    .ecc-settings-avatar-upload-btn:hover {
        filter: brightness(1.1);
        transform: scale(1.05);
    }

    .ecc-avatar-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 3;
        color: var(--ecc-primary);
    }
    
    .ecc-spin {
        animation: ecc-spin 1s linear infinite;
    }
    @keyframes ecc-spin { 100% { transform: rotate(360deg); } }

    .ecc-settings-avatar-badge {
        position: absolute;
        right: -4px;
        bottom: -4px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--ecc-primary);
        color: #151109;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(0,0,0,.18);
    }

    .ecc-settings-profile-name {
        color: #fff;
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: -.03em;
    }

    .ecc-settings-profile-meta {
        color: rgba(245,239,225,.58);
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .18em;
        text-transform: uppercase;
    }

    .ecc-membership-badge {
        display: inline-flex;
        align-items: center;
        padding: .45rem .9rem;
        border-radius: 999px;
        background: rgba(199, 167, 90,.10);
        border: 1px solid rgba(199, 167, 90,.18);
        color: var(--ecc-primary);
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .ecc-settings-side-nav {
        padding: 1rem;
    }

    .ecc-settings-side-label {
        color: rgba(245,239,225,.52);
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .24em;
        text-transform: uppercase;
        margin-bottom: .8rem;
        padding-inline: .5rem;
    }

    .ecc-settings-side-link {
        display: flex;
        align-items: center;
        gap: .9rem;
        padding: 1rem;
        border-radius: 1rem;
        color: rgba(245,239,225,.78);
        text-decoration: none;
        transition: .2s ease;
    }

    .ecc-settings-side-link:hover {
        background: rgba(255,255,255,.04);
        color: #fff;
    }

    .ecc-settings-side-link.is-active {
        background: var(--ecc-primary);
        color: #16110a;
        font-weight: 800;
    }

    .ecc-settings-side-link-icon {
        width: 22px;
        text-align: center;
    }

    .ecc-settings-title {
        color: #fff;
        font-size: clamp(2.2rem, 4vw, 4rem);
        line-height: .95;
        font-weight: 900;
        letter-spacing: -.05em;
    }

    .ecc-settings-subtitle {
        color: rgba(245,239,225,.64);
        max-width: 640px;
        line-height: 1.8;
    }

    .ecc-settings-action-card {
        width: 100%;
        padding: 1.5rem;
        color: #f5efe1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        transition: .25s ease;
        text-align: left;
        border: 1px solid rgba(199, 167, 90,.12);
        background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
        border-radius: 1.25rem;
    }

    .ecc-settings-action-card:hover {
        border-color: rgba(199, 167, 90,.28);
        transform: translateY(-1px);
        background: linear-gradient(180deg, rgba(34,29,20,.94), rgba(27,23,17,.98));
    }

    .ecc-settings-action-icon {
        width: 52px;
        height: 52px;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(199, 167, 90,.10);
        color: var(--ecc-primary);
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .ecc-settings-action-title {
        color: #fff;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .ecc-settings-action-text {
        color: rgba(245,239,225,.56);
        font-size: .88rem;
        margin-top: .2rem;
    }

    .ecc-settings-action-arrow {
        color: rgba(245,239,225,.46);
        font-size: 1.3rem;
    }

    .ecc-btn-logout {
        border-radius: 999px;
        border: 1px solid rgba(255,125,125,.7);
        color: #ffb4ab;
        background: transparent;
        font-size: .78rem;
        font-weight: 900;
        letter-spacing: .2em;
        text-transform: uppercase;
    }

    .ecc-btn-logout:hover,
    .ecc-btn-logout:focus {
        background: rgba(255,125,125,.08);
        color: #ffd4cf;
    }

    .ecc-settings-divider {
        border-color: rgba(199, 167, 90,.08) !important;
    }

    .ecc-settings-section-title {
        color: #fff;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -.03em;
    }

    .ecc-settings-info-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.2rem 1.4rem;
        color: #f5efe1;
        text-decoration: none;
        transition: .2s ease;
    }

    .ecc-settings-info-link:hover {
        border-color: rgba(199, 167, 90,.28);
        color: #fff;
        background: rgba(255,255,255,.02);
    }

    .ecc-settings-divider-line {
        height: 1px;
        background: rgba(199, 167, 90,.08);
    }

    .ecc-settings-version {
        color: rgba(245,239,225,.46);
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .22em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .ecc-settings-modal {
        background: linear-gradient(180deg, rgba(24,19,10,.98), rgba(17,13,7,.99));
        border: 1px solid rgba(199, 167, 90,.16);
        color: #f5efe1;
        border-radius: 1.5rem;
    }

    .ecc-settings-modal-kicker {
        color: var(--ecc-primary);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .24em;
        text-transform: uppercase;
    }

    .ecc-settings-modal-title {
        color: #fff;
        font-size: 1.6rem;
        font-weight: 800;
        letter-spacing: -.03em;
    }

    .ecc-settings-modal-subtitle {
        color: rgba(245,239,225,.62);
        line-height: 1.7;
    }

    .ecc-settings-form-label {
        color: #f5efe1;
        font-size: .82rem;
        font-weight: 700;
        margin-bottom: .45rem;
    }

    .ecc-settings-form-control {
        min-height: 48px;
        border-radius: .9rem;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(199, 167, 90,.12);
        color: #f5efe1;
        box-shadow: none !important;
    }

    .ecc-settings-form-control:focus {
        background: rgba(255,255,255,.06);
        border-color: rgba(199, 167, 90,.42);
        color: #fff;
    }

    .ecc-settings-alert-success {
        background: rgba(16,185,129,.10);
        border: 1px solid rgba(16,185,129,.18);
        color: #34d399;
        border-radius: .9rem;
    }

    .ecc-membership-detail-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .95rem 1rem;
        border-radius: .9rem;
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(199, 167, 90,.08);
    }

    .ecc-membership-detail-label {
        color: rgba(245,239,225,.56);
    }

    .ecc-membership-detail-note {
        color: rgba(245,239,225,.68);
        line-height: 1.8;
        padding-top: .25rem;
    }

    .ecc-btn-primary {
        background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
        border: 1px solid var(--ecc-primary);
        color: #16110a;
        font-weight: 800;
        border-radius: .95rem;
        padding: 0.75rem 1.5rem;
    }

    .ecc-btn-primary:hover,
    .ecc-btn-primary:focus {
        background: var(--ecc-primary-gradient-dark);
        color: #16110a;
    }

    .ecc-btn-outline-light {
        background: transparent;
        border: 1px solid rgba(245,239,225,.16);
        color: #f5efe1;
        font-weight: 700;
        border-radius: .95rem;
        padding: 0.75rem 1.5rem;
    }

    .ecc-btn-outline-light:hover,
    .ecc-btn-outline-light:focus {
        background: rgba(255,255,255,.04);
        color: #fff;
        border-color: rgba(199, 167, 90,.24);
    }
</style>
@endpush
