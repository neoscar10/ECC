@props(['title' => null, 'subtitle' => null, 'badge' => null])

@if($title || $subtitle || $badge)
    <div {{ $attributes->merge(['class' => 'ecc-block-header mb-4']) }}>
        <div class="d-flex align-items-center justify-content-between">
            <div>
                @if($badge)
                    <div class="ecc-hero-badge mb-2" style="transform: scale(0.8); transform-origin: left;">
                        <i class="mdi mdi-star-four-points text-gold"></i>
                        <span>{{ $badge }}</span>
                    </div>
                @endif
                @if($title)
                    <h2 class="ecc-block-title">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="ecc-block-subtitle mb-0 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
    </div>
@endif
