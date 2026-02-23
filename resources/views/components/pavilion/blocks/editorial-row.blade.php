@props(['block'])

<div class="pavilion-card">
    <div class="row g-0">
        <div class="col-4">
            @if($block['media']['image_url'])
                <div class="h-100 min-vh-15" style="background: url('{{ $block['media']['image_url'] }}') center/cover no-repeat; min-height: 120px;"></div>
            @else
                <div class="h-100 bg-secondary d-flex align-items-center justify-content-center" style="min-height: 120px;">
                    <span class="material-symbols-outlined text-white-50">image</span>
                </div>
            @endif
        </div>
        <div class="col-8">
            <div class="p-3 d-flex flex-column h-100">
                <div class="mb-1">
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill border border-warning border-opacity-25 py-1 px-2 text-uppercase" style="font-size: 9px; letter-spacing: 0.5px;">
                        EDITORIAL
                    </span>
                </div>
                <h5 class="ecc-outfit fw-bold text-white mb-2 fs-6 line-clamp-1">{{ $block['title'] }}</h5>
                <p class="text-white-50 small mb-3 line-clamp-2">{{ $block['subtitle'] ?? 'Read the full editorial story.' }}</p>
                
                <div class="mt-auto d-flex align-items-center justify-content-between">
                    <span class="text-white-50 x-small">
                        <span class="material-symbols-outlined align-middle" style="font-size: 12px;">schedule</span>
                        5 min read
                    </span>
                    @if($block['web_detail_url'] ?? false)
                        <a href="{{ $block['web_detail_url'] }}" class="btn btn-link text-warning p-0 text-decoration-none x-small fw-bold">
                            Read More →
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 10px; }
    .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
</style>
