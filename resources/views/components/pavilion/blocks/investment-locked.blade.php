@props(['block'])

@php
    $access = $block['access'] ?? [];
    $message = $access['message'] ?? [];
@endphp

<div class="pavilion-card position-relative overflow-hidden" style="min-height: 200px; background: linear-gradient(135deg, #1e1e1e 0%, #121212 100%);">
    {{-- Decorative Watermark --}}
    <div class="position-absolute end-0 top-0 opacity-10 p-4">
        <span class="material-symbols-outlined" style="font-size: 120px; color: var(--ecc-primary);">account_balance</span>
    </div>

    <div class="p-4 d-flex flex-column align-items-center justify-content-center h-100 text-center position-relative" style="z-index: 2;">
        <span class="material-symbols-outlined text-warning mb-3" style="font-size: 40px;">lock_clock</span>
        
        <h4 class="ecc-outfit fw-bold text-white mb-1 fs-5">Investment Vault</h4>
        <p class="text-warning small fw-bold mb-3 text-uppercase" style="letter-spacing: 1px;">Reserved for Investors</p>
        
        <p class="text-white-50 small mb-4 px-4">
            Access financial insights and exclusive investment opportunities.
        </p>

        @foreach($access['actions'] ?? [] as $action)
            <a href="{{ $action['deeplink'] ?? '#' }}" class="btn btn-ecc-outline btn-sm px-4">
                {{ $action['label'] }}
            </a>
        @endforeach
    </div>
</div>
