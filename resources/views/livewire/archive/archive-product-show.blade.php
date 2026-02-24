@php
  use Illuminate\Support\Str;

  $d = $detail ?? [];
  $hero = $d['hero_image_url'] ?? null;
  $title = $d['title'] ?? '';
  $kicker = $d['kicker'] ?? '';
  $chips = $d['chips'] ?? [];
  $facts = $d['facts'] ?? [];
  $sections = $d['sections'] ?? [];
  $estimate = $d['estimate']['value'] ?? null;
  $estimateLabel = $d['estimate']['label'] ?? 'Current Estimate';
  $enquireEnabled = (bool)($d['enquire']['enabled'] ?? true);
@endphp

<div class="ecc-ad ecc-ad--shell position-relative min-vh-100 overflow-hidden">
  <div class="ecc-ad__tex"></div>

  {{-- Top floating header --}}
  <header class="ecc-ad__topbar">
    <div class="d-flex align-items-center justify-content-between px-3 px-md-4 pt-4 pt-md-3 pb-3">
      <a href="{{ url('/archive') }}" class="ecc-ad__circle" aria-label="Back">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>

      {{-- Share/Bookmark icons removed per request --}}
    </div>
  </header>

  {{-- Hero --}}
  <section class="ecc-ad__hero">
    <div class="ecc-ad__hero-bg" style="background-image:url('{{ $hero }}');"></div>
    <div class="ecc-ad__hero-grad"></div>

    <div class="ecc-ad__hero-actions">
      @if(!empty($d['ctas']['view_360_url']) && $d['ctas']['view_360_url'] !== '#')
        <a class="ecc-ad__pill" href="{{ $d['ctas']['view_360_url'] }}">
          <span class="material-symbols-outlined">360</span> View 360°
        </a>
      @endif
      <button type="button" class="ecc-ad__pill" onclick="window.dispatchEvent(new CustomEvent('eccZoomOpen'))">
        <span class="material-symbols-outlined">zoom_in</span> Zoom
      </button>
    </div>
  </section>

  <main class="px-3 px-md-4 pb-5 position-relative z-2 ecc-ad__main">
    @php
      // 1. Bucket the sections
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
        
        // Match line_items / line_text -> move to chips
        if (in_array($type, ['line_items', 'line_text', 'lineText'])) {
            if ($can && is_array($content)) {
                foreach($content as $li) {
                    $l = $li['label'] ?? '';
                    $v = $li['value'] ?? '';
                    $extractedChips[] = $l ? "$l: $v" : $v;
                }
            }
            // We skip rendering these in the main loop regardless of $can to follow "no longer appear in body"
            continue;
        }

        // Match key_values / key_val / kv -> move to facts grid
        if (in_array($type, ['key_values', 'key_val', 'kv'])) {
            if ($can && is_array($content)) {
                $extractedFacts = array_merge($extractedFacts, $content);
            }
            // Skip rendering in main loop
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

      // Merge with initial system data
      $finalFactsGrid = array_merge($facts, $extractedFacts);
      $finalChipsRow = array_merge($chips, $extractedChips);
    @endphp

    {{-- Title area --}}
    <div class="ecc-ad__head">
      <div class="d-flex align-items-center gap-2 mb-2">
        <div class="ecc-ad__kicker text-uppercase">{{ $kicker }}</div>
        <div class="ecc-ad__kline"></div>
      </div>

      <h1 class="ecc-ad__title">{{ $title }}</h1>

      <div class="d-flex flex-wrap gap-2 mt-2">
        @foreach($finalChipsRow as $c)
          <div class="ecc-ad__chip">{{ $c['label'] ?? $c }}</div>
        @endforeach
      </div>
    </div>

    {{-- 2. Render Facts Grid (Origin/Material/Weight/Authentication etc) --}}
    @if(!empty($finalFactsGrid))
      <div class="row g-3 ecc-ad__facts">
        @foreach($finalFactsGrid as $f)
          <div class="col-6">
            <div class="ecc-ad__fact">
              <div class="ecc-ad__fact-l">{{ $f['label'] ?? '' }}</div>
              <div class="ecc-ad__fact-v">{{ $f['value'] ?? '' }}</div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    {{-- 3. MARKDOWN sections (Provenance, etc) --}}
    @foreach($markdownBlocks as $s)
      @php
        $st = $s['title'] ?? '';
        $acc = $s['access'] ?? ['can_view' => true];
        $can = (bool)($acc['can_view'] ?? true);
        $content = $s['content'] ?? null;
      @endphp
      <section class="ecc-ad__section">
        @if($st)
          <h2 class="ecc-ad__h2">
            <span class="material-symbols-outlined">history_edu</span> {{ $st }}
          </h2>
        @endif

        <div class="ecc-ad__mdwrap {{ $can ? '' : 'is-locked' }}">
          {!! \Illuminate\Support\Str::markdown($content ?: '') !!}

          @if(!$can)
            <div class="ecc-ad__lock">
              <span class="material-symbols-outlined ecc-ad__lock-ic">lock</span>
              <div class="ecc-ad__lock-ttl">{{ $acc['lock_title'] ?? 'Inner Circle Access' }}</div>
              <div class="ecc-ad__lock-sub">{{ $acc['lock_hint'] ?? 'Upgrade to view this section.' }}</div>
              <a href="{{ url('/membership/apply-intro') }}" class="ecc-ad__lock-btn">Unlock Access</a>
            </div>
          @endif
        </div>
      </section>
    @endforeach

    {{-- 5. DETAILED IMAGERY gallery strip --}}
    @if($galleryBlock)
      @php
        $st = $galleryBlock['title'] ?? 'Detailed Imagery';
        $acc = $galleryBlock['access'] ?? ['can_view' => true];
        $can = (bool)($acc['can_view'] ?? true);
        $content = $galleryBlock['content'] ?? [];
      @endphp
      <section class="ecc-ad__section">
        <div class="ecc-ad__subttl">{{ $st }}</div>
        <div class="ecc-ad__thumbs hide-scrollbar">
          @foreach($content as $img)
            <button type="button"
                    class="ecc-ad__thumb"
                    style="background-image:url('{{ $img['url'] ?? $img }}')"
                    onclick="window.dispatchEvent(new CustomEvent('eccGalleryOpen', { detail: { url: '{{ $img['url'] ?? $img }}' } }))">
            </button>
          @endforeach
        </div>
      </section>
    @endif

    {{-- 6. Remaining attachments (CTAs, etc) --}}
    @foreach($otherBlocks as $s)
      @php
        $type = $s['type'] ?? '';
        $st = $s['title'] ?? '';
        $acc = $s['access'] ?? ['can_view' => true];
        $can = (bool)($acc['can_view'] ?? true);
        $content = $s['content'] ?? null;
      @endphp

      @if($type === 'cta' && $content)
        <section class="ecc-ad__section">
          <div class="ecc-ad__cta-box p-4 rounded-4 border border-secondary border-opacity-10 bg-secondary bg-opacity-5">
            @if($st) <h3 class="ecc-ad__fact-v m-0 mb-2 border-0 p-0 text-uppercase fs-6">{{ $st }}</h3> @endif
            <p class="ecc-ad__lock-sub m-0 mb-3">{{ $content['body'] ?? '' }}</p>
            @if(!empty($content['button_label']))
              <a href="{{ $content['button_href'] ?? '#' }}" class="ecc-ad__lock-btn d-inline-block text-center w-auto px-4">
                {{ $content['button_label'] }}
              </a>
            @endif
          </div>
        </section>
      @endif
    @endforeach


    {{-- Final Non-Sticky Enquire Section --}}
    <div class="ecc-ad__enquire-section mt-5 pt-4 border-top border-secondary border-opacity-10">
      <div class="d-flex align-items-center gap-3">
        <div class="flex-grow-1">
          <div class="ecc-ad__est-l">{{ $estimateLabel }}</div>
          <div class="ecc-ad__est-v">{{ $estimate ?: '—' }}</div>
        </div>

        <button type="button"
                class="ecc-ad__enq btn"
                @if(!$enquireEnabled) disabled @endif>
          <span class="material-symbols-outlined">mail</span>
          Enquire Privately
        </button>
      </div>
    </div>
  </main>

  {{-- Reuse existing bottom nav component --}}
  @include('layouts.user.partials.app-bottom-nav', ['active' => 'archive'])

  {{-- Simple Zoom / Gallery overlay --}}
  <div class="ecc-ad__modal" id="eccZoom" hidden>
    <div class="ecc-ad__modal-bg" onclick="window.dispatchEvent(new CustomEvent('eccModalClose'))"></div>
    <div class="ecc-ad__modal-card">
      <button class="ecc-ad__modal-x" onclick="window.dispatchEvent(new CustomEvent('eccModalClose'))" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
      <div class="ecc-ad__modal-img" id="eccZoomImg" style="background-image:url('{{ $hero }}');"></div>
    </div>
  </div>
</div>

@push('styles')
<style>
  /* Scoped styles */
  .ecc-ad--shell{ background:#1a160d; color:#f1ecc9; max-width:1100px; margin:0 auto; padding-bottom:100px; }
  @media(max-width:767px){ .ecc-ad--shell{ max-width: 100%; } }
  .ecc-ad--shell a{ color:inherit; text-decoration:none; }
  .ecc-ad__tex{ position:absolute; inset:0; background:radial-gradient(circle at 50% 15%, rgba(242,185,13,.10), transparent 60%); pointer-events:none; }

  .ecc-ad__topbar{ position:fixed; top:0; left:0; right:0; z-index:50; background:linear-gradient(to bottom, rgba(26,22,13,.95), rgba(26,22,13,.40), transparent); }
  @media(min-width:768px){ .ecc-ad__topbar{ left: var(--ecc-sidebar-w, 260px); } }
  
  .ecc-ad__circle{ width:40px; height:40px; border-radius:9999px; display:flex; align-items:center; justify-content:center;
    background:rgba(26,22,13,.35); backdrop-filter:blur(10px); border:1px solid rgba(242,185,13,.20); color:#f2b90d;
  }

  .ecc-ad__hero{ position:relative; height:50vh; border-bottom:1px solid rgba(242,185,13,.10); }
  .ecc-ad__hero-bg{ position:absolute; inset:0; background-size:cover; background-position:center; }
  .ecc-ad__hero-grad{ position:absolute; inset:0; background:linear-gradient(to top, rgba(26,22,13,1), transparent 65%); opacity:.95; }
  .ecc-ad__hero-actions{ position:absolute; right:14px; bottom:18px; display:flex; gap:8px; }
  .ecc-ad__pill{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:9999px;
    background:rgba(26,22,13,.55); backdrop-filter:blur(10px); border:1px solid rgba(242,185,13,.30);
    color:#f2b90d; font-size:12px; font-weight:700; letter-spacing:.04em; border: 0;
  }

  .ecc-ad__main{ margin-top:-24px; position:relative; z-index:5; }
  .ecc-ad__kicker{ color:#f2b90d; font-size:11px; font-weight:800; letter-spacing:.20em; }
  .ecc-ad__kline{ height:1px; width:40px; background:rgba(242,185,13,.40); }
  .ecc-ad__title{ color:#f2b90d; font-size:34px; font-weight:700; line-height:1.1; margin: 10px 0; font-family:"Newsreader",serif; }

  .ecc-ad__chip{ 
    border:1px solid rgba(242,185,13,.50); background:rgba(242,185,13,.08); color:#f2b90d;
    padding:6px 14px; border-radius:10px; font-size:12px; font-weight:800; letter-spacing:.02em;
  }

  .ecc-ad__facts{ border-top:1px solid rgba(242,185,13,.10); border-bottom:1px solid rgba(242,185,13,.10); padding:20px 0; margin:20px 0 24px; }
  .ecc-ad__fact-l{ color:#f2b90d; font-size:9px; letter-spacing:.25em; text-transform:uppercase; font-weight:800; opacity:.8; }
  .ecc-ad__fact-v{ color:#f1ecc9; font-size:18px; font-weight:700; padding-left:12px; border-left:2px solid #f2b90d; margin-top:4px; line-height:1.2; }

  .ecc-ad__section{ margin-bottom: 34px; }
  .ecc-ad__h2{ color:#f2b90d; font-size:24px; font-style:italic; display:flex; gap:12px; align-items:center; margin-bottom:16px; font-family:"Newsreader",serif; }

  .ecc-ad__mdwrap{ position:relative; }
  .ecc-ad__mdwrap.is-locked{ filter: blur(5px); opacity:.40; pointer-events:none; user-select:none; }
  .is-locked-blur{ filter: blur(4px); opacity:.30; pointer-events:none; }
  
  .ecc-ad__lock{
    position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center;
    background:rgba(26, 22, 13, 0.7); border:1px solid rgba(242,185,13,.35);
    border-radius:14px; padding:18px; text-align:center; z-index: 20;
  }
  .ecc-ad__lock-ic{ color:#f2b90d; font-size:32px; }
  .ecc-ad__lock-ttl{ color:#f2b90d; font-weight:800; font-size:16px; margin-top:8px; }
  .ecc-ad__lock-sub{ color:#cbbc90; font-size:12px; margin:8px 0 14px; }
  .ecc-ad__lock-btn{
    width:100%; max-width:240px; padding:10px 12px; border-radius:10px;
    border:1px solid rgba(242,185,13,1); background:rgba(242,185,13,.10);
    color:#f2b90d; font-weight:900; text-transform:uppercase; letter-spacing:.10em; font-size:12px;
  }

  .ecc-ad__list-card{ border:1px solid rgba(242,185,13,.15); border-radius:14px; background:rgba(242,185,13,.03); overflow:hidden; }
  .ecc-ad__list-row{ display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-bottom:1px solid rgba(242,185,13,.10); }
  .ecc-ad__list-row:last-child{ border-bottom:0; }
  .ecc-ad__list-label{ color:#cbbc90; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.12em; }
  .ecc-ad__list-value{ color:#f1ecc9; font-size:14px; font-weight:800; }

  .ecc-ad__subttl{ color:#cbbc90; font-size:11px; letter-spacing:.20em; text-transform:uppercase; border-bottom:1px solid rgba(242,185,13,.15); padding-bottom:8px; margin-bottom:16px; font-weight:800; }
  .ecc-ad__thumbs{ display:flex; gap:10px; overflow-x:auto; padding-bottom:12px; }
  .ecc-ad__thumb{ width:115px; height:115px; border-radius:12px; background-size:cover; background-position:center; border:1px solid rgba(242,185,13,.30); flex:0 0 auto; transition: transform .2s; }
  .ecc-ad__thumb:active{ transform: scale(.95); }

  .ecc-ad__est-l{ color:#cbbc90; font-size:11px; letter-spacing:.22em; text-transform:uppercase; }
  .ecc-ad__est-v{ color:#f2b90d; font-size:20px; font-family:"Newsreader",serif; font-weight:700; }
  .ecc-ad__enq{
    height:54px; padding: 0 24px; border-radius:12px; border:0;
    background:#f2b90d; color:#1a160d !important; font-weight:900; letter-spacing:.06em;
    display:flex; align-items:center; justify-content:center; gap:8px;
    box-shadow:0 10px 24px rgba(242,185,13,.2);
  }

  .ecc-ad__modal[hidden]{ display:none !important; }
  .ecc-ad__modal{ position:fixed; inset:0; z-index:100; }
  .ecc-ad__modal-bg{ position:absolute; inset:0; background:rgba(0,0,0,.85); }
  .ecc-ad__modal-card{ position:relative; margin: 5vh auto 0; width:min(800px, calc(100% - 24px)); border-radius:16px; overflow:hidden; background:#000; }
  .ecc-ad__modal-img{ height:80vh; background-size:contain; background-position:center; background-repeat: no-repeat; }
  .ecc-ad__modal-x{ position:absolute; top:15px; right:15px; width:44px; height:44px; border-radius:50%; background:rgba(0,0,0,0.5); color:#fff; border:0; display:flex; align-items:center; justify-content:center; }
  .hide-scrollbar::-webkit-scrollbar{ display:none; }
</style>
@endpush

@push('scripts')
<script>
  window.addEventListener('eccZoomOpen', () => {
    const el = document.getElementById('eccZoom');
    if (el) el.hidden = false;
  });
  window.addEventListener('eccModalClose', () => {
    const el = document.getElementById('eccZoom');
    if (el) el.hidden = true;
  });

  // Optional: reuse same modal for gallery; open sets hero background.
  window.addEventListener('eccGalleryOpen', (e) => {
    const el = document.getElementById('eccZoom');
    if (!el) return;
    const img = el.querySelector('.ecc-ad__modal-img');
    if (img && e.detail?.url) img.style.backgroundImage = `url('${e.detail.url}')`;
    el.hidden = false;
  });
</script>
@endpush
