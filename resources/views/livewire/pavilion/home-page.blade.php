<div class="container-xxl py-4 py-lg-5 ecc-explore-page">
    <div class="d-flex flex-column gap-4 gap-xl-5">

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
                <span class="material-symbols-outlined text-muted fs-1 mb-3">auto_awesome_motion</span>
                <p class="text-muted">No content available at the moment. Check back soon!</p>
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
        color: #f5efe1;
    }

    /* Hero Block Styles */
    .ecc-hero-block {
        min-height: 500px;
        border-radius: 1.75rem;
        border: 1px solid rgba(212,175,55,.14);
        background: #17120a;
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
        background: linear-gradient(90deg, rgba(17,13,7,.98) 0%, rgba(17,13,7,.85) 45%, rgba(17,13,7,.25) 100%);
        z-index: 1;
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
        background: rgba(212,175,55,.12);
        border: 1px solid rgba(212,175,55,.24);
        color: #d4af37;
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
        color: #fff;
    }

    .ecc-text-gold { color: #d4af37; }
    .ecc-text-italic { font-style: italic; }

    .ecc-hero-text {
        color: rgba(245,239,225,.76);
        font-size: 1.1rem;
        line-height: 1.8;
        max-width: 600px;
    }

    /* Section & Typography */
    .ecc-block-title {
        color: #fff;
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -.02em;
        margin-bottom: 0;
    }

    .ecc-block-subtitle {
        color: rgba(245,239,225,.45);
        font-size: 0.85rem;
        font-weight: 400;
    }

    .ecc-inline-link {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        color: #d4af37;
        font-weight: 800;
        text-decoration: none;
        font-size: .95rem;
        transition: .2s ease;
    }

    .ecc-inline-link:hover {
        color: #e7c75c;
        gap: .5rem;
    }

    /* Cards - Auction style (3:4) */
    .ecc-auction-card {
        transition: transform .3s ease;
    }

    .ecc-auction-card-media {
        aspect-ratio: 1/1;
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid rgba(212,175,55,.12);
        background: #1a140b;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .ecc-card-badge {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 10;
        padding: .25rem .5rem;
        border-radius: .4rem;
        background: rgba(17,13,7,.85);
        backdrop-filter: blur(8px);
        color: #d4af37;
        font-size: .55rem;
        font-weight: 900;
        letter-spacing: .1em;
        text-transform: uppercase;
        border: 1px solid rgba(212,175,55,.2);
    }

    .ecc-card-media-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(17,13,7,0.9) 0%, rgba(17,13,7,0) 45%);
        z-index: 1;
    }

    .ecc-card-bottom-meta {
        position: absolute;
        left: 0.75rem;
        right: 0.75rem;
        bottom: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        z-index: 2;
    }

    .ecc-countdown {
        color: #fff;
        font-size: .7rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        background: rgba(0,0,0,0.3);
        padding: .2rem .4rem;
        border-radius: .35rem;
    }

    .ecc-card-action-icon {
        width: 36px;
        height: 36px;
        border-radius: .7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #d4af37;
        color: #17120a;
        font-size: 1rem;
        box-shadow: 0 6px 20px rgba(212,175,55,.25);
        transition: .2s ease;
    }

    .ecc-auction-card:hover .ecc-card-action-icon {
        background: #e7c75c;
        transform: scale(1.1);
    }

    .ecc-card-title {
        color: #fff;
        font-size: 0.95rem;
        font-weight: 800;
        margin-bottom: .2rem;
    }

    .ecc-card-meta-label {
        color: rgba(245,239,225,.4);
        font-size: .75rem;
        font-weight: 600;
    }

    .ecc-card-meta-value {
        color: #d4af37;
        font-weight: 800;
        font-size: .85rem;
    }

    /* Editorial Cards (16:9 / manual) */
    .ecc-editorial-card {
        aspect-ratio: 1/1;
        border-radius: 1.15rem;
        border: 1px solid rgba(212,175,55,.1);
        background: #1a140b;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .ecc-editorial-card img {
        transition: transform .7s ease;
    }

    .ecc-editorial-card:hover img {
        transform: scale(1.08);
    }

    .ecc-editorial-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(13,9,5,0.95) 0%, rgba(13,9,5,0.7) 35%, rgba(13,9,5,0.2) 100%);
        z-index: 1;
    }

    .ecc-editorial-content {
        position: absolute;
        left: 1rem;
        right: 1rem;
        bottom: 1rem;
        z-index: 2;
    }

    .ecc-editorial-title {
        color: #fff;
        font-size: 1.15rem;
        line-height: 1.1;
        font-style: italic;
        font-weight: 900;
        margin-bottom: .15rem;
        letter-spacing: -.01em;
    }

    .ecc-editorial-meta {
        color: #d4af37;
        font-weight: 800;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .08em;
    }

    /* Buttons */
    .ecc-btn-gold {
        background: linear-gradient(180deg, #e0be52, #cfa52b);
        border: 1px solid #d4af37;
        color: #16110a;
        font-weight: 800;
        border-radius: 1rem;
        transition: .3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .ecc-btn-gold:hover {
        background: linear-gradient(180deg, #e7c75c, #d7ad35);
        color: #16110a;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(212,175,55,0.3);
    }

    .ecc-btn-glass {
        background: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.12);
        color: #fff;
        border-radius: 1rem;
        font-weight: 700;
        backdrop-filter: blur(12px);
        transition: .3s ease;
    }

    .ecc-btn-glass:hover {
        background: rgba(255,255,255,.12);
        border-color: #d4af37;
        color: #fff;
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
