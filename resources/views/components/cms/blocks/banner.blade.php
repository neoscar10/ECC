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
        <div class="cms-banner-container rounded-4 overflow-hidden position-relative @if($textPos === 'below') bg-surface @endif" style="border-radius: 20px !important; border: 1px solid rgba(212,175,55,0.1);">
            @if($textPos === 'overlay')
                {{-- Premium Hero Style --}}
                <div class="cms-hero-wrapper position-relative d-flex align-items-center">
                    {{-- Image --}}
                    <img src="{{ $media['image_url'] ?? asset('images/placeholder.jpg') }}" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0" alt="{{ $content['title'] }}" style="object-position: center;">
                    
                    {{-- Premium Soft Gradient Overlay --}}
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.1) 100%);"></div>
                    
                    {{-- Text Content --}}
                    <div class="position-relative w-100 p-4 p-md-5" style="z-index: 2; max-width: 650px;">
                        <x-cms.partials.section-heading 
                            :title="$content['title']" 
                            :subtitle="$content['subtitle']" 
                            :badge="$content['badge_text']" 
                        />
                        
                        @if($hasDetail && ($content['cta_text'] ?? null))
                            <a href="{{ url('/content/blocks/' . $content['id']) }}" class="btn cms-btn-gold rounded-pill px-5 py-3 mt-4 fw-bold">
                                {{ $content['cta_text'] }}
                            </a>
                        @endif
                    </div>
                </div>
            @else
                {{-- Premium Below Style --}}
                <div class="card border-0 rounded-4 overflow-hidden" style="background: #111; border-radius: 20px !important;">
                    <div class="position-relative overflow-hidden" style="height: 320px;">
                         <img src="{{ $media['image_url'] ?? asset('images/placeholder.jpg') }}" class="w-100 h-100 object-fit-cover" alt="{{ $content['title'] }}">
                    </div>
                    <div class="card-body p-4">
                        <x-cms.partials.section-heading 
                            :title="$content['title']" 
                            :subtitle="$content['subtitle']" 
                            :badge="$content['badge_text']" 
                        />
                        
                        @if($hasDetail && ($content['cta_text'] ?? null))
                            <a href="{{ url('/content/blocks/' . $content['id']) }}" class="btn cms-btn-gold rounded-pill px-4 mt-3 fw-bold">
                                {{ $content['cta_text'] }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
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
