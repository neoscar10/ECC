@props(['block'])

<div class="pavilion-card h-100">
    @if($block['media']['image_url'])
        <div style="height: 200px; background: url('{{ $block['media']['image_url'] }}') center/cover no-repeat;"></div>
    @else
        <div class="bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
            <span class="material-symbols-outlined text-white-50" style="font-size: 48px;">image</span>
        </div>
    @endif

    <div class="p-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="ecc-outfit fw-bold text-white mb-0 fs-5 text-truncate">{{ $block['title'] }}</h4>
            <span class="material-symbols-outlined text-success" style="font-size: 18px;">public</span>
        </div>
        
        @if($block['subtitle'])
            <p class="text-white-50 small mb-4 text-truncate">{{ $block['subtitle'] }}</p>
        @endif

        <div class="d-flex align-items-center justify-content-between mt-auto">
            <span class="text-white-50 fw-bold small text-uppercase" style="letter-spacing: 1px; font-size: 10px;">
                ARTIFACT #{{ str_pad($block['id'], 3, '0', STR_PAD_LEFT) }}
            </span>
            @if($block['web_detail_url'] ?? false)
                <a href="{{ $block['web_detail_url'] }}" class="btn btn-link text-warning p-0 text-decoration-none small fw-bold">
                    View Artifact →
                </a>
            @endif
        </div>
    </div>
</div>
