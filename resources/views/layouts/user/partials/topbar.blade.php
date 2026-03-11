@php
    $title = $title ?? '';
    $backUrl = $backUrl ?? null;
    $cartCount = $cartCount ?? null;
    $cartUrl = $cartUrl ?? url('/cart');
@endphp

<div class="d-flex align-items-center justify-content-between py-2" style="height:56px;">
    {{-- Left: Back --}}
    <div class="d-flex align-items-center" style="width:56px;">
        @if($backUrl)
            <a href="{{ $backUrl }}"
               class="btn btn-sm rounded-circle border-0 text-white"
               style="width:40px;height:40px; background: rgba(255,255,255,.06);">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
        @endif
    </div>

    {{-- Center: Title --}}
    <div class="flex-grow-1 text-center">
        @php
            $displayTitle = trim($title);
            if (strtolower($displayTitle) === 'home') {
                $displayTitle = 'Explore'; // Normalize Home to Explore per user example
            }
            if (!empty($displayTitle) && !str_starts_with(strtolower($displayTitle), 'the ')) {
                $displayTitle = 'The ' . $displayTitle;
            }
        @endphp
        <div class="fw-bold" style="letter-spacing:.5px; font-size: 18px;">
            {{ $displayTitle }}
        </div>
    </div>

    {{-- Right: Cart --}}
    <div class="d-flex align-items-center justify-content-end" style="width:56px;">
        <a href="{{ $cartUrl }}"
           class="position-relative btn btn-sm rounded-circle border-0 text-white"
           style="width:40px;height:40px; background: rgba(255,255,255,.06);"
           x-data="{ count: {{ (int)($cartCount ?? 0) }} }"
           @refresh-cart-badge.window="count = $event.detail.count">
            <span class="material-symbols-outlined">shopping_cart</span>

            <template x-if="count > 0">
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                      style="background: var(--ecc-primary); color:#000; font-weight:800; font-size:10px;"
                      x-text="count">
                </span>
            </template>
        </a>
    </div>
</div>
