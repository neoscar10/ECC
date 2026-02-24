@props(['title' => null, 'subtitle' => null, 'badge' => null])

@if($title || $subtitle || $badge)
    <div {{ $attributes->merge(['class' => 'cms-section-heading mb-3']) }}>
        <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="d-flex align-items-center gap-2">
                @if($title)
                    <h4 class="mb-0 fw-bold" style="color: var(--ecc-gold, #D4AF37);">{{ $title }}</h4>
                @endif
                @if($badge)
                    <span class="badge" style="font-size: 10px; padding: 4px 10px; border: 1px solid var(--ecc-gold, #D4AF37); color: var(--ecc-gold, #D4AF37); background: transparent; letter-spacing: 0.5px;">{{ $badge }}</span>
                @endif
            </div>
        </div>
        @if($subtitle)
            <p class="mb-0 small" style="color: #FFFFFF; opacity: 0.85; font-weight: 300;">{{ $subtitle }}</p>
        @endif
    </div>
@endif
