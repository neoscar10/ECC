<div class="container-xxl py-4 py-lg-5 ecc-vault-page">
    <div class="d-flex flex-column gap-4 gap-xl-5">

        <!-- Header Section -->
        <section class="ecc-vault-header position-relative overflow-hidden">
            <div class="row g-4 align-items-end">
                <div class="col-12 col-lg-7">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="ecc-vault-header-line"></span>
                        <span class="ecc-vault-kicker">SECURITY PROTOCOL {{ $vaultProtocolVersion ?? 'V4.2' }}</span>
                    </div>

                    <h1 class="ecc-vault-title mb-3">THE VAULT</h1>

                    <p class="ecc-vault-subtitle mb-0">
                        {{ $vaultIntroText ?? 'Your digital stronghold for authenticated assets and secured certificates of provenance.' }}
                    </p>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="ecc-vault-standing-card">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                            <div>
                                <div class="ecc-vault-standing-label">ACCOUNT STANDING</div>
                                <div class="ecc-vault-standing-tier">{{ $vaultTierLabel }}</div>
                            </div>

                            <div class="text-md-end">
                                <span class="ecc-vault-access-pill">
                                    <i class="mdi mdi-shield-check-outline me-2"></i>
                                    {{ $vaultAccessLabel ?? 'VAULT ACCESS: GRANTED' }}
                                </span>

                                @if(!empty($vaultVerificationLabel))
                                    <div class="ecc-vault-standing-note mt-2">{{ $vaultVerificationLabel }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section>
            <div class="row g-4 g-xl-5">
                <!-- Left Sidebar -->
                <div class="col-12 col-xl-3">
                    <div class="d-flex flex-column gap-4">

                        @if(!empty($vaultSecurityItems))
                            <div class="ecc-vault-sidebar-card">
                                <h2 class="ecc-vault-sidebar-title">Vault Security Protocols</h2>

                                <div class="d-flex flex-column gap-4">
                                    @foreach($vaultSecurityItems as $securityItem)
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="ecc-vault-sidebar-icon">
                                                <i class="{{ $securityItem['icon'] ?? 'mdi mdi-shield-lock-outline' }}"></i>
                                            </div>

                                            <div>
                                                <div class="ecc-vault-sidebar-item-title">{{ $securityItem['title'] }}</div>
                                                @if(!empty($securityItem['description']))
                                                    <div class="ecc-vault-sidebar-item-text">{{ $securityItem['description'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($insuredValueLabel) || !empty($policyStatusLabel))
                            <div class="ecc-vault-value-card">
                                <div class="ecc-vault-value-label">TOTAL INSURED VALUE</div>
                                <div class="ecc-vault-value-amount">{{ $insuredValueLabel }}</div>

                                <div class="ecc-vault-value-footer">
                                    <span>{{ $policyStatusLabel ?? 'POLICY ACTIVE' }}</span>
                                    <span class="ecc-vault-value-status-dot"></span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Artifact Grid -->
                <div class="col-12 col-xl-9">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                        <h2 class="ecc-vault-grid-title">
                            SECURED ARTIFACTS
                            @if(isset($vaultArtifactCount) && $vaultArtifactCount !== null)
                                ({{ str_pad((string) $vaultArtifactCount, 2, '0', STR_PAD_LEFT) }})
                            @endif
                        </h2>

                        @if($supportsVaultViewToggle)
                            <div class="d-flex align-items-center gap-2">
                                <button type="button"
                                        class="btn ecc-vault-toggle-btn {{ $vaultViewMode === 'grid' ? 'is-active' : '' }}"
                                        wire:click="setVaultView('grid')">
                                    <i class="mdi mdi-view-grid-outline"></i>
                                </button>

                                <button type="button"
                                        class="btn ecc-vault-toggle-btn {{ $vaultViewMode === 'list' ? 'is-active' : '' }}"
                                        wire:click="setVaultView('list')">
                                    <i class="mdi mdi-format-list-bulleted"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="row g-4">
                        @foreach($vaultArtifacts as $artifact)
                            <div class="{{ $vaultViewMode === 'list' ? 'col-12' : 'col-12 col-md-6' }}">
                                <article class="ecc-vault-artifact-card h-100 {{ $vaultViewMode === 'list' ? 'd-flex align-items-center' : '' }}">
                                    <div class="ecc-vault-artifact-media position-relative {{ $vaultViewMode === 'list' ? 'w-25 h-100 min-vh-25' : '' }}" @if($vaultViewMode === 'list') style="min-width: 250px" @endif>
                                        <img src="{{ $artifact->image_url }}"
                                             alt="{{ $artifact->title }}"
                                             class="w-100 h-100 object-fit-cover">

                                        @if($artifact->status_badge_label)
                                            <span class="ecc-vault-artifact-badge">
                                                {{ $artifact->status_badge_label }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="ecc-vault-artifact-body {{ $vaultViewMode === 'list' ? 'flex-grow-1 p-4' : '' }}">
                                        <h3 class="ecc-vault-artifact-title">{{ $artifact->title }}</h3>

                                        @if($artifact->description)
                                            <p class="ecc-vault-artifact-text {{ $vaultViewMode === 'list' ? 'pe-lg-5' : '' }}">{{ \Illuminate\Support\Str::limit($artifact->description, 120) }}</p>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-auto pt-3">
                                            @if($artifact->certificate_url)
                                                <a href="{{ $artifact->certificate_url }}"
                                                   target="_blank"
                                                   class="ecc-vault-inline-link">
                                                    <i class="mdi mdi-file-document-outline"></i>
                                                    <span>DIGITAL CERTIFICATE</span>
                                                </a>
                                            @elseif($artifact->details_url)
                                                <a href="{{ $artifact->details_url }}"
                                                   class="ecc-vault-inline-link">
                                                    <i class="mdi mdi-file-document-outline"></i>
                                                    <span>VIEW DETAILS</span>
                                                </a>
                                            @endif

                                            @if($artifact->reference_label)
                                                <span class="ecc-vault-ref">{{ $artifact->reference_label }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach

                        @if(empty($vaultArtifacts) || count($vaultArtifacts) === 0)
                            <div class="col-12">
                                <div class="ecc-empty-state py-5 text-center">
                                    <div class="mb-3">
                                        <i class="mdi mdi-shield-lock-outline fs-1" style="color: var(--luxe-gold);"></i>
                                    </div>
                                    <h4 class="text-white fw-bold">
                                        {{ auth('web')->user()?->has_vault_access ? 'NO SECURED ARTIFACTS' : 'VAULT ACCESS RESTRICTED' }}
                                    </h4>
                                    <p class="text-white-50 mx-auto" style="max-width: 400px;">
                                        {{ auth('web')->user()?->has_vault_access 
                                            ? 'Your vault is currently empty. Acquire and secure premium assets to view them here.' 
                                            : 'Upgrade your membership to unlock full access to the Executive Vault.' }}
                                    </p>
                                    
                                    @if(!auth('web')->user()?->has_vault_access)
                                        <button class="btn ecc-btn-gold mt-3 px-4 py-2" wire:click="$set('showAccessModal', true)">
                                            UNLOCK ACCESS
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif


                    </div>
                </div>
            </div>
        </section>

    </div>

    {{-- Premium Access Upgrade Modal --}}
    @include('components.shared.premium-access-modal')
</div>

@push('styles')
<style>
    .ecc-vault-page {
        color: #f5efe1;
    }

    .ecc-vault-header {
        padding-top: 1rem;
        padding-bottom: 1rem;
        position: relative;
    }

    .ecc-vault-header::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(212,175,55,.08) 0%, transparent 70%);
        pointer-events: none;
    }

    .ecc-vault-header-line {
        width: 48px;
        height: 1px;
        background: #d4af37;
        display: inline-block;
    }

    .ecc-vault-kicker {
        color: #d4af37;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .24em;
        text-transform: uppercase;
    }

    .ecc-vault-title {
        font-size: clamp(2.8rem, 7vw, 6rem);
        line-height: .92;
        font-weight: 900;
        letter-spacing: -.06em;
        color: #fff;
        text-transform: uppercase;
        margin: 0;
    }

    .ecc-vault-subtitle {
        color: rgba(245,239,225,.70);
        max-width: 620px;
        font-size: 1.05rem;
        line-height: 1.8;
    }

    .ecc-vault-standing-card,
    .ecc-vault-sidebar-card,
    .ecc-vault-artifact-card,
    .ecc-vault-appraisal-card {
        background: linear-gradient(180deg, rgba(24,19,10,.94), rgba(17,13,7,.98));
        border: 1px solid rgba(212,175,55,.14);
        border-radius: 1.25rem;
        box-shadow: 0 12px 30px rgba(0,0,0,.14);
        color: #f5efe1;
    }

    .ecc-vault-standing-card {
        padding: 1.5rem;
    }

    .ecc-vault-standing-label,
    .ecc-vault-grid-title {
        color: rgba(245,239,225,.56);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .20em;
        text-transform: uppercase;
    }

    .ecc-vault-standing-tier {
        color: #d4af37;
        font-size: 2rem;
        font-weight: 900;
        letter-spacing: -.03em;
        text-transform: uppercase;
    }

    .ecc-vault-access-pill {
        display: inline-flex;
        align-items: center;
        padding: .55rem 1rem;
        border-radius: 999px;
        background: rgba(212,175,55,.10);
        border: 1px solid rgba(212,175,55,.24);
        color: #d4af37;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .ecc-vault-standing-note {
        color: rgba(245,239,225,.42);
        font-size: .62rem;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .ecc-vault-sidebar-card {
        padding: 1.5rem;
    }

    .ecc-vault-sidebar-title {
        color: #d4af37;
        font-size: .85rem;
        font-weight: 900;
        letter-spacing: .18em;
        text-transform: uppercase;
        margin-bottom: 1.25rem;
        padding-bottom: .75rem;
        border-bottom: 1px solid rgba(212,175,55,.08);
    }

    .ecc-vault-sidebar-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: .85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(212,175,55,.12);
        color: #d4af37;
    }

    .ecc-vault-sidebar-item-title {
        color: #fff;
        font-size: .98rem;
        font-weight: 800;
        margin-bottom: .15rem;
    }

    .ecc-vault-sidebar-item-text {
        color: rgba(245,239,225,.58);
        font-size: .84rem;
        line-height: 1.7;
    }

    .ecc-vault-value-card {
        padding: 1.5rem;
        border-left: 4px solid #d4af37;
        border-radius: 1.1rem;
        background: rgba(255,255,255,.03);
        border-top: 1px solid rgba(212,175,55,.08);
        border-right: 1px solid rgba(212,175,55,.08);
        border-bottom: 1px solid rgba(212,175,55,.08);
    }

    .ecc-vault-value-label {
        color: rgba(245,239,225,.56);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .18em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }

    .ecc-vault-value-amount {
        color: #fff;
        font-size: clamp(1.8rem, 3vw, 2.6rem);
        font-weight: 900;
        letter-spacing: -.04em;
    }

    .ecc-vault-value-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(245,239,225,.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: rgba(245,239,225,.52);
        font-size: .64rem;
        font-weight: 900;
        letter-spacing: .18em;
        text-transform: uppercase;
    }

    .ecc-vault-value-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 10px rgba(16,185,129,.45);
    }

    .ecc-vault-toggle-btn {
        width: 40px;
        height: 40px;
        border-radius: .85rem;
        border: 1px solid rgba(212,175,55,.12);
        background: rgba(255,255,255,.03);
        color: rgba(245,239,225,.56);
    }

    .ecc-vault-toggle-btn.is-active,
    .ecc-vault-toggle-btn:hover {
        color: #d4af37;
        border-color: rgba(212,175,55,.24);
        background: rgba(212,175,55,.06);
    }

    .ecc-vault-artifact-card,
    .ecc-vault-appraisal-card {
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .ecc-vault-artifact-media {
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: #121212;
    }

    .ecc-vault-artifact-media img {
        transition: .7s ease;
        filter: grayscale(.55);
        opacity: .78;
    }

    .ecc-vault-artifact-card:hover .ecc-vault-artifact-media img {
        transform: scale(1.08);
        filter: grayscale(0);
        opacity: 1;
    }

    .ecc-vault-artifact-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: .35rem .7rem;
        border-radius: .6rem;
        background: rgba(0,0,0,.62);
        border: 1px solid rgba(212,175,55,.28);
        color: #d4af37;
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .ecc-vault-artifact-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .ecc-vault-artifact-title {
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -.03em;
        margin-bottom: .5rem;
    }

    .ecc-vault-artifact-text {
        color: rgba(245,239,225,.60);
        line-height: 1.75;
        margin-bottom: 1rem;
    }

    .ecc-vault-inline-link {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        color: #d4af37;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .16em;
        text-transform: uppercase;
        text-decoration: none;
    }

    .ecc-vault-inline-link:hover {
        color: #e7c75c;
    }

    .ecc-vault-ref {
        color: rgba(245,239,225,.35);
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .ecc-vault-appraisal-card {
        border-style: dashed;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
    }

    .ecc-vault-appraisal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(212,175,55,.08);
        color: #d4af37;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin-bottom: 1.25rem;
    }

    .ecc-vault-appraisal-title {
        color: #fff;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -.02em;
        margin-bottom: .5rem;
        text-transform: uppercase;
    }

    .ecc-vault-appraisal-text {
        color: rgba(245,239,225,.58);
        line-height: 1.75;
        max-width: 260px;
        margin-bottom: 1.5rem;
    }

    .ecc-btn-gold {
        background: linear-gradient(180deg, #e0be52, #cfa52b);
        border: 1px solid #d4af37;
        color: #16110a !important;
        font-weight: 800;
        border-radius: .95rem;
        text-transform: uppercase;
        letter-spacing: .12em;
        text-decoration: none;
        display: inline-block;
        transition: filter .2s ease;
    }

    .ecc-btn-gold:hover,
    .ecc-btn-gold:focus {
        filter: brightness(1.1);
        color: #16110a;
    }
</style>
@endpush
