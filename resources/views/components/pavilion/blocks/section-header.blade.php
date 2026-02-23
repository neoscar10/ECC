@props(['block'])

<div class="mt-5 mb-4 px-2">
    <h3 class="ecc-outfit fw-bold text-white mb-1 fs-4">{{ $block['title'] }}</h3>
    @if($block['subtitle'])
        <p class="text-white-50 small mb-0">{{ $block['subtitle'] }}</p>
    @endif
    <div class="mt-2" style="width: 40px; height: 3px; background: var(--ecc-primary); border-radius: 2px;"></div>
</div>
