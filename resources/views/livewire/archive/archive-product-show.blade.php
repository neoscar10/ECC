<div class="archive-detail-page">
    @php
        $d = $detail ?? [];
        $title = $d['title'] ?? 'Archive Detail';
        $hero = $d['hero_image_url'] ?? null;
        $kicker = $d['kicker'] ?? '';
        $chips = $d['chips'] ?? [];
        $facts = $d['facts'] ?? [];
        $sections = $d['sections'] ?? [];
        $estimateValue = $d['estimate']['value'] ?? null;
        $estimateLabel = $d['estimate']['label'] ?? 'Current Estimate';
        $enquireEnabled = (bool)($d['enquire']['enabled'] ?? true);

        // Classify sections mapped to our luxury grid blocks
        $extractedFacts = [];
        $extractedChips = [];
        $markdownBlocks = [];
        $galleryBlock = null;
        $otherBlocks = []; 

        foreach($sections as $s) {
            $type = $s['type'] ?? 'markdown';
            $content = $s['content'] ?? [];
            $acc = $s['access'] ?? ['can_view' => true];
            $can = (bool)($acc['can_view'] ?? true);
            
            if (in_array($type, ['line_items', 'line_text', 'lineText'])) {
                if ($can && is_array($content)) {
                    foreach($content as $li) {
                        $l = $li['label'] ?? '';
                        $v = $li['value'] ?? '';
                        $extractedChips[] = ['label' => $l, 'value' => $v];
                    }
                }
                continue;
            }

            if (in_array($type, ['key_values', 'key_val', 'kv'])) {
                if ($can && is_array($content)) {
                    $extractedFacts = array_merge($extractedFacts, $content);
                }
                continue;
            }

            if ($type === 'markdown') {
                $markdownBlocks[] = $s;
            } elseif ($type === 'gallery') {
                $galleryBlock = $s;
            } else {
                $otherBlocks[] = $s;
            }
        }

        $finalFactsGrid = array_merge($facts, $extractedFacts);
        $finalChipsRow = array_merge($chips, $extractedChips);

        $mainImage = $hero;
        
        $galleryItems = collect();
        if ($galleryBlock && !empty($galleryBlock['content'])) {
            $galleryItems = collect($galleryBlock['content'])->filter()->values();
        }
        
        // Also add the hero as first gallery item if gallery exists but hero is separate
        $allGalleryItems = $galleryItems;
        
        $lotBadge = $kicker;
        $subtitle = '';
        
        $specifications = collect($finalFactsGrid)->filter(fn ($row) => !empty($row['label']))->values();
    @endphp

    @section('title', $title)

    {{-- MAIN CONTENT GRID (9/3 Split — mirrors shop page) --}}
    <div class="row g-3 g-xl-4">

        {{-- LEFT: GALLERY STAGE + CONTENT (9/12) --}}
        <div class="col-lg-9">

            {{-- GALLERY STAGE (Main image + desktop vertical thumbs in one card) --}}
            <div class="archive-detail-gallery-stage mb-3">

                {{-- MAIN IMAGE WRAPPER --}}
                <div class="archive-detail-stage-image-wrap">
                    @if(!empty($mainImage))
                        <img
                            src="{{ is_array($mainImage) ? ($mainImage['url'] ?? null) : $mainImage }}"
                            alt="{{ $title }}"
                            id="archiveDetailMainImage"
                        >
                    @else
                        <img
                            src="https://placehold.co/1400x900/17130b/d4af37?text=Archive+Item"
                            alt="{{ $title }}"
                            id="archiveDetailMainImage"
                        >
                    @endif

                    {{-- Prev / Next controls --}}
                    @if($allGalleryItems->count() > 1)
                        <button type="button" class="archive-detail-stage-control prev" id="archiveDetailPrevBtn" aria-label="Previous image">
                            <i class="mdi mdi-chevron-left"></i>
                        </button>
                        <button type="button" class="archive-detail-stage-control next" id="archiveDetailNextBtn" aria-label="Next image">
                            <i class="mdi mdi-chevron-right"></i>
                        </button>
                    @endif

                    {{-- Zoom button --}}
                    @if(!empty($mainImage))
                        <button type="button" class="archive-detail-stage-control zoom" id="archiveDetailZoomBtn" onclick="window.dispatchEvent(new CustomEvent('eccZoomOpen'))" aria-label="Zoom image">
                            <i class="mdi mdi-magnify-plus-outline"></i>
                        </button>
                    @endif
                </div>

                {{-- DESKTOP THUMBNAILS (Vertical rail, inside the stage card) --}}
                @if($allGalleryItems->count() > 1)
                    <div class="archive-detail-stage-thumbs d-none d-lg-flex">
                        <div class="archive-detail-thumb-rail scroll-luxury w-100" id="archiveDetailThumbsDesktop">
                            @foreach($allGalleryItems as $index => $media)
                                @php
                                    $thumbUrl = is_array($media)
                                        ? ($media['thumb_url'] ?? $media['url'] ?? null)
                                        : ($media->thumb_url ?? $media->url ?? null);
                                    $fullUrl = is_array($media)
                                        ? ($media['url'] ?? null)
                                        : ($media->url ?? null);
                                @endphp
                                <button
                                    type="button"
                                    class="archive-detail-thumb {{ $index === 0 ? 'active' : '' }}"
                                    data-index="{{ $index }}"
                                    data-full-src="{{ $fullUrl }}"
                                    aria-label="View image {{ $index + 1 }}"
                                >
                                    <img src="{{ $thumbUrl ?: $fullUrl }}" alt="{{ $title }} thumbnail {{ $index + 1 }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- MOBILE THUMBNAILS (Below stage, hidden on desktop) --}}
            @if($allGalleryItems->count() > 1)
                <div class="d-lg-none mb-3">
                    <div class="archive-detail-thumbs-mobile scroll-luxury" id="archiveDetailThumbsMobile">
                        @foreach($allGalleryItems as $index => $media)
                            @php
                                $thumbUrl = is_array($media)
                                    ? ($media['thumb_url'] ?? $media['url'] ?? null)
                                    : ($media->thumb_url ?? $media->url ?? null);
                                $fullUrl = is_array($media)
                                    ? ($media['url'] ?? null)
                                    : ($media->url ?? null);
                            @endphp
                            <button
                                type="button"
                                class="archive-detail-thumb {{ $index === 0 ? 'active' : '' }}"
                                data-index="{{ $index }}"
                                data-full-src="{{ $fullUrl }}"
                                aria-label="View image {{ $index + 1 }}"
                            >
                                <img src="{{ $thumbUrl ?: $fullUrl }}" alt="{{ $title }} thumbnail {{ $index + 1 }}">
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- PRODUCT INFO BELOW GALLERY --}}
            <div class="archive-detail-info-block mt-3">
                <header class="mb-2">
                    @if(!empty($lotBadge))
                        <span class="archive-detail-kicker">{{ $lotBadge }}</span>
                    @endif
                    <h1 class="archive-detail-title">{{ $title }}</h1>
                    @if(!empty($subtitle))
                        <p class="archive-detail-subtitle">{{ $subtitle }}</p>
                    @endif
                </header>

                <div class="archive-detail-divider mb-3"></div>

                {{-- 2-Column Split for Line Texts and Specifications --}}
                <div class="row g-3 mb-3">
                    {{-- Left Side: Line Texts --}}
                    <div class="col-md-6">
                        <div class="archive-detail-meta-chips mt-0 mb-0 d-flex flex-column align-items-start gap-2">
                            @forelse($finalChipsRow as $c)
                                @php
                                    $l = is_array($c) ? ($c['label'] ?? null) : null;
                                    $v = is_array($c) ? ($c['value'] ?? null) : $c;
                                    $displayValue = $l ? "$l: $v" : $v;
                                @endphp
                                <div class="archive-detail-chip">
                                    <i class="mdi mdi-check-decagram-outline"></i>
                                    <span>
                                        <span class="archive-detail-chip-accent">{{ $displayValue }}</span>
                                    </span>
                                </div>
                            @empty
                                <div class="archive-detail-chip" style="opacity: 0.5;">
                                    <span>No line items available.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Right Side: Specifications --}}
                    <div class="col-md-6">
                        <h2 class="archive-detail-section-title" style="font-size: 1.1rem;">Specifications</h2>

                        @if($specifications->count())
                            <div class="archive-detail-specs">
                                @foreach($specifications as $row)
                                    @php
                                        $label = is_array($row) ? ($row['label'] ?? null) : ($row->label ?? null);
                                        $value = is_array($row) ? ($row['value'] ?? null) : ($row->value ?? null);
                                    @endphp
                                    <div class="archive-detail-spec-row">
                                        <div class="archive-detail-spec-label">{{ $label }}</div>
                                        <div class="archive-detail-spec-value">{{ $value }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="archive-detail-description-card p-3">
                                <div class="archive-detail-richtext">
                                    <p class="mb-0 small">No specifications available.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="archive-detail-divider mb-3"></div>

                {{-- Markdown Blocks (Provenance / Description) --}}
                @forelse($markdownBlocks as $s)
                    @php
                        $st = $s['title'] ?? '';
                        $acc = $s['access'] ?? ['can_view' => true];
                        $can = (bool)($acc['can_view'] ?? true);
                        $content = $s['content'] ?? null;
                    @endphp
                    <div class="mb-3">
                        <div class="archive-detail-description-card">
                            @if($st)
                                <h2 class="archive-detail-section-title">{{ $st }}</h2>
                            @endif

                            <div class="archive-detail-richtext {{ $can ? '' : 'is-locked blur opacity-50 pe-none' }}">
                                {!! \Illuminate\Support\Str::markdown($content ?: '') !!}
                            </div>
                            
                            @if(!$can)
                                <div class="archive-detail-cert-card mt-3">
                                    <span class="material-symbols-outlined text-warning mb-2 fs-2">lock</span>
                                    <div class="archive-detail-cert-title">{{ $acc['lock_title'] ?? 'Inner Circle Access' }}</div>
                                    <div class="archive-detail-cert-subtitle">{{ $acc['lock_hint'] ?? 'Upgrade to view this section.' }}</div>
                                    <a href="{{ url('/membership/apply-intro') }}" class="archive-detail-outline-btn d-inline-block mt-3 px-4">Unlock Access</a>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="mb-3">
                        <div class="archive-detail-description-card">
                            <h2 class="archive-detail-section-title">Provenance</h2>
                            <div class="archive-detail-richtext">
                                <p>No narrative information available.</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- RIGHT: SIDEBAR (3/12) — Estimate card, mirrors shop Add-to-Cart position --}}
        <div class="col-lg-3">
            <div class="archive-detail-sidebar">
                {{-- ESTIMATE SIDE CARD --}}
                <div class="archive-detail-side-card">
                    <div class="archive-detail-side-kicker">{{ $estimateLabel }}</div>

                    @if(!empty($estimateValue))
                        <div class="archive-detail-side-value">{{ $estimateValue }}</div>
                    @else
                        <div class="archive-detail-side-value">—</div>
                    @endif

                    <div class="archive-detail-side-actions">
                        <button type="button" 
                                class="archive-detail-solid-soft-btn w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#enquireModal"
                                @if(!$enquireEnabled) disabled @endif>
                            Enquire Privately
                        </button>
                    </div>
                </div>

                {{-- CERT CARD --}}
                <div class="archive-detail-cert-card">
                    <i class="mdi mdi-seal-variant"></i>
                    <div class="archive-detail-cert-title">Certified Authenticity</div>
                    <div class="archive-detail-cert-subtitle">Guaranteed authentic by our experts.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enquire Modal --}}
    <div class="modal fade" id="enquireModal" tabindex="-1" aria-labelledby="enquireModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--ecc-bg-surface); border:1px solid rgba(199, 167, 90,.30); border-radius:16px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title archive-detail-title fs-4" id="enquireModalLabel" style="margin:0;">Enquire Privately</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    @if($enquirySuccess)
                        <div class="alert alert-success d-flex align-items-center gap-2" style="background: var(--ecc-bg-hover); border: 1px solid var(--ecc-success); color: var(--ecc-success); border-radius: 12px;">
                            <span class="material-symbols-outlined">check_circle</span>
                            Your private enquiry has been successfully sent to the admin.
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" class="archive-detail-outline-btn w-auto px-4" data-bs-dismiss="modal">Close</button>
                        </div>
                    @else
                        <p class="text-secondary text-start mb-4" style="font-size:14px; margin-top:0;">Send a private enquiry to the admin. You will be contacted directly using your registered details.</p>
                        
                        <form wire:submit.prevent="submitEnquiry">
                            <div class="mb-4">
                                <textarea wire:model="enquiryMessage" class="form-control" rows="3" placeholder="Add an optional message..." style="background: var(--ecc-bg-input); border:1px solid var(--ecc-border); color: var(--ecc-text-primary); border-radius:12px; box-shadow:none;"></textarea>
                                @error('enquiryMessage') <span class="text-danger small mt-1 d-block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="archive-detail-solid-soft-btn w-100 w-md-auto px-4 d-inline-flex align-items-center ms-auto justify-content-center">
                                    <span class="material-symbols-outlined me-2" wire:loading.remove wire:target="submitEnquiry">send</span>
                                    <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="submitEnquiry" role="status" aria-hidden="true"></span>
                                    Send Enquiry
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
  {{-- Simple Zoom overlay --}}
  <div class="archive-detail-page ecc-bg-page bg-opacity-75" style="position:fixed; inset:0; z-index:9999; backdrop-filter:blur(8px);" id="eccZoom" hidden>
    <div class="position-absolute" style="inset:0" onclick="window.dispatchEvent(new CustomEvent('eccModalClose'))"></div>
    <div class="position-relative" style="margin: 5vh auto 0; width:min(1000px, calc(100% - 24px)); border-radius:16px; overflow:hidden; background: var(--ecc-bg-page);">
      <button class="position-absolute d-flex align-items-center justify-content-center" style="top:15px; right:15px; width:44px; height:44px; border-radius:50%; background:rgba(0,0,0,0.5); color: var(--ecc-text-primary); border:0; z-index:100;" onclick="window.dispatchEvent(new CustomEvent('eccModalClose'))" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
      <div style="height:80vh; background-size:contain; background-position:center; background-repeat: no-repeat; background-image:url('{{ is_array($hero) ? ($hero['url'] ?? '') : $hero }}');" id="eccZoomImg"></div>
    </div>
  </div>

</div>

@push('styles')
<style>
    .archive-detail-page {
        padding-top: .25rem;
        padding-bottom: 1.5rem;
    }

    /* ── Gallery Stage (mirrors shop-detail-gallery-stage) ── */
    .archive-detail-gallery-stage {
        position: relative;
        border-radius: 22px;
        overflow: hidden;
        border: 1px solid var(--ecc-primary-border);
        background: var(--ecc-bg-surface-2);
        box-shadow: 0 12px 32px rgba(0,0,0,.3);
        display: flex;
        align-items: stretch;
        padding: 1.25rem;
        min-height: 480px;
        height: clamp(480px, 60vh, 700px);
        max-height: 700px;
    }

    .archive-detail-stage-image-wrap {
        flex-grow: 1;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        overflow: hidden;
    }

    .archive-detail-stage-image-wrap img#archiveDetailMainImage {
        width: 100%;
        height: 100%;
        max-width: 98%;
        max-height: 98%;
        object-fit: contain;
        display: block;
        margin: auto;
        transition: transform .7s ease;
    }

    .archive-detail-stage-image-wrap:hover img#archiveDetailMainImage {
        transform: scale(1.04);
    }

    /* ── Desktop thumbnail rail (inside stage card, vertical) ── */
    .archive-detail-stage-thumbs {
        flex-shrink: 0;
        width: 90px;
        height: 100%;
        padding-left: 1rem;
        display: flex;
        flex-direction: column;
    }

    .archive-detail-thumb-rail {
        display: flex;
        flex-direction: column;
        gap: .75rem;
        height: 100%;
        overflow-y: auto;
        scrollbar-width: none;
    }

    .archive-detail-thumb-rail::-webkit-scrollbar {
        display: none;
    }

    /* ── Shared thumbnail style ── */
    .archive-detail-thumb {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--ecc-primary-border);
        background: var(--ecc-bg-surface-2);
        aspect-ratio: 1 / 1;
        padding: 0;
        transition: .2s ease;
        box-shadow: 0 8px 16px rgba(0,0,0,.16);
        width: 100%;
        flex: 0 0 auto;
    }

    .archive-detail-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: .25s ease;
        opacity: .74;
    }

    .archive-detail-thumb:hover img,
    .archive-detail-thumb.active img {
        opacity: 1;
        transform: scale(1.03);
    }

    .archive-detail-thumb.active {
        border-color: var(--ecc-primary);
        box-shadow: 0 0 0 2px var(--ecc-primary-soft);
    }

    /* ── Stage controls (prev/next/zoom) ── */
    .archive-detail-stage-control {
        position: absolute;
        z-index: 4;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        border: 1px solid var(--ecc-border);
        background: var(--ecc-overlay-dark);
        color: var(--ecc-text-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: .2s ease;
        backdrop-filter: blur(8px);
    }

    .archive-detail-stage-control:hover {
        background: var(--ecc-primary);
        color: #111;
        border-color: var(--ecc-primary);
    }

    .archive-detail-stage-control.prev {
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
    }

    .archive-detail-stage-control.next {
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
    }

    .archive-detail-stage-control.zoom {
        right: 1rem;
        bottom: 1rem;
    }

    /* ── Mobile thumbnail strip ── */
    .archive-detail-thumbs-mobile {
        display: flex;
        gap: .75rem;
        overflow-x: auto;
        overflow-y: hidden;
        padding-top: .5rem;
        padding-bottom: .2rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(199, 167, 90,.45) transparent;
    }

    .archive-detail-thumbs-mobile::-webkit-scrollbar {
        height: 4px;
    }

    .archive-detail-thumbs-mobile::-webkit-scrollbar-thumb {
        background: rgba(199, 167, 90,.45);
        border-radius: 999px;
    }

    .archive-detail-thumbs-mobile .archive-detail-thumb {
        min-width: 80px;
        width: 80px;
        min-height: 80px;
        height: 80px;
        flex: 0 0 auto;
    }

    /* ── Info block ── */
    .archive-detail-kicker {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .38rem .75rem;
        border-radius: 999px;
        background: rgba(199, 167, 90,.12);
        border: 1px solid rgba(199, 167, 90,.18);
        color: var(--ecc-primary);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .1em;
        text-transform: uppercase;
        margin-bottom: .5rem;
    }

    .archive-detail-title {
        color: var(--ecc-text-primary);
        font-size: clamp(1.8rem, 3vw, 3rem);
        line-height: 1.04;
        font-weight: 900;
        letter-spacing: -.045em;
        margin: .5rem 0 .4rem;
    }

    .archive-detail-subtitle {
        color: var(--ecc-primary);
        font-size: 1rem;
        line-height: 1.6;
        font-weight: 600;
        margin: 0;
    }

    .archive-detail-divider {
        height: 1px;
        background: linear-gradient(90deg, var(--ecc-primary-border), transparent);
    }

    /* ── Chips ── */
    .archive-detail-meta-chips {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
        margin-top: .5rem;
        margin-bottom: .75rem;
    }

    .archive-detail-chip {
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        min-height: 42px;
        padding: .65rem .9rem;
        border-radius: 999px;
        background: rgba(199, 167, 90,.08);
        border: 1px solid rgba(199, 167, 90,.16);
        color: var(--ecc-text-primary);
        font-size: .84rem;
        font-weight: 800;
        line-height: 1;
    }

    .archive-detail-chip i {
        color: var(--ecc-primary);
        font-size: .95rem;
    }

    .archive-detail-chip-accent {
        color: var(--ecc-primary);
    }

    /* ── Description cards ── */
    .archive-detail-description-card,
    .archive-detail-side-card,
    .archive-detail-cert-card {
        border-radius: 22px;
        border: 1px solid rgba(199, 167, 90,.14);
        background:
            linear-gradient(180deg, var(--ecc-bg-hover), transparent),
            var(--ecc-bg-surface);
        box-shadow: 0 18px 36px rgba(0,0,0,.22);
    }

    .archive-detail-description-card {
        padding: 1.4rem;
        margin-bottom: 1rem;
    }

    .archive-detail-richtext,
    .archive-detail-richtext p,
    .archive-detail-richtext li,
    .archive-detail-richtext span {
        color: var(--ecc-text-secondary);
        line-height: 1.85;
        font-size: .96rem;
    }

    .archive-detail-richtext h1,
    .archive-detail-richtext h2,
    .archive-detail-richtext h3,
    .archive-detail-richtext h4,
    .archive-detail-richtext h5,
    .archive-detail-richtext h6 {
        color: var(--ecc-text-primary);
        font-weight: 900;
        line-height: 1.2;
        letter-spacing: -.03em;
        margin-top: 0;
        margin-bottom: .85rem;
    }

    .archive-detail-richtext h1 { font-size: 2rem; }
    .archive-detail-richtext h2 { font-size: 1.7rem; }
    .archive-detail-richtext h3 { font-size: 1.45rem; }
    .archive-detail-richtext strong { color: var(--ecc-text-primary); font-weight: 800; }
    .archive-detail-richtext ul,
    .archive-detail-richtext ol { padding-left: 1.1rem; margin-bottom: 1rem; }
    .archive-detail-richtext li { margin-bottom: .4rem; }

    .archive-detail-section-title {
        color: var(--ecc-text-primary);
        font-size: 1.5rem;
        font-weight: 900;
        letter-spacing: -.03em;
        margin: 0 0 1rem;
        display: inline-flex;
        align-items: center;
        gap: .75rem;
        text-transform: uppercase;
    }

    .archive-detail-section-title::before {
        content: "";
        display: inline-block;
        width: 4px;
        height: 24px;
        border-radius: 999px;
        background: var(--ecc-primary);
        flex: 0 0 auto;
    }

    .archive-detail-specs {
        border-radius: 14px;
        overflow: hidden;
        background: transparent;
    }

    .archive-detail-spec-row {
        display: flex;
        justify-content: space-between;
        gap: 1.5rem;
        padding: .7rem .15rem;
        border-bottom: 1px solid rgba(199, 167, 90,.10);
    }

    .archive-detail-spec-label {
        color: var(--ecc-text-secondary);
        font-size: .92rem;
        font-weight: 500;
    }

    .archive-detail-spec-value {
        color: var(--ecc-text-primary);
        font-size: .92rem;
        font-weight: 800;
        text-align: right;
    }

    /* ── Sidebar ── */
    .archive-detail-sidebar {
        position: sticky;
        top: 98px;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .archive-detail-side-card {
        padding: 1.35rem;
    }

    .archive-detail-side-kicker {
        color: var(--ecc-text-secondary);
        font-size: .7rem;
        font-weight: 900;
        letter-spacing: .16em;
        text-transform: uppercase;
        margin-bottom: .65rem;
    }

    .archive-detail-side-value {
        color: var(--ecc-primary);
        font-size: clamp(1.8rem, 2vw, 2.4rem);
        line-height: 1.15;
        font-weight: 900;
        margin-bottom: 1.1rem;
    }

    .archive-detail-side-actions {
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .archive-detail-outline-btn,
    .archive-detail-solid-soft-btn {
        min-height: 46px;
        border-radius: 999px;
        font-size: .76rem;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        transition: .2s ease;
    }

    .archive-detail-outline-btn {
        border: 1px solid rgba(199, 167, 90,.42);
        background: transparent;
        color: var(--ecc-primary);
    }

    .archive-detail-outline-btn:hover {
        background: rgba(199, 167, 90,.10);
        color: var(--ecc-primary);
        border-color: var(--ecc-primary);
    }

    .archive-detail-solid-soft-btn {
        border: 1px solid rgba(199, 167, 90,.24);
        background: rgba(199, 167, 90,.12);
        color: var(--ecc-primary);
    }

    .archive-detail-solid-soft-btn:hover {
        background: rgba(199, 167, 90,.18);
        color: var(--ecc-primary);
        border-color: rgba(199, 167, 90,.38);
    }

    .archive-detail-cert-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: .3rem;
        padding: 1.25rem;
    }

    .archive-detail-cert-card i {
        color: var(--ecc-primary);
        font-size: 1.1rem;
    }

    .archive-detail-cert-title {
        color: var(--ecc-text-primary);
        font-size: .76rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .archive-detail-cert-subtitle {
        color: var(--ecc-text-secondary);
        font-size: .72rem;
        line-height: 1.5;
    }

    /* ── Responsive ── */
    @media (max-width: 991.98px) {
        .archive-detail-gallery-stage {
            min-height: 380px;
            height: auto;
            max-height: 520px;
        }

        .archive-detail-sidebar {
            position: static;
        }
    }

    @media (max-width: 767.98px) {
        .archive-detail-gallery-stage {
            min-height: 300px;
            max-height: 420px;
            padding: 1rem;
        }

        .archive-detail-spec-row {
            flex-direction: column;
            gap: .3rem;
            padding: .75rem 0;
        }

        .archive-detail-spec-value {
            text-align: left;
        }

        .archive-detail-description-card {
            padding: 1.1rem;
        }

        .archive-detail-chip {
            min-height: 40px;
            padding: .6rem .8rem;
            font-size: .82rem;
        }
    }

    @media (min-width: 992px) {
        .archive-detail-thumb-rail {
            flex-direction: column;
            flex: 0 0 auto;
            max-height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0;
        }

        .archive-detail-thumb {
            flex: 0 0 auto;
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 10px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        function initArchiveDetailGallery() {
            const mainImage = document.getElementById('archiveDetailMainImage');
            const desktopThumbs = Array.from(document.querySelectorAll('#archiveDetailThumbsDesktop .archive-detail-thumb'));
            const mobileThumbs = Array.from(document.querySelectorAll('#archiveDetailThumbsMobile .archive-detail-thumb'));
            const allThumbs = [...desktopThumbs, ...mobileThumbs];
            const prevBtn = document.getElementById('archiveDetailPrevBtn');
            const nextBtn = document.getElementById('archiveDetailNextBtn');

            if (!mainImage || !allThumbs.length) return;

            const uniqueThumbs = [];
            const seen = new Set();

            allThumbs.forEach((btn) => {
                const index = btn.getAttribute('data-index');
                const src = btn.getAttribute('data-full-src');
                const key = `${index}::${src}`;
                if (!seen.has(key) && src) {
                    seen.add(key);
                    uniqueThumbs.push({ index: Number(index), src });
                }
            });

            if (!uniqueThumbs.length) return;

            let currentIndex = 0;

            function syncActive(index) {
                document.querySelectorAll('.archive-detail-thumb').forEach((btn) => {
                    btn.classList.toggle('active', Number(btn.getAttribute('data-index')) === index);
                });
            }

            function activate(index) {
                const safeIndex = (index + uniqueThumbs.length) % uniqueThumbs.length;
                currentIndex = safeIndex;

                const current = uniqueThumbs[safeIndex];
                if (current?.src) {
                    mainImage.setAttribute('src', current.src);
                }

                syncActive(current.index);
            }

            allThumbs.forEach((btn) => {
                btn.addEventListener('click', function () {
                    activate(Number(btn.getAttribute('data-index')));
                });
            });

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    activate(currentIndex - 1);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    activate(currentIndex + 1);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initArchiveDetailGallery);
        document.addEventListener('livewire:navigated', initArchiveDetailGallery);
        
        // Zoom functionality
        window.addEventListener('eccZoomOpen', () => {
            const el = document.getElementById('eccZoom');
            const img = document.getElementById('eccZoomImg');
            const mainImg = document.getElementById('archiveDetailMainImage');
            if (el && mainImg && img) {
                img.style.backgroundImage = `url('${mainImg.src}')`;
                el.hidden = false;
            }
        });
        window.addEventListener('eccModalClose', () => {
            const el = document.getElementById('eccZoom');
            if (el) el.hidden = true;
        });

    })();
</script>
@endpush
