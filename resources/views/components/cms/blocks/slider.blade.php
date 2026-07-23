@props(['block', 'previewMode' => null, 'previewScopeId' => null])
@php
    $content = $block;
    $slider = $content['slider'] ?? [];
    $media = $content['media'] ?? [];
    $access = $content['access'] ?? [];
    $mode = $slider['mode'] ?? 'category'; 
    $source = $slider['source'] ?? 'shop';
    $items = $slider['items'] ?? [];
    $slides = $media['slides'] ?? [];
    
    $uniqueId = 'swiper-' . $content['id'];
    $isPreviewStep4 = $previewMode === 'access-step';
    $scopeId = $previewScopeId ?? $uniqueId;
@endphp

@php
    $exploreAllUrl = ($this instanceof \App\Livewire\Pavilion\HomePage) ? $this->getExploreAllUrl($block) : null;
@endphp

<div class="cms-slider-block cms-fade-in" style="margin-bottom: 12px;">
    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-2 mb-2 px-2">
        <div>
            @if(!empty($content['badge_text']))
                <div class="ecc-hero-badge mb-2" style="transform: scale(0.8); transform-origin: left;">
                    <i class="mdi mdi-star-four-points ecc-text-primary"></i>
                    <span>{{ $content['badge_text'] }}</span>
                </div>
            @endif
            <h2 class="ecc-block-title">
                {{ $content['title'] }}
            </h2>

            @if(!empty($content['subtitle']))
                <p class="ecc-block-subtitle mb-0">{{ $content['subtitle'] }}</p>
            @endif
        </div>

        @if($exploreAllUrl && ($access['is_allowed'] ?? true))
            <a href="{{ $exploreAllUrl }}" class="ecc-inline-link mb-lg-1">
                Explore All
                <i class="mdi mdi-chevron-right"></i>
            </a>
        @endif
    </div>

    <x-cms.partials.access-gate :access="$access">
        @if($isPreviewStep4)
            {{-- Simplified Preview Grid for Access Step --}}
            <div class="row g-3 px-2">
                @foreach(collect($items)->take(3) as $item)
                    <div class="col-4">
                         <x-cms.partials.item-card :item="$item" :source="$source" :isEditorial="$mode === 'manual'" :previewMode="$previewMode" />
                    </div>
                @endforeach
            </div>
        @else
            <div class="cms-slider-outer position-relative px-md-2">
                {{-- Shared Swiper Container for both Manual and Category modes --}}
                <div class="swiper ecc-cms-preview-swiper {{ $uniqueId }} px-2" wire:ignore>
                    <div class="swiper-wrapper">
                        @if($mode === 'manual' || $mode === 'images')
                            {{-- Editorial / Manual Items --}}
                            @php $manualItems = $mode === 'images' ? $slides : $items; @endphp
                            @foreach($manualItems as $item)
                                <div class="swiper-slide h-auto">
                                    <x-cms.partials.item-card :item="$item" :source="$source" :isEditorial="true" :previewMode="$previewMode" />
                                </div>
                            @endforeach
                        @else
                            {{-- Category Items --}}
                            @foreach($items as $item)
                                <div class="swiper-slide h-auto">
                                    <x-cms.partials.item-card :item="$item" :source="$source" :previewMode="$previewMode" />
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                {{-- Navigation --}}
                <div class="swiper-button-next {{ $uniqueId }}-next d-none d-lg-flex"></div>
                <div class="swiper-button-prev {{ $uniqueId }}-prev d-none d-lg-flex"></div>
            </div>
        @endif
    </x-cms.partials.access-gate>
</div>

@push('scripts')
<script>
    (function() {
        const initCmsSlider_{{ str_replace('-', '_', $uniqueId) }} = function() {
            @if($isPreviewStep4)
                // Deferred to EccCmsPreviewSwiper in Livewire modal
            @else
                const container = document.querySelector('.{{ $uniqueId }}');
                if (container && !container.classList.contains('swiper-initialized')) {
                    new Swiper('.{{ $uniqueId }}', {
                        slidesPerView: 1.5,
                        spaceBetween: 8,
                        loop: true,
                        observer: true,
                        observeParents: true,
                        autoplay: {
                            delay: 4000,
                            disableOnInteraction: false,
                            pauseOnMouseEnter: true,
                        },
                        navigation: {
                            nextEl: '.{{ $uniqueId }}-next',
                            prevEl: '.{{ $uniqueId }}-prev',
                        },
                        breakpoints: {
                            768: { slidesPerView: 2.5, spaceBetween: 12 },
                            1200: { slidesPerView: 4.2, spaceBetween: 16 }
                        }
                    });
                }
            @endif
        };

        // Standard load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCmsSlider_{{ str_replace('-', '_', $uniqueId) }});
        } else {
            initCmsSlider_{{ str_replace('-', '_', $uniqueId) }}();
        }

        // Livewire v3 SPA Navigation support
        document.addEventListener('livewire:navigated', initCmsSlider_{{ str_replace('-', '_', $uniqueId) }});
    })();
</script>
@endpush

<style>
    .cms-slider-outer {
        padding: 0 10px;
    }
    @media (min-width: 992px) {
        .cms-slider-outer { padding: 0 40px; }
    }

    .cms-slider-block .swiper-button-next,
    .cms-slider-block .swiper-button-prev {
        color: var(--ecc-gold, var(--ecc-primary));
        background: rgba(11, 11, 11, 0.8);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(199, 167, 90,0.3);
        box-shadow: 0 0 15px rgba(199, 167, 90,0.15);
        transition: all 0.3s ease;
        margin-top: -22px;
        z-index: 20;
    }
    
    .cms-slider-block .swiper-button-prev { left: -5px; }
    .cms-slider-block .swiper-button-next { right: -5px; }

    .cms-slider-block .swiper-button-next:hover,
    .cms-slider-block .swiper-button-prev:hover {
        background: rgba(199, 167, 90, 0.2);
        border-color: var(--ecc-primary);
        box-shadow: 0 0 20px rgba(199, 167, 90,0.4);
        transform: scale(1.1);
    }
    .cms-slider-block .swiper-button-next:after,
    .cms-slider-block .swiper-button-prev:after {
        font-size: 18px;
        font-weight: bold;
    }

    @keyframes cmsFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cms-fade-in {
        animation: cmsFadeIn 0.8s ease forwards;
    }
</style>
