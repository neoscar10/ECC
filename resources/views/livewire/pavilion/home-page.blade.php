<div class="cms-home-container">
    {{-- Zone 1: Home Hero (Top Blocks) --}}
    @if(!empty($homeHeroBlocks))
        <section class="cms-hero-section mb-5">
            <x-cms.renderer :blocks="$homeHeroBlocks" placement="home-hero" />
        </section>
    @endif

    {{-- Zone 2: Explore (Secondary Blocks) --}}
    @if(!empty($exploreBlocks))
        <section class="cms-explore-section">
            <x-cms.renderer :blocks="$exploreBlocks" placement="explore" />
        </section>
    @endif

    {{-- Empty State (Optional) --}}
    @if(empty($homeHeroBlocks) && empty($exploreBlocks))
        <div class="text-center py-5 cms-fade-in">
            <span class="material-symbols-outlined text-muted fs-1 mb-3">auto_awesome_motion</span>
            <p class="text-muted">No content available at the moment. Check back soon!</p>
        </div>
    @endif
    
    {{-- Safety Spacer for Bottom Nav --}}
    <div class="cms-bottom-nav-spacer d-lg-none" style="height: 120px;"></div>
</div>

@push('styles')
<style>
    .cms-home-container {
        max-width: 100%;
        overflow-x: hidden;
        padding-bottom: 60px;
    }
    /* Specific adjustments for Hero zone */
    .cms-hero-section .cms-block-wrapper {
        margin-bottom: 3rem !important;
    }
    .cms-explore-section {
        margin-bottom: 4rem;
    }

    @keyframes cmsFadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cms-fade-in {
        animation: cmsFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>
{{-- Swiper CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
@endpush

@push('scripts')
{{-- Swiper JS --}}
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
@endpush
