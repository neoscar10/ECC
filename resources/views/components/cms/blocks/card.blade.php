@php
    $content = $block; 
    $media = $content['media'] ?? [];
    $access = $content['access'] ?? [];
    $hasDetail = $content['has_detail_page'] ?? false;
    $id = $content['id'] ?? uniqid();

    // Format Title ECC Style (Italicize last word)
    $title = $content['title'] ?? 'Untitled';
    $words = explode(' ', $title);
    if(count($words) > 1) {
        $last = array_pop($words);
        $formattedTitle = implode(' ', $words) . ' <span class="ecc-text-italic ecc-text-gold">' . $last . '</span>';
    } else {
        $formattedTitle = $title;
    }
@endphp

<div class="cms-fade-in">
    <x-cms.partials.access-gate :access="$access">
        <section class="cms-card-split mb-5 overflow-hidden rounded-4 border border-secondary border-opacity-25 bg-dark shadow-lg">
            <div class="row g-0 align-items-stretch">
                <!-- Image Section -->
                <div class="col-lg-6 order-1">
                    <div class="cms-card-image-panel h-100" style="min-height: 320px;">
                        <img src="{{ $media['image_url'] ?? asset('images/placeholder.jpg') }}" 
                             alt="{{ $content['title'] ?? 'Card Image' }}" 
                             class="w-100 h-100 object-fit-cover">
                    </div>
                </div>

                <!-- Text Section -->
                <div class="col-lg-6 order-2 d-flex align-items-center">
                    <div class="cms-card-content-panel p-4 p-lg-5 w-100">
                        @if(!empty($content['badge_text']))
                            <div class="ecc-hero-badge mb-3">
                                <i class="mdi mdi-star-four-points text-gold"></i>
                                <span>{{ $content['badge_text'] }}</span>
                            </div>
                        @endif

                        <h2 class="ecc-hero-title mb-3 fs-2 text-white">
                            {!! $formattedTitle !!}
                        </h2>

                        @if(!empty($content['subtitle']))
                            <p class="ecc-hero-text text-light-emphasis mb-4 fs-5">{{ $content['subtitle'] }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-3 mt-2">
                            @if($hasDetail && ($content['cta_text'] ?? null))
                                <a href="{{ $content['web_detail_url'] ?? url('/content/blocks/' . $id) }}" class="btn ecc-btn-gold px-4 px-lg-5 py-3">
                                    {{ $content['cta_text'] }}
                                    <i class="mdi mdi-arrow-right ms-2"></i>
                                </a>
                            @endif

                            @if(!empty($content['secondary_cta_url']))
                                <a href="{{ $content['secondary_cta_url'] }}" class="btn ecc-btn-glass px-4 px-lg-5 py-3">
                                    {{ $content['secondary_cta_label'] ?? 'Learn More' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </x-cms.partials.access-gate>
</div>

<style>
    .cms-card-split {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .cms-card-split:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    
    .ecc-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(212, 175, 55, 0.1);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 50px;
        color: #D4AF37;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .ecc-hero-title {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        line-height: 1.2;
    }

    .ecc-text-italic { font-style: italic; }
    .ecc-text-gold { color: #D4AF37; }

    .ecc-btn-gold {
        background: linear-gradient(135deg, #D4AF37 0%, #B8961E 100%);
        color: #000;
        border: none;
        box-shadow: 0 4px 20px rgba(212,175,55,0.25);
        transition: all 0.3s ease;
        font-weight: 600;
    }
    .ecc-btn-gold:hover {
        background: linear-gradient(135deg, #E5C05B 0%, #D4AF37 100%);
        transform: scale(1.02);
        box-shadow: 0 6px 25px rgba(212,175,55,0.35);
        color: #000;
    }

    .ecc-btn-glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        transition: all 0.3s ease;
    }
    .ecc-btn-glass:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        transform: scale(1.02);
    }

    @media (max-width: 991.98px) {
        .cms-card-image-panel {
            min-height: 240px !important;
        }
    }
</style>
