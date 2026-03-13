@props(['item', 'source' => 'shop', 'isEditorial' => false, 'previewMode' => null])

@php
    $id = $item['id'] ?? null;
    $title = $item['title'] ?? ($item['name'] ?? 'Untitled');
    $image = $item['image_url'] ?? ($item['image'] ?? null);
    $price = $item['price_label'] ?? ($item['price'] ?? null);
    
    // Access Handling
    $access = $item['access'] ?? null;
    $viewMode = $access['view_mode'] ?? 'clear';
    $isNavigable = ($viewMode === 'clear');
    $isLocked = $viewMode === 'blocked';
    $isBlurred = $viewMode === 'blur';
    $icon = $access['message']['icon'] ?? 'lock';
    $lockTitle = $access['message']['title'] ?? 'Restricted View';
    $lockHint = $access['message']['body'] ?? 'Membership Required';

    // Modal Navigation payload
    $targetTierId = null;
    if (!empty($access['actions'])) {
        foreach ($access['actions'] as $action) {
            if (in_array($action['type'], ['upgrade_membership', 'purchase_membership']) && !empty($action['target_tier']['id'])) {
                $targetTierId = $action['target_tier']['id'];
                break;
            }
        }
    }
    
    // Determine Target Link
    $link = '#';
    if ($source === 'shop') {
        $link = url("/shop/products/{$id}");
    } elseif ($source === 'archive') {
        $link = url("/archive/products/{$id}");
    } elseif ($source === 'auctions') {
        $link = url("/auctions/lots/{$id}");
    }
@endphp

@php
    $isEditorial = $isEditorial ?? false;
    $isPreview = ($previewMode === 'access-step');
@endphp

@if($isEditorial)
    {{-- Editorial Card Design (Test Cricket Icons style) --}}
    @if($isNavigable)
    <a href="{{ $link }}" class="ecc-editorial-card d-block text-decoration-none h-100 overflow-hidden position-relative">
    @else
    <div role="button" tabindex="0" wire:click.prevent="openAccessModal({{ $targetTierId ?? 'null' }}, {{ json_encode($title) }}, {{ json_encode($icon) }})" class="ecc-editorial-card d-block h-100 overflow-hidden position-relative" style="cursor: pointer;">
    @endif
        <img src="{{ $image }}" alt="{{ $title }}" class="w-100 h-100 object-fit-cover">
        
        <div class="ecc-editorial-overlay"></div>

        <div class="ecc-editorial-content p-3">
            <h3 class="ecc-editorial-title mb-1" style="font-size: 1.5rem;">{{ $title }}</h3>
            @if($price)
                <div class="ecc-editorial-meta" style="font-size: 0.8rem;">{{ $price }}</div>
            @endif
        </div>

        @if($isLocked || $isBlurred)
            <div class="ecc-cms-lock-overlay">
                <div class="ecc-cms-lock-icon">
                    <span class="material-symbols-outlined">
                      @if($icon === 'time-lock') lock_clock
                      @elseif($icon === 'diamond') diamond
                      @else lock
                      @endif
                    </span>
                </div>
            </div>
        @endif
    @if($isNavigable)
    </a>
    @else
    </div>
    @endif

@else
    {{-- Auction Card Design (Signed by Legends style) --}}
    <article class="ecc-auction-card h-100">
        @if($isNavigable)
        <a href="{{ $link }}" class="text-decoration-none d-block h-100">
        @else
        <div role="button" tabindex="0" wire:click.prevent="openAccessModal({{ $targetTierId ?? 'null' }}, {{ json_encode($title) }}, {{ json_encode($icon) }})" class="text-decoration-none d-block h-100 text-start w-100 bg-transparent border-0 p-0 m-0" style="cursor: pointer;">
        @endif
            <div class="ecc-auction-card-media position-relative">
                <img src="{{ $image }}" 
                     alt="{{ $title }}" 
                     class="w-100 h-100 object-fit-cover {{ $isBlurred ? 'blur-md' : '' }}">

                @if($item['badge_text'] ?? ($item['badge'] ?? null))
                    <span class="ecc-card-badge">{{ $item['badge_text'] ?? $item['badge'] }}</span>
                @endif

                <div class="ecc-card-media-overlay"></div>

                <div class="ecc-card-bottom-meta">
                    @if(!empty($item['countdown_label']) || !empty($item['status']))
                        <div class="ecc-countdown">
                            <span class="mdi mdi-clock-outline"></span>
                            <span>{{ $item['countdown_label'] ?? strtoupper($item['status']) }}</span>
                        </div>
                    @endif

                    <span class="ecc-card-action-icon">
                        <i class="mdi {{ $source === 'auctions' ? 'mdi-gavel' : ($source === 'shop' ? 'mdi-cart-outline' : 'mdi-eye-outline') }}"></i>
                    </span>
                </div>

                @if($isLocked || $isBlurred)
                    <div class="ecc-cms-lock-overlay">
                        <div class="ecc-cms-lock-icon">
                            <span class="material-symbols-outlined">
                              @if($icon === 'time-lock') lock_clock
                              @elseif($icon === 'diamond') diamond
                              @else lock
                              @endif
                            </span>
                        </div>
                        <div class="ecc-cms-lock-title text-uppercase">{{ $lockTitle }}</div>
                        <div class="ecc-cms-lock-hint">{{ $lockHint }}</div>
                    </div>
                @endif
            </div>

            <div class="pt-2 pb-1">
                <h3 class="ecc-card-title truncate-1">{{ $title }}</h3>

                <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="ecc-card-meta-label">
                        {{ $source === 'auctions' ? 'Current Bid' : ($source === 'shop' ? 'Club Store' : 'The Archive') }}
                    </span>
                    @if($price)
                        <span class="ecc-card-meta-value">{{ $price }}</span>
                    @endif
                </div>
            </div>
        @if($isNavigable)
        </a>
        @else
        </div>
        @endif
    </article>
@endif

<style>
    .truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .cms-item-card:hover .card {
        background: #181818 !important;
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(212,175,55,0.2) !important;
        border-color: rgba(212,175,55,0.4) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .ecc-cms-lock-overlay {
        position: absolute; inset: 0;
        z-index: 15;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 15px;
        background: rgba(0,0,0,0.4);
    }
    .ecc-cms-lock-icon {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.8);
        border: 1px solid rgba(212, 175, 55, 0.3);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 8px;
        color: #D4AF37;
        box-shadow: 0 4px 12px rgba(0,0,0,0.5);
    }
    .ecc-cms-lock-title {
        color: #fceec5;
        font-weight: 800;
        font-size: 9px;
        letter-spacing: 0.05em;
    }
    .ecc-cms-lock-hint {
        color: #cbbc90;
        margin-top: 3px;
        font-size: 8px;
        font-weight: 600;
    }

    /* Shared Card Styles migrated from Home Page */
    .ecc-auction-card {
        transition: transform .3s ease;
    }

    .ecc-auction-card-media {
        aspect-ratio: {{ $isPreview ? '4/5' : '1/1' }};
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

    .ecc-card-title {
        color: #fff;
        font-size: {{ $isPreview ? '1.15rem' : '0.95rem' }};
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

    .ecc-editorial-card {
        aspect-ratio: {{ $isPreview ? '16/9' : '1/1' }};
        border-radius: 1.15rem;
        border: 1px solid rgba(212,175,55,.1);
        background: #1a140b;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
        font-size: {{ $isPreview ? '1.8rem' : '1.15rem' }};
        line-height: 1.1;
        font-style: italic;
        font-weight: 900;
        margin-bottom: .15rem;
        letter-spacing: -.01em;
    }

    .ecc-editorial-meta {
        color: #d4af37;
        font-weight: 800;
        font-size: {{ $isPreview ? '0.9rem' : '0.75rem' }};
        text-transform: uppercase;
        letter-spacing: .08em;
    }
</style>
