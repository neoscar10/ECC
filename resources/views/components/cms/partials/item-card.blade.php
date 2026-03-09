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

@if($isNavigable)
<a href="{{ $link }}" class="cms-item-card d-block text-decoration-none h-100">
@else
<div role="button" tabindex="0" wire:click.prevent="openAccessModal({{ $targetTierId ?? 'null' }}, {{ json_encode($title) }}, {{ json_encode($icon) }})" class="cms-item-card d-block text-decoration-none h-100 text-start w-100 bg-transparent border-0 p-0 m-0" style="cursor: pointer; outline: none;">
@endif
    <div class="card h-100 border-0 overflow-hidden rounded-4 text-white" style="background: #111; border: 1px solid rgba(212,175,55,0.18) !important; border-radius: 18px !important;">
        {{-- Image with fixed 4:5 ratio --}}
        <div class="cms-card-media position-relative" style="padding-top: 125%; background: #080808; overflow: hidden;">
            @if($image)
                <img src="{{ $image }}" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover" alt="{{ $title }}" style="object-position: center; {{ $isBlurred ? 'filter: blur(12px); transform: scale(1.1);' : '' }}">
            @else
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center text-white-50">
                    <span class="material-symbols-outlined fs-1">image</span>
                </div>
            @endif

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
        
        {{-- Content --}}
        <div class="card-body p-3">
            <h6 class="text-white fw-bold truncate-2 mb-1" style="font-size: 13px; line-height: 1.4; height: 2.8em; min-height: 2.8em;">{{ $title }}</h6>
            @if($price)
                <div class="fw-bold" style="color: var(--ecc-gold, #D4AF37); font-size: 14px;">{{ $price }}</div>
            @endif
        </div>
    </div>
@if($isNavigable)
</a>
@else
</div>
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
        width: 48px; height: 48px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.8);
        border: 1px solid rgba(212, 175, 55, 0.3);
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 12px;
        color: #D4AF37;
        box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    }
    .ecc-cms-lock-title {
        color: #fceec5;
        font-weight: 800;
        font-size: 11px;
        letter-spacing: 0.05em;
    }
    .ecc-cms-lock-hint {
        color: #cbbc90;
        margin-top: 5px;
        font-size: 10px;
        font-weight: 600;
    }
</style>
