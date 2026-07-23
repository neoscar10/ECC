<div class="w-100 pt-1 pb-3 ecc-explore-page">
    <div class="d-flex flex-column gap-2 gap-xl-3">

        {{-- Zone 1: Home Hero (Top Blocks) --}}
        @if(!empty($homeHeroBlocks))
            <x-cms.renderer :blocks="$homeHeroBlocks" placement="home-hero" />
        @endif

        {{-- Zone 2: Explore (Secondary Blocks) --}}
        @if(!empty($exploreBlocks))
            <section class="cms-explore-section">
                <x-cms.renderer :blocks="$exploreBlocks" placement="explore" />
            </section>
        @endif

        {{-- Empty State --}}
        @if(empty($homeHeroBlocks) && empty($exploreBlocks))
            <div class="text-center py-5 cms-fade-in">
                <span class="material-symbols-outlined ecc-ecc-text-muted fs-1 mb-3">auto_awesome_motion</span>
                <p class="ecc-ecc-text-muted">No content available at the moment. Check back soon!</p>
            </div>
        @endif
        
        {{-- Safety Spacer for Bottom Nav --}}
        <div class="cms-bottom-nav-spacer d-lg-none" style="height: 120px;"></div>

        @include('components.shared.premium-access-modal')
    </div>
</div>

@push('styles')
<style>
    .ecc-explore-page {
        color: var(--ecc-text-primary);
    }

    /* Hero Block Styles */
    .ecc-hero-block {
        min-height: 500px;
        border-radius: 1.75rem;
        border: 1px solid var(--ecc-primary-border);
        background: var(--ecc-bg-surface);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .ecc-hero-bg {
        position: absolute;
        inset: 0;
        z-index: 0;
    }

    .ecc-hero-bg img {
        transition: transform .8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .ecc-hero-block:hover .ecc-hero-bg img {
        transform: scale(1.05);
    }

    .ecc-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, var(--ecc-bg-surface-2) 0%, var(--ecc-overlay-dark) 45%, transparent 100%);
        z-index: 1;
    }

    html[data-theme="light"] .ecc-hero-overlay {
        background: linear-gradient(90deg, var(--ecc-bg-surface) 0%, var(--ecc-overlay-light) 45%, transparent 100%);
    }

    .ecc-hero-content {
        z-index: 2;
        padding: 4rem 3.5rem;
        max-width: 780px;
        min-height: 500px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .ecc-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        width: fit-content;
        margin-bottom: 1.25rem;
        padding: .45rem 1rem;
        border-radius: 999px;
        background: var(--ecc-primary-soft);
        border: 1px solid rgba(199, 167, 90,.24);
        color: var(--ecc-primary);
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .ecc-hero-title {
        font-size: clamp(2.8rem, 6vw, 5rem);
        line-height: .92;
        font-weight: 900;
        letter-spacing: -.05em;
        color: var(--ecc-text-primary);
    }

    .ecc-text-primary { color: var(--ecc-primary); }
    .ecc-text-italic { font-style: italic; }

    .ecc-hero-text {
        color: var(--ecc-text-secondary);
        font-size: 1.1rem;
        line-height: 1.8;
        max-width: 600px;
    }

    /* Section & Typography */
    .ecc-block-title {
        color: var(--ecc-text-primary);
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -.02em;
        margin-bottom: 0;
    }

    .ecc-block-subtitle {
        color: var(--ecc-text-subtle);
        font-size: 0.85rem;
        font-weight: 400;
    }

    .ecc-inline-link {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        color: var(--ecc-primary);
        font-weight: 800;
        text-decoration: none;
        font-size: .95rem;
        transition: .2s ease;
    }

    .ecc-inline-link:hover {
        color: #e7c75c;
        gap: .5rem;
    }

    /* Buttons */
    .ecc-btn-primary {
        background: linear-gradient(180deg, var(--ecc-primary), var(--ecc-gold-500));
        border: 1px solid var(--ecc-primary);
        color: #16110a;
        font-weight: 800;
        border-radius: 1rem;
        transition: .3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .ecc-btn-primary:hover {
        background: var(--ecc-primary-gradient-dark);
        color: #16110a;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(199, 167, 90,0.3);
    }

    .ecc-btn-glass {
        background: var(--ecc-border-soft);
        border: 1px solid var(--ecc-border);
        color: var(--ecc-text-primary);
        border-radius: 1rem;
        font-weight: 700;
        backdrop-filter: blur(12px);
        transition: .3s ease;
    }

    .ecc-btn-glass:hover {
        background: var(--ecc-text-primary);
        border-color: var(--ecc-primary);
        color: var(--ecc-text-primary);
    }

    /* Animation */
    .cms-fade-in {
        animation: eccFadeIn 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes eccFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Utilities */
    .truncate-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Mobile Adjustments */
    @media (max-width: 991.98px) {
        .ecc-hero-content {
            padding: 3rem 2rem;
            min-height: 440px;
        }

        .ecc-block-title {
            font-size: 1.8rem;
        }

        .ecc-hero-title {
            font-size: 3rem;
        }
    }

    @media (max-width: 767.98px) {
        .ecc-hero-block,
        .ecc-hero-content {
            min-height: 400px;
        }

        .ecc-hero-content {
            padding: 2rem 1.5rem;
        }

        .ecc-hero-title {
            font-size: 2.5rem;
        }
    }
</style>
{{-- Swiper Bundle --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
@endpush
