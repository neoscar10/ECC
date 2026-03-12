<div class="ecc-archive-shell">
  {{-- Header --}}
  <div class="ecc-archive-topbar d-flex align-items-center justify-content-between px-3 px-md-4">
    <h1 class="ecc-archive-title mb-0">The Archive</h1>
    <button type="button" class="ecc-icon-circle" wire:click="toggleFilters" aria-label="Filters">
      <span class="material-symbols-outlined">filter_list</span>
    </button>
  </div>

  {{-- Tabs --}}
  <div class="ecc-tabs-wrap px-3 px-md-4">
    <div class="ecc-tabs hide-scrollbar d-flex gap-2">
      @foreach($tabs as $t)
        @php $on = $activeTab === $t['key']; @endphp
        <button type="button"
                class="ecc-tab {{ $on ? 'is-on' : '' }}"
                wire:click="setTab('{{ $t['key'] }}')">
          {{ $t['label'] }}
        </button>
      @endforeach
    </div>
  </div>

  {{-- Grid --}}
  <div class="px-3 px-md-4 pb-5">
    <div class="row g-3 ecc-grid-pad">
      @foreach($products as $p)
        @php
          // Handle both model objects and array-like results if service transforms
          $item = is_array($p) ? $p : $p->toArray();
          
          $id = $item['id'] ?? 0;
          $title = $item['title'] ?? '';
          
          // Re-resolve access for metadata if not pre-transformed in service
          // Actually, service returns Models. Resource usually does transformation for API.
          // For Livewire, we can resolve access here if needed, or better, reuse the logic.
          $user = auth('web')->user();
          $tier = app(\App\Services\Membership\MembershipTierResolver::class)->resolveForUser($user);
          $resolver = app(\App\Services\Archive\ArchiveAccessResolver::class);
          
          // Product model instance needed for resolver
          $productModel = is_array($p) ? \App\Models\Archive\ArchiveProduct::find($id) : $p;
          $access = $resolver->resolveProductAccess($productModel, $user, $tier);
          
          // Last-mile icon normalization
          $access['message']['icon'] = \App\Support\Archive\AccessIconNormalizer::normalize(
              $access['reason'] ?? null, 
              $access['view_mode'] ?? 'blocked'
          );

          $canView = ($access['view_mode'] === 'clear' || $access['view_mode'] === 'blur');
          $isBlurred = ($access['view_mode'] === 'blur');
          $lockType = $access['message']['icon'] ?? 'lock';
          $lockTitle = $access['message']['title'] ?? 'Restricted View';
          $lockHint = $access['message']['body'] ?? 'Membership Required';
          
          // Metadata/Subtitle from model
          $subtitle = $productModel->meta_line ?? '';

          $images = $productModel->images;
          $image = $images->first()?->image_path ? url(\Storage::url($images->first()->image_path)) : null;

          $href = route('archive.products.show', ['id' => $id]);
        @endphp

        <div class="col-6 col-md-4 col-lg-3" wire:key="archive-product-{{ $id }}">
          <div class="ecc-card-wrap h-100">
             <div class="ecc-card-media @if(!$canView || $isBlurred) cursor-pointer @endif"
                 @if(!$canView || $isBlurred) wire:click.prevent="openAccessModal({{ $id }})" @endif>
              @if($image)
                <div class="ecc-card-bg" style="background-image:url('{{ $image }}');"></div>
              @else
                <div class="ecc-card-bg ecc-card-bg--empty"></div>
              @endif

              {{-- Top-right badge --}}
              @if($access['view_mode'] === 'clear')
                <div class="ecc-badge-open">OPEN</div>
              @endif

              {{-- Locked overlays --}}
              @if(!$canView || $isBlurred)
                <div class="ecc-lock-overlay">
                  <div class="ecc-lock-icon">
                    <span class="material-symbols-outlined">
                      @if($lockType === 'time-lock') lock_clock
                      @elseif($lockType === 'diamond') diamond
                      @else lock
                      @endif
                    </span>
                  </div>
                  <div class="ecc-lock-title text-uppercase">{{ $lockTitle }}</div>
                  <div class="ecc-lock-hint">{{ $lockHint }}</div>
                </div>
              @endif

              {{-- Blur --}}
              @if($isBlurred || (!$canView))
                <div class="ecc-blur"></div>
              @endif
            </div>

            <div class="mt-2">
              <div class="ecc-item-title text-truncate">{{ $title }}</div>

              @if($canView && !$isBlurred)
                <div class="ecc-item-sub text-truncate">{{ $subtitle }}</div>
              @else
                {{-- skeleton-like lines for locked items --}}
                <div class="ecc-skel mt-2"></div>
                <div class="ecc-item-sub mt-2 text-truncate opacity-50">{{ $subtitle }}</div>
              @endif
            </div>

            @if($canView && !$isBlurred)
              <a href="{{ $href }}" class="stretched-link" aria-label="Open {{ $title }}"></a>
            @else
              {{-- For blurred/locked, prevent navigation and open the premium modal --}}
               <button type="button" class="stretched-link bg-transparent border-0 p-0 m-0 w-100 h-100 position-absolute top-0 start-0" aria-label="Unlock Access for {{ $title }}" wire:click.prevent="openAccessModal({{ $id }})"></button>
            @endif
          </div>
        </div>
      @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-5 d-flex justify-content-center">
      {{ $products->links() }}
    </div>
  </div>

  {{-- Premium Access Upgrade Modal --}}
  @include('components.shared.premium-access-modal')
</div>

@push('styles')
<style>
  /* Shell styling - using a dark-gold palette */
  .ecc-archive-shell {
    background: #0d0c07; /* Darker near-black */
    min-height: 100vh;
    color: #fceec5;
    width: 100%;
    margin: 0 auto;
  }

  .ecc-archive-topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    height: 70px;
    background: rgba(13, 12, 7, 0.95);
    backdrop-filter: blur(15px);
    border-bottom: 1px solid rgba(212, 175, 55, 0.15);
  }
  
  .ecc-archive-title {
    color: #D4AF37;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.01em;
    font-family: inherit;
  }

  .ecc-icon-circle {
    width: 44px; height: 44px;
    border-radius: 50%;
    border: 1px solid rgba(212,175,55,0.2);
    background: rgba(212,175,55,0.05);
    color: #D4AF37;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s ease;
  }
  .ecc-icon-circle:hover { 
    background: rgba(212, 175, 55, 0.15); 
    border-color: rgba(212, 175, 55, 0.4);
    transform: rotate(90deg);
  }

  .ecc-tabs-wrap {
    position: sticky;
    top: 70px;
    z-index: 40;
    background: rgba(13, 12, 7, 0.95);
    backdrop-filter: blur(15px);
    padding-top: 15px;
    padding-bottom: 15px;
  }
  
  .ecc-tabs {
    overflow-x: auto;
    padding-bottom: 5px;
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
  .ecc-tabs::-webkit-scrollbar { display: none; }

  .ecc-tab {
    height: 38px;
    padding: 0 18px;
    border-radius: 20px;
    border: 1px solid rgba(212, 175, 55, 0.15);
    background: transparent;
    color: #cbbc90;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    transition: all 0.2s ease;
  }
  .ecc-tab:hover { border-color: rgba(212, 175, 55, 0.4); color: #fceec5; }
  .ecc-tab.is-on {
    background: linear-gradient(135deg, #D4AF37 0%, #B8961E 100%);
    border-color: #D4AF37;
    color: #000;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(212, 175, 55, 0.2);
  }

  .ecc-card-wrap { position: relative; }
  .ecc-card-media {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 5;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(212, 175, 55, 0.1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    background: #080808;
  }
  
  .ecc-card-bg {
    position: absolute; inset: 0;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1);
  }
  .ecc-card-wrap:hover .ecc-card-bg { transform: scale(1.1); }
  .ecc-card-bg--empty { background: linear-gradient(135deg, #1a1a1a, #0d0d0d); }

  .ecc-badge-open {
    position: absolute;
    top: 12px; right: 12px;
    z-index: 10;
    background: #D4AF37;
    color: #000;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.1em;
    padding: 4px 10px;
    border-radius: 6px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  }

  .ecc-blur {
    position: absolute; inset: 0;
    backdrop-filter: blur(8px);
    background: rgba(13, 12, 7, 0.3);
    z-index: 12;
  }

  .ecc-lock-overlay {
    position: absolute; inset: 0;
    z-index: 15;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 15px;
  }
  
  .ecc-lock-icon {
    width: 48px; height: 48px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.8);
    border: 1px solid rgba(212, 175, 55, 0.3);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
    color: #D4AF37;
    box-shadow: 0 8px 20px rgba(0,0,0,0.5);
  }
  
  .ecc-lock-title {
    color: #fceec5;
    font-weight: 800;
    font-size: 11px;
    letter-spacing: 0.05em;
  }
  .ecc-lock-hint {
    color: #cbbc90;
    margin-top: 5px;
    font-size: 10px;
    font-weight: 600;
  }

  .ecc-item-title {
    color: #fceec5;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.3;
  }
  .ecc-item-sub {
    color: #cbbc90;
    font-size: 12px;
    margin-top: 4px;
    font-weight: 500;
  }

  .ecc-skel {
    height: 10px;
    width: 60%;
    border-radius: 5px;
    background: rgba(212, 175, 55, 0.1);
    animation: eccPulse 1.5s ease-in-out infinite;
  }
  @keyframes eccPulse {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 1; }
  }

  /* Responsive Grid Adjustments */
  @media (min-width: 992px) {
    .ecc-archive-shell {
      max-width: 1200px;
      margin: 0 auto;
    }
  }
</style>
@endpush
