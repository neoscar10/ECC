@php
    $content = $block; 
    $media = $content['media'] ?? [];
    $access = $content['access'] ?? [];
    $textPos = $content['text_position'] ?? 'below'; 
    $hasDetail = $content['has_detail_page'] ?? false;
    $id = $content['id'] ?? uniqid();
@endphp

<div class="cms-fade-in">
    <x-cms.partials.access-gate :access="$access">
        <section class="ecc-hero-block position-relative overflow-hidden mb-5">
            <div class="ecc-hero-bg">
                <img src="{{ $media['image_url'] ?? asset('images/placeholder.jpg') }}"
                     alt="{{ $content['title'] }}"
                     class="w-100 h-100 object-fit-cover">
            </div>

            <div class="ecc-hero-overlay"></div>

            <div class="ecc-hero-content position-relative">
                @if(!empty($content['badge_text']))
                    <div class="ecc-hero-badge">
                        <i class="mdi mdi-star-four-points text-gold"></i>
                        <span>{{ $content['badge_text'] }}</span>
                    </div>
                @endif

                <h1 class="ecc-hero-title mb-3">
                    @php
                        $title = $content['title'];
                        // Premium formatting: italicize last word if it's multiple words
                        $words = explode(' ', $title);
                        if(count($words) > 1) {
                            $last = array_pop($words);
                            $formattedTitle = implode(' ', $words) . ' <span class="ecc-text-italic ecc-text-gold">' . $last . '</span>';
                        } else {
                            $formattedTitle = $title;
                        }
                    @endphp
                    {!! $formattedTitle !!}
                </h1>

                @if(!empty($content['subtitle']))
                    <p class="ecc-hero-text mb-4">{{ $content['subtitle'] }}</p>
                @endif

                <div class="d-flex flex-wrap gap-3 mt-2">
                    @if($hasDetail && ($content['cta_text'] ?? null))
                        <a href="{{ url('/content/blocks/' . $content['id']) }}" class="btn ecc-btn-gold px-4 px-lg-5 py-3">
                            {{ $content['cta_text'] }}
                            <i class="mdi mdi-arrow-right ms-2"></i>
                        </a>
                    @endif
                    
                    {{-- Secondary CTA fallback if configured in block (optional ECC pattern) --}}
                    @if(!empty($content['secondary_cta_url']))
                         <a href="{{ $content['secondary_cta_url'] }}" class="btn ecc-btn-glass px-4 px-lg-5 py-3">
                            {{ $content['secondary_cta_label'] ?? 'Learn More' }}
                        </a>
                    @endif
                </div>
            </div>
        </section>
    </x-cms.partials.access-gate>
</div>

<style>
    .cms-hero-wrapper {
        min-height: 320px;
    }
    @media (min-width: 768px) {
        .cms-hero-wrapper { min-height: 420px; }
    }
    @media (min-width: 992px) {
        .cms-hero-wrapper { min-height: 580px; }
    }

    /* Smart default for focal area */
    .cms-hero-wrapper img {
        object-position: center top !important;
    }

    .cms-btn-gold {
        background: linear-gradient(135deg, #D4AF37 0%, #B8961E 100%);
        color: #000;
        border: none;
        box-shadow: 0 4px 20px rgba(212,175,55,0.25);
        transition: all 0.3s ease;
    }
    .cms-btn-gold:hover {
        background: linear-gradient(135deg, #E5C05B 0%, #D4AF37 100%);
        transform: scale(1.02);
        box-shadow: 0 6px 25px rgba(212,175,55,0.35);
        color: #000;
    }

    @keyframes cmsFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cms-fade-in {
        animation: cmsFadeIn 0.8s ease forwards;
    }
</style>
