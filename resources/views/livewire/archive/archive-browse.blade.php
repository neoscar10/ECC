<div class="archive-page">
    {{-- OPTIONAL MINIMAL INTRO --}}
    <div class="archive-page-top">
        <span class="archive-page-kicker">
            <i class="mdi mdi-image-multiple-outline"></i>
            <span>Archive Collection</span>
        </span>

        <h1 class="archive-page-title mt-3">Explore the Archive</h1>

        <p class="archive-page-subtitle">
            Discover rare cricket memorabilia, historical collectibles, and premium archive pieces curated for members and collectors.
        </p>
    </div>

    {{-- FILTER + SORT TOOLBAR --}}
    <div class="archive-toolbar-wrap">
        <div class="archive-toolbar">
            <div class="archive-filter-rail">
                @if(empty($tabs) || !count($tabs))
                    <button type="button" class="archive-chip active">
                        <span>All</span>
                    </button>
                @else
                    @foreach($tabs as $t)
                        @php
                            $filterKey = $t['key'] ?? null;
                            $filterLabel = $t['label'] ?? null;
                            $isActive = (string) $activeTab === (string) $filterKey;
                        @endphp
                        <button
                            type="button"
                            wire:click="setTab('{{ $filterKey }}')"
                            class="archive-chip {{ $isActive ? 'active' : '' }}"
                        >
                            <span>{{ $filterLabel }}</span>
                        </button>
                    @endforeach
                @endif
            </div>
            
            {{-- Sort functionality omitted as per instructions --}}
        </div>
    </div>

    {{-- GRID --}}
    @if(collect($products)->count())
        <div class="archive-grid">
            <div class="row g-4">
                @foreach($products as $p)
                    @php
                        // Preserve original logic
                        $item = is_array($p) ? $p : $p->toArray();
                        
                        $id = $item['id'] ?? 0;
                        $title = $item['title'] ?? 'Archive Item';
                        
                        $user = auth('web')->user();
                        $tier = app(\App\Services\Membership\MembershipTierResolver::class)->resolveForUser($user);
                        $resolver = app(\App\Services\Archive\ArchiveAccessResolver::class);
                        
                        $productModel = is_array($p) ? \App\Models\Archive\ArchiveProduct::find($id) : $p;
                        $access = $resolver->resolveProductAccess($productModel, $user, $tier);
                        
                        $access['message']['icon'] = \App\Support\Archive\AccessIconNormalizer::normalize(
                            $access['reason'] ?? null, 
                            $access['view_mode'] ?? 'blocked'
                        );

                        $canView = ($access['view_mode'] === 'clear' || $access['view_mode'] === 'blur');
                        $isBlurred = ($access['view_mode'] === 'blur');
                        $lockType = $access['message']['icon'] ?? 'lock';
                        $lockTitle = $access['message']['title'] ?? 'Restricted View';
                        $lockHint = $access['message']['body'] ?? 'Membership Required';
                        
                        $subtitle = $productModel->meta_line ?? '';

                        $images = $productModel->images;
                        $image = $images->first()?->image_path ? url(\Storage::url($images->first()->image_path)) : 'https://placehold.co/800x1000/17130b/d4af37?text=Archive';

                        $detailUrl = route('archive.products.show', ['id' => $id]);
                        
                        $isAccessible = $canView && !$isBlurred;
                    @endphp

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3" wire:key="archive-product-{{ $id }}">
                        @if($isAccessible)
                            <a href="{{ $detailUrl }}" class="text-decoration-none d-block h-100">
                                <article class="archive-card">
                                    <div class="archive-card-media">
                                        <img src="{{ $image }}" alt="{{ $title }}">
                                        <div class="archive-open-badge">
                                            <span class="archive-open-badge-dot"></span>
                                            <span>Open</span>
                                        </div>
                                        <div class="archive-card-gradient"></div>
                                    </div>

                                    <div class="archive-card-body">
                                        <h3 class="archive-card-title text-truncate">{{ $title }}</h3>
                                        <p class="archive-card-subtitle text-truncate">{{ $subtitle }}</p>
                                    </div>
                                </article>
                            </a>
                        @else
                            <article class="archive-card is-locked" wire:click.prevent="openAccessModal({{ $id }})" style="cursor: pointer;">
                                <div class="archive-card-media">
                                    <img src="{{ $image }}" alt="{{ $title }}">

                                    <div class="archive-restricted-overlay">
                                        <div class="archive-restricted-content">
                                            <span class="archive-lock-icon">
                                                <span class="material-symbols-outlined fs-2">
                                                    @if($lockType === 'time-lock') lock_clock
                                                    @elseif($lockType === 'diamond') diamond
                                                    @else lock
                                                    @endif
                                                </span>
                                            </span>

                                            <div class="archive-lock-label">{{ $lockTitle }}</div>
                                            
                                            @if(!empty($lockHint))
                                                <p class="archive-lock-message">{{ $lockHint }}</p>
                                            @endif

                                            <button type="button" class="archive-unlock-btn" wire:click.prevent="openAccessModal({{ $id }})">
                                                Unlock View
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="archive-card-body">
                                    <h3 class="archive-card-title text-truncate">{{ $title }}</h3>
                                    <p class="archive-card-subtitle text-truncate opacity-50">{{ $subtitle }}</p>
                                </div>
                            </article>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- LOAD MORE / PAGINATION --}}
        <div class="archive-load-more-wrap">
            <div class="d-flex justify-content-center w-100">
                {{ $products->links() }}
            </div>
        </div>
    @else
        <div class="archive-empty-state">
            No archive items found for the selected criteria.
        </div>
    @endif
    
    {{-- Premium Access Upgrade Modal --}}
    @include('components.shared.premium-access-modal')
</div>

@push('styles')
<style>
    .archive-page {
        --archive-max: 1440px;
    }

    .archive-page-top {
        margin-bottom: 1.5rem;
    }

    .archive-page-kicker {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        padding: .5rem .95rem;
        border-radius: 999px;
        background: rgba(199, 167, 90,.08);
        border: 1px solid rgba(199, 167, 90,.16);
        color: var(--luxe-gold);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .archive-page-title {
        color: #fff;
        font-size: clamp(2rem, 3vw, 3.25rem);
        line-height: 1.02;
        font-weight: 900;
        letter-spacing: -.04em;
        margin: 0;
    }

    .archive-page-subtitle {
        color: var(--luxe-text-soft);
        font-size: 1rem;
        line-height: 1.8;
        max-width: 760px;
        margin: 1rem 0 0;
    }

    .archive-toolbar-wrap {
        position: sticky;
        top: 76px;
        z-index: 25;
        margin-bottom: 2rem;
    }

    .archive-toolbar {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1rem;
        border-radius: 22px;
        border: 1px solid rgba(199, 167, 90,.12);
        background:
            linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
            rgba(23,19,11,.92);
        backdrop-filter: blur(16px);
        box-shadow: 0 16px 32px rgba(0,0,0,.24);
    }

    .archive-filter-rail {
        display: flex;
        align-items: center;
        gap: .75rem;
        overflow-x: auto;
        overflow-y: hidden;
        flex: 1 1 auto;
        min-width: 0;
        padding-bottom: .2rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(199, 167, 90,.5) transparent;
    }

    .archive-filter-rail::-webkit-scrollbar {
        height: 5px;
    }

    .archive-filter-rail::-webkit-scrollbar-thumb {
        background: rgba(199, 167, 90,.45);
        border-radius: 999px;
    }

    .archive-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 42px;
        padding: .72rem 1rem;
        border-radius: 999px;
        border: 1px solid rgba(199, 167, 90,.14);
        background: rgba(255,255,255,.03);
        color: rgba(245,240,231,.92);
        font-size: .88rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        text-decoration: none;
        transition: .2s ease;
    }

    .archive-chip:hover {
        border-color: rgba(199, 167, 90,.34);
        color: #fff;
    }

    .archive-chip.active {
        background: var(--luxe-gold);
        border-color: var(--luxe-gold);
        color: #111;
        box-shadow: 0 10px 22px rgba(199, 167, 90,.18);
    }

    .archive-chip .mdi {
        font-size: 1rem;
    }

    .archive-sort-wrap {
        flex: 0 0 auto;
        min-width: 220px;
    }

    .archive-sort-wrap .form-select {
        height: 42px;
        border-radius: 999px;
        border: 1px solid rgba(199, 167, 90,.14);
        background-color: rgba(255,255,255,.03);
        color: #fff;
        box-shadow: none;
        font-size: .9rem;
        font-weight: 700;
        padding-left: 1rem;
        padding-right: 2.5rem;
    }

    .archive-sort-wrap .form-select:focus {
        border-color: rgba(199, 167, 90,.34);
        box-shadow: 0 0 0 .2rem rgba(199, 167, 90,.08);
    }

    .archive-sort-wrap .form-select option {
        color: #111;
    }

    .archive-grid {
        margin-bottom: 2.5rem;
    }

    .archive-card {
        height: 100%;
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid rgba(199, 167, 90,.12);
        background:
            linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02)),
            var(--luxe-surface);
        box-shadow: 0 18px 38px rgba(0,0,0,.26);
        transition: transform .22s ease, border-color .22s ease, box-shadow .22s ease;
    }

    .archive-card:hover {
        transform: translateY(-5px);
        border-color: rgba(199, 167, 90,.28);
        box-shadow: 0 24px 44px rgba(0,0,0,.34);
    }

    .archive-card-media {
        position: relative;
        aspect-ratio: 4 / 5;
        overflow: hidden;
        background: #0f0d09;
    }

    .archive-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform .45s ease, filter .25s ease, opacity .25s ease;
    }

    .archive-card:hover .archive-card-media img {
        transform: scale(1.05);
    }

    .archive-card-gradient {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0) 30%, rgba(10,8,5,.78) 100%);
        pointer-events: none;
    }

    .archive-open-badge {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 3;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .34rem .62rem;
        border-radius: 999px;
        background: #1ed760;
        color: #fff;
        font-size: .62rem;
        font-weight: 900;
        line-height: 1;
        letter-spacing: .08em;
        text-transform: uppercase;
        box-shadow: 0 8px 16px rgba(30,215,96,.18);
    }

    .archive-open-badge-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #fff;
        display: inline-block;
    }

    .archive-restricted-overlay {
        position: absolute;
        inset: 0;
        z-index: 4;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        background: rgba(20,16,8,.70);
        backdrop-filter: blur(3px);
        text-align: center;
    }

    .archive-restricted-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .7rem;
        max-width: 200px;
    }

    .archive-lock-icon {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: rgba(199, 167, 90,.12);
        border: 1px solid rgba(199, 167, 90,.24);
        color: var(--luxe-gold);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
    }

    .archive-lock-label {
        color: var(--luxe-gold);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .archive-unlock-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: .6rem .95rem;
        border-radius: 999px;
        border: 1px solid var(--luxe-gold);
        background: rgba(199, 167, 90,.10);
        color: var(--luxe-gold);
        font-size: .76rem;
        font-weight: 800;
        text-decoration: none;
        transition: .2s ease;
    }

    .archive-unlock-btn:hover {
        background: var(--luxe-gold);
        color: #111;
    }

    .archive-card.is-locked .archive-card-media img {
        filter: grayscale(1) blur(1px);
        opacity: .48;
        transform: none !important;
    }

    .archive-card-body {
        padding: 1.15rem 1.1rem 1.2rem;
    }

    .archive-card-title {
        color: #fff;
        font-size: 1.16rem;
        font-weight: 800;
        line-height: 1.2;
        margin: 0 0 .35rem;
        letter-spacing: -.02em;
    }

    .archive-card-subtitle {
        color: var(--luxe-text-soft);
        font-size: .88rem;
        line-height: 1.55;
        margin: 0;
    }

    .archive-card.is-locked .archive-card-title,
    .archive-card.is-locked .archive-card-subtitle {
        opacity: .55;
    }

    .archive-load-more-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .9rem;
        padding-top: .5rem;
        padding-bottom: 1rem;
    }

    .archive-load-more-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .55rem;
        min-height: 54px;
        padding: .9rem 1.65rem;
        border-radius: 999px;
        border: 1px solid rgba(199, 167, 90,.18);
        background: rgba(255,255,255,.03);
        color: #fff;
        font-size: .95rem;
        font-weight: 800;
        text-decoration: none;
        transition: .2s ease;
    }

    .archive-load-more-btn:hover {
        border-color: rgba(199, 167, 90,.36);
        color: var(--luxe-gold);
        background: rgba(199, 167, 90,.06);
    }

    .archive-result-note {
        color: var(--luxe-text-soft);
        font-size: .88rem;
        text-align: center;
    }

    .archive-empty-state {
        border: 1px dashed rgba(199, 167, 90,.18);
        border-radius: 24px;
        padding: 2.5rem 1.5rem;
        background: rgba(255,255,255,.02);
        text-align: center;
        color: var(--luxe-text-soft);
    }

    @media (max-width: 1199.98px) {
        .archive-toolbar-wrap {
            top: 72px;
        }
    }

    @media (max-width: 991.98px) {
        .archive-toolbar {
            padding: .85rem;
        }

        .archive-sort-wrap {
            min-width: 100%;
        }
    }

    @media (max-width: 575.98px) {
        .archive-page-top {
            margin-bottom: 1.15rem;
        }

        .archive-toolbar-wrap {
            top: 68px;
            margin-bottom: 1.35rem;
        }

        .archive-card-body {
            padding: 1rem;
        }

        .archive-card-title {
            font-size: 1.05rem;
        }

        .archive-page-subtitle {
            font-size: .95rem;
        }
    }
</style>
@endpush
