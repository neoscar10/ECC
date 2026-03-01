<div class="mobile-preview-device border rounded-4 shadow-sm overflow-hidden" style="{{ $style ?? 'width: 320px; height: 600px; margin: 0 auto; position: sticky; top: 20px; background-color: var(--vz-body-bg);' }}">
    <!-- Status Bar -->
    <div class="status-bar bg-dark text-white px-3 py-1 d-flex justify-content-between align-items-center fs-10">
        <span>9:41</span>
        <div class="d-flex gap-1">
            <i class="ri-signal-wifi-fill"></i>
            <i class="ri-battery-fill"></i>
        </div>
    </div>

    <!-- App Header -->
    <div class="app-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
        <i class="ri-menu-2-line"></i>
        <span class="fw-bold">Executive Club Cricket</span>
        <i class="ri-notification-3-line"></i>
    </div>

    <!-- Content Area -->
    <div class="preview-content bg-light h-100 overflow-auto" style="padding-bottom: 60px;">
        @if(($previewMode ?? '') === 'access-step')
             {{-- Real Block Component --}}
             @if(isset($block) && $block)
                  @php 
                      if (!isset($block['id'])) $block['id'] = uniqid('preview_');
                  @endphp
                  <div wire:key="cms-preview-{{ $previewScopeId ?? 'default' }}-{{ $block['id'] }}-{{ $previewTierId ?? 'guest' }}">
                      @if($block['type'] === 'slider')
                           <x-cms.blocks.slider :block="$block" :previewMode="$previewMode" :previewScopeId="$previewScopeId ?? null" />
                      @elseif($block['type'] === 'banner' || $block['type'] === 'card')
                           <x-cms.blocks.banner :block="$block" />
                      @elseif($block['type'] === 'text')
                           <x-cms.blocks.text :block="$block" />
                      @endif
                  </div>
             @else
                  <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                       <div class="text-center mt-5">
                            <i class="ri-eye-line fs-1 mb-2 d-block opacity-50"></i>
                            <p>Preview not available</p>
                       </div>
                  </div>
             @endif
        @else
             {{-- The rough preview from step 3 --}}
             <div class="p-3">
                 @include('livewire.admin.cms.blocks.partials._builder-preview-content')
             </div>
        @endif
    </div>
</div>
