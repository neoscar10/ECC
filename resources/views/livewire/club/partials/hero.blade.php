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
