@php
    $content = $block;
    $access = $content['access'] ?? [];
    $hasDetail = $content['has_detail_page'] ?? false;
@endphp

<div class="cms-fade-in">
    <x-cms.partials.access-gate :access="$access">
        <div class="card border-0 rounded-4 overflow-hidden mb-4" style="background: #111; border: 1px solid rgba(199, 167, 90,0.1) !important; border-radius: 20px !important;">
            <div class="card-body p-4 p-md-5">
                <x-cms.partials.section-heading 
                    :title="$content['title']" 
                    :subtitle="$content['subtitle']" 
                    :badge="$content['badge_text']" 
                />
                
                @if(!empty($content['body_text']))
                    <div class="mt-4" style="line-height: 1.8; color: #FFFFFF; opacity: 0.9; font-size: 15px;">
                        {!! nl2br(e($content['body_text'])) !!}
                    </div>
                @endif

                @if($hasDetail && ($content['cta_text'] ?? null))
                    <a href="{{ url('/content/blocks/' . $content['id']) }}" class="btn cms-btn-gold rounded-pill px-5 py-2 mt-4 fw-bold">
                        {{ $content['cta_text'] }}
                    </a>
                @endif
            </div>
        </div>
    </x-cms.partials.access-gate>
</div>

{{-- Global CMS styles if not already present, but scoped here for safety --}}
@once
<style>
    .cms-btn-gold {
        background: linear-gradient(135deg, var(--ecc-primary) 0%, #B8961E 100%);
        color: #000;
        border: none;
        box-shadow: 0 4px 20px rgba(199, 167, 90,0.25);
        transition: all 0.3s ease;
    }
    .cms-btn-gold:hover {
        background: linear-gradient(135deg, #E5C05B 0%, var(--ecc-primary) 100%);
        transform: scale(1.02);
        box-shadow: 0 6px 25px rgba(199, 167, 90,0.35);
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
@endonce
