<div class="px-2">
    {{-- Hero Section --}}
    <div class="cms-detail-hero mb-4 position-relative rounded-4 overflow-hidden shadow-sm" style="background: var(--ecc-surface); min-height: 300px;">
        @if($block['media']['image_url'] ?? null)
            <img src="{{ $block['media']['image_url'] }}" class="w-100 position-absolute top-0 start-0 h-100 object-fit-cover" alt="{{ $block['title'] }}">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 60%, transparent 100%);"></div>
        @else
            <div class="w-100 h-100 bg-secondary d-flex align-items-center justify-content-center position-absolute top-0 start-0">
                <span class="material-symbols-outlined text-white-50 fs-1">image</span>
            </div>
        @endif
        
        <div class="position-absolute bottom-0 start-0 w-100 p-4">
            @if($block['badge_text'] ?? null)
                <span class="badge bg-primary text-uppercase mb-2 fw-bold px-3 py-2 rounded-pill" style="font-size: 11px;">{{ $block['badge_text'] }}</span>
            @endif
            <h1 class="text-white fw-bold mb-1">{{ $block['title'] }}</h1>
            @if($block['subtitle'] ?? null)
                <p class="text-white-50 mb-0">{{ $block['subtitle'] }}</p>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="px-2">
        @if(!($block['access']['is_allowed'] ?? false))
            {{-- This case is usually handled by access gate but for a dedicated page we show it prominently --}}
            <div class="card border-0 rounded-4 p-5 text-center shadow-lg" style="background: var(--ecc-surface);">
                <span class="material-symbols-outlined text-primary mb-3" style="font-size: 64px;">{{ $block['access']['message']['icon'] ?? 'lock' }}</span>
                <h2 class="fw-bold text-white mb-2">{{ $block['access']['message']['title'] ?? 'Access Restricted' }}</h2>
                <p class="text-muted mb-4 fs-5">{{ $block['access']['message']['body'] ?? 'This content is reserved for members.' }}</p>
                
                <div class="d-flex justify-content-center gap-3 mt-2">
                    @foreach($block['access']['actions'] as $action)
                        <a href="{{ $action['deeplink'] ?? '#' }}" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-bold">
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <div class="cms-markdown-content text-white-50" style="line-height: 1.8; font-size: 16px;">
                @if(!empty($bodyHtml))
                    {!! $bodyHtml !!}
                @elseif(!empty($block['body_text'] ?? null))
                     {!! nl2br(e($block['body_text'])) !!}
                @else
                    <div class="text-center py-5">
                        <p>Detailed content for this block will be available soon.</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Back Button --}}
    <div class="mt-5 mb-5 text-center">
        <a href="{{ route('home') }}" class="btn btn-outline-light rounded-pill btn-sm px-4 py-2 d-inline-flex align-items-center gap-2">
            <span class="material-symbols-outlined fs-6">arrow_back</span>
            Back to Home
        </a>
    </div>
    <style>
        .cms-markdown-content h1, .cms-markdown-content h2, .cms-markdown-content h3 {
            color: white;
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        .cms-markdown-content p {
            margin-bottom: 1.5rem;
        }
        .cms-markdown-content img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            margin: 2rem 0;
        }
        .cms-markdown-content ul, .cms-markdown-content ol {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }
        .cms-markdown-content li {
            margin-bottom: 0.5rem;
        }
    </style>
</div>
