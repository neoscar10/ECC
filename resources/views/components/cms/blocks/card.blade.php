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
        $formattedTitle = implode(' ', $words) . ' <span class="ecc-text-italic ecc-text-primary">' . $last . '</span>';
    } else {
        $formattedTitle = $title;
    }

    $targetUrl = null;
    if (($content['has_target'] ?? false) && !empty($content['target'])) {
        $target = $content['target'];
        $kind = $target['kind'] ?? '';
        $source = $target['source'] ?? '';
        $targetId = $target['id'] ?? null;
        
        if ($kind === 'item' && $targetId) {
            if ($source === 'shop') {
                $slug = \App\Models\Shop\ShopProduct::where('id', $targetId)->value('slug');
                if ($slug) {
                    $targetUrl = route('shop.show', ['slug' => $slug]);
                }
            } elseif ($source === 'archive') {
                $targetUrl = route('archive.products.show', ['id' => $targetId]);
            } elseif ($source === 'auctions') {
                $targetUrl = route('auctions.show', ['lot' => $targetId]);
            }
        } elseif ($kind === 'category' && $targetId) {
            if ($source === 'shop') {
                $targetUrl = route('shop.index', ['activeCategoryId' => $targetId]);
            } elseif ($source === 'archive') {
                $targetUrl = route('archive.index') . '?activeTab=' . $targetId;
            }
        }
    }
@endphp

<div class="cms-fade-in">
    <x-cms.partials.access-gate :access="$access">
        @if($targetUrl)
            <a href="{{ $targetUrl }}" class="text-decoration-none text-dark d-block">
        @endif
        <section class="cms-card-split mb-5 overflow-hidden rounded-4 border border-secondary border-opacity-25 ecc-bg-surface shadow-lg">
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
                                <i class="mdi mdi-star-four-points ecc-text-primary"></i>
                                <span>{{ $content['badge_text'] }}</span>
                            </div>
                        @endif

                        <h2 class="ecc-hero-title mb-3 fs-2 ecc-text-primary">
                            {!! $formattedTitle !!}
                        </h2>

                        @if(!empty($content['subtitle']))
                            <p class="ecc-hero-text ecc-text-muted-emphasis mb-4 fs-5">{{ $content['subtitle'] }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-3 mt-2">
                            @if($hasDetail && ($content['cta_text'] ?? null))
                                <a href="{{ $content['web_detail_url'] ?? url('/content/blocks/' . $id) }}" class="btn ecc-btn-primary px-4 px-lg-5 py-3">
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
        @if($targetUrl)
            </a>
        @endif
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
        background: rgba(199, 167, 90, 0.1);
        border: 1px solid rgba(199, 167, 90, 0.2);
        border-radius: 50px;
        color: var(--ecc-primary);
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
    .ecc-text-primary { color: var(--ecc-primary); }

    .ecc-btn-primary {
        background: linear-gradient(135deg, var(--ecc-primary) 0%, #B8961E 100%);
        color: var(--ecc-text-primary);
        border: none;
        box-shadow: 0 4px 20px rgba(199, 167, 90,0.25);
        transition: all 0.3s ease;
        font-weight: 600;
    }
    .ecc-btn-primary:hover {
        background: linear-gradient(135deg, #E5C05B 0%, var(--ecc-primary) 100%);
        transform: scale(1.02);
        box-shadow: 0 6px 25px rgba(199, 167, 90,0.35);
        color: var(--ecc-text-primary);
    }

    .ecc-btn-glass {
        background: var(--ecc-bg-input);
        backdrop-filter: blur(10px);
        border: 1px solid var(--ecc-text-primary);
        color: var(--ecc-text-primary);
        transition: all 0.3s ease;
    }
    .ecc-btn-glass:hover {
        background: var(--ecc-text-primary);
        color: var(--ecc-text-primary);
        transform: scale(1.02);
    }

    @media (max-width: 991.98px) {
        .cms-card-image-panel {
            min-height: 240px !important;
        }
    }
</style>
