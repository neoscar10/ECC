@props(['item', 'source' => 'shop'])

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
</style>
