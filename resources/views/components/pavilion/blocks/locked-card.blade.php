@props(['block'])

@php
    $access = $block['access'] ?? [];
    $message = $access['message'] ?? [];
    $icon = $message['icon'] ?? 'lock';
    $imageUrl = $block['media']['image_url'] ?? null;
@endphp

<div class="pavilion-card position-relative overflow-hidden" style="min-height: 250px;">
    {{-- Blurred Background --}}
    @if($imageUrl)
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ $imageUrl }}') center/cover no-repeat; filter: blur(8px) brightness(0.4); z-index: 1;"></div>
    @else
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="z-index: 1;"></div>
    @endif
    
    {{-- Lock Overlay --}}
    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4 text-center" style="z-index: 2; background: rgba(5,5,5,0.4);">
        <span class="material-symbols-outlined text-warning mb-3" style="font-size: 48px;">{{ $icon }}</span>
        
        <span class="text-white-50 fw-bold small text-uppercase mb-2" style="letter-spacing: 2px; font-size: 10px;">
            MEMBERS ONLY CONTENT
        </span>
        
        <h4 class="ecc-outfit fw-bold text-white mb-3 fs-5">{{ $block['title'] }}</h4>
        
        @foreach($access['actions'] ?? [] as $action)
            <a href="{{ $action['deeplink'] ?? '#' }}" class="btn btn-ecc-primary btn-sm px-4 py-2">
                {{ $action['label'] }}
            </a>
        @endforeach
    </div>
</div>
