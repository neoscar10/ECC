@props(['block'])

@php
    $imageUrl = $block['media']['image_url'] ?? null;
@endphp

<div class="pavilion-card border-0 position-relative" style="min-height: 400px;">
    @if($imageUrl)
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: url('{{ $imageUrl }}') center/cover no-repeat; z-index: 1;"></div>
    @else
        <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark" style="z-index: 1;"></div>
    @endif
    
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(5,5,5,0.2) 0%, rgba(5,5,5,0.95) 100%); z-index: 2;"></div>

    <div class="position-absolute bottom-0 start-0 w-100 p-4" style="z-index: 3;">
        <span class="badge bg-warning text-dark mb-3 fw-bold rounded-pill px-3 py-2 text-uppercase" style="letter-spacing: 1px; font-size: 10px;">
            FEATURED STORY
        </span>
        <h2 class="ecc-outfit fw-bold text-white mb-2 fs-1">{{ $block['title'] }}</h2>
        <p class="text-white-50 mb-4 fs-6 line-clamp-2" style="max-width: 600px;">
            {{ $block['subtitle'] ?? 'Discover the latest news and stories from the club.' }}
        </p>
        
        @if($block['web_detail_url'] ?? false)
            <a href="{{ $block['web_detail_url'] }}" class="btn btn-ecc-primary px-4 py-2">
                Read Story
            </a>
        @endif
    </div>
</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
