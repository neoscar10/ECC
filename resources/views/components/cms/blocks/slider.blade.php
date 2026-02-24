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
@endphp

<div class="cms-slider-block cms-fade-in" style="margin-bottom: 120px;">
    {{-- Header --}}
    <x-cms.partials.section-heading 
        :title="$content['title']" 
        :subtitle="$content['subtitle']" 
        :badge="$content['badge_text']" 
        class="px-2 mb-4"
    />

    <x-cms.partials.access-gate :access="$access">
        <div class="cms-slider-outer position-relative px-md-4">
            <div class="swiper {{ $uniqueId }} px-2" wire:ignore>
                <div class="swiper-wrapper">
                    @if($mode === 'images')
                        {{-- Image Slides --}}
                        @foreach($slides as $slide)
                            <div class="swiper-slide h-auto">
                                <div class="cms-image-slide rounded-4 overflow-hidden position-relative shadow-sm" style="border-radius: 20px !important; border: 1px solid rgba(212,175,55,0.1);">
                                    <div class="cms-slide-media" style="aspect-ratio: 16 / 9; overflow: hidden;">
                                        <img src="{{ $slide['image_url'] }}" class="w-100 h-100 object-fit-cover" alt="{{ $slide['title'] ?? '' }}" style="object-position: center top;">
                                    </div>
                                    @if(!empty($slide['title']) || !empty($slide['subtitle']))
                                        <div class="position-absolute bottom-0 start-0 w-100 p-3 pt-5" style="background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 60%, transparent 100%);">
                                            <h6 class="fw-bold mb-1" style="color: var(--ecc-gold, #D4AF37); font-size: 15px;">{{ $slide['title'] }}</h6>
                                            <p class="text-white small mb-0" style="opacity: 0.8;">{{ $slide['subtitle'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- Item Slides (Shop/Archive/Auctions) --}}
                        @foreach($items as $item)
                            <div class="swiper-slide h-auto">
                                <x-cms.partials.item-card :item="$item" :source="$source" />
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            
            {{-- Premium Gold Navigation - Positioned Outside --}}
            <div class="swiper-button-next {{ $uniqueId }}-next d-none d-lg-flex"></div>
            <div class="swiper-button-prev {{ $uniqueId }}-prev d-none d-lg-flex"></div>
        </div>
    </x-cms.partials.access-gate>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new Swiper('.{{ $uniqueId }}', {
            slidesPerView: 1.1,
            spaceBetween: 12,
            loop: true,
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
                768: { slidesPerView: 2, spaceBetween: 16 },
                992: { slidesPerView: 3, spaceBetween: 24 }
            }
        });
    });
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
        color: var(--ecc-gold, #D4AF37);
        background: rgba(11, 11, 11, 0.8);
        width: 44px;
        height: 44px;
        border-radius: 50%;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(212,175,55,0.3);
        box-shadow: 0 0 15px rgba(212,175,55,0.15);
        transition: all 0.3s ease;
        margin-top: -22px;
        z-index: 20;
    }
    
    .cms-slider-block .swiper-button-prev { left: -5px; }
    .cms-slider-block .swiper-button-next { right: -5px; }

    .cms-slider-block .swiper-button-next:hover,
    .cms-slider-block .swiper-button-prev:hover {
        background: rgba(212, 175, 55, 0.2);
        border-color: #D4AF37;
        box-shadow: 0 0 20px rgba(212,175,55,0.4);
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
