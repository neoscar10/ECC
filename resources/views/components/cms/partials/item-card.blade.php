@props(['item', 'source' => 'shop'])

@php
    $id = $item['id'] ?? null;
    $title = $item['title'] ?? ($item['name'] ?? 'Untitled');
    $image = $item['image_url'] ?? ($item['image'] ?? null);
    $price = $item['price_label'] ?? ($item['price'] ?? null);
    
    // Access Handling
    $access = $item['access'] ?? null;
    $viewMode = $access['view_mode'] ?? 'clear';
    $isLocked = $viewMode === 'blocked';
    $isBlurred = $viewMode === 'blur';
    $icon = $access['message']['icon'] ?? 'lock';
    
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

<a href="{{ $link }}" class="cms-item-card d-block text-decoration-none h-100">
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
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5);">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">
                        @if($icon === 'time-lock')
                            <i class="ri-time-line fs-5 text-white"></i>
                        @elseif($icon === 'diamond')
                            <i class="ri-vip-diamond-fill fs-5" style="color: var(--ecc-gold, #d4af37);"></i>
                        @else
                            <i class="ri-lock-fill fs-5 text-white"></i>
                        @endif
                    </div>
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
</a>

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
</style>
