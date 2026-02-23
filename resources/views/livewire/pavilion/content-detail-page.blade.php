<div class="px-2">
    {{-- Hero Section --}}
    <div class="pavilion-card mb-4 position-relative">
        @if($vm['media']['image_url'])
            <img src="{{ $vm['media']['image_url'] }}" class="w-100" style="height: 300px; object-fit: cover;" alt="{{ $vm['title'] }}">
        @else
            <div class="w-100 bg-secondary d-flex align-items-center justify-content-center" style="height: 300px;">
                <span class="material-symbols-outlined text-white-50 fs-1">image</span>
            </div>
        @endif
        
        <div class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
            @if($vm['badge'])
                <span class="badge bg-warning text-dark mb-2 fw-bold rounded-pill text-uppercase">{{ $vm['badge'] }}</span>
            @endif
            <h1 class="ecc-outfit fw-bold text-white mb-1">{{ $vm['title'] }}</h1>
            @if($vm['subtitle'])
                <p class="text-white-50 mb-0">{{ $vm['subtitle'] }}</p>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="px-2">
        @if($vm['is_locked'])
            <div class="pavilion-card p-5 text-center border-warning border-opacity-50" style="background: rgba(242, 185, 13, 0.05);">
                <span class="material-symbols-outlined text-warning mb-3" style="font-size: 48px;">{{ $vm['access']['message']['icon'] ?? 'lock' }}</span>
                <h3 class="ecc-outfit fw-bold text-white mb-2">{{ $vm['access']['message']['title'] ?? 'Access Restricted' }}</h3>
                <p class="text-muted mb-4">{{ $vm['access']['message']['body'] ?? 'This content is reserved for members.' }}</p>
                
                @foreach($vm['access']['actions'] as $action)
                    <a href="{{ $action['deeplink'] ?? '#' }}" class="btn btn-ecc-primary px-4 py-2">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        @else
            <div class="markdown-content">
                {!! $vm['body_html'] !!}
            </div>
            
            @if(empty($vm['body_html']))
                <div class="text-center py-5 text-muted">
                    <p>No further details available for this item.</p>
                </div>
            @endif
        @endif
    </div>

    {{-- Back Button --}}
    <div class="mt-5 mb-4 text-center">
        <a href="{{ route('home') }}" class="btn btn-ecc-outline btn-sm px-4">
            <span class="material-symbols-outlined align-middle me-1 fs-6">arrow_back</span>
            Back to Explore
        </a>
    </div>
</div>
