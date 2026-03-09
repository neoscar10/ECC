@php
  $active = $active ?? null;

  $mainItems = [
    ['key'=>'explore','label'=>'Explore','icon'=>'explore',      'href'=>url('/home')],
    ['key'=>'archive','label'=>'Archive','icon'=>'inventory_2',  'href'=>url('/archive')],
    ['key'=>'auctions','label'=>'Auctions','icon'=>'gavel',      'href'=>url('/auctions')],
    ['key'=>'club',    'label'=>'Club',   'icon'=>'shield_person', 'href'=>url('/club')],
    ['key'=>'shop',    'label'=>'Shop',   'icon'=>'storefront',    'href'=>url('/store')],
  ];

  $bottomItems = [
    ['key'=>'settings','label'=>'Settings','icon'=>'settings',   'href'=>url('/settings')],
  ];

  $path = trim(request()->path(), '/');

  $keyFromPath = match (true) {
    $path === '' || str_starts_with($path, 'home')                        => 'explore',
    str_starts_with($path, 'archive')                                     => 'archive',
    str_starts_with($path, 'auctions')                                    => 'auctions',
    str_starts_with($path, 'club')                                        => 'club',
    str_starts_with($path, 'store') || str_starts_with($path, 'shop')     => 'shop',
    str_starts_with($path, 'settings')                                    => 'settings',
    default                                                               => null,
  };

  $active = $active ?? $keyFromPath;

  $isOn = fn($k) => $active === $k;
@endphp

<div class="ecc-sidebar-nav h-100 d-flex flex-column py-4 px-3">
  <div class="ecc-sidebar-section-header mb-3">NAVIGATION</div>
  
  <div class="d-flex flex-column gap-2 mb-4">
    @foreach($mainItems as $it)
      @php $on = $isOn($it['key']); @endphp
      <a href="{{ $it['href'] }}" 
         class="ecc-sidebar-item d-flex align-items-center gap-3 text-decoration-none {{ $on ? 'is-active' : '' }}">
        <span class="material-symbols-outlined ecc-sidebar-icon">{{ $it['icon'] }}</span>
        <span class="ecc-sidebar-label">{{ $it['label'] }}</span>
        @if($on)
          <div class="active-indicator"></div>
        @endif
      </a>
    @endforeach
  </div>

  <div class="ecc-sidebar-section-header mb-3 mt-auto">ACCOUNT</div>
  <div class="d-flex flex-column gap-2">
    @foreach($bottomItems as $it)
      @php $on = $isOn($it['key']); @endphp
      <a href="{{ $it['href'] }}" 
         class="ecc-sidebar-item d-flex align-items-center gap-3 text-decoration-none {{ $on ? 'is-active' : '' }}">
        <span class="material-symbols-outlined ecc-sidebar-icon">{{ $it['icon'] }}</span>
        <span class="ecc-sidebar-label">{{ $it['label'] }}</span>
        @if($on)
          <div class="active-indicator"></div>
        @endif
      </a>
    @endforeach

    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="ecc-sidebar-item w-100 border-0 bg-transparent d-flex align-items-center gap-3 text-decoration-none">
        <span class="material-symbols-outlined ecc-sidebar-icon">logout</span>
        <span class="ecc-sidebar-label">Logout</span>
      </button>
    </form>
  </div>
</div>

<style>
  .ecc-sidebar-nav {
    background: #0a0a0a;
    border-right: 1px solid rgba(242, 185, 13, 0.12);
  }
  .ecc-sidebar-section-header {
    color: rgba(242, 185, 13, 0.45);
    font-size: 11px;
    letter-spacing: .15em;
    font-weight: 800;
  }
  .ecc-sidebar-item {
    position: relative;
    padding: 12px 16px;
    border-radius: 12px;
    color: rgba(242, 185, 13, 0.50);
    transition: all 200ms ease;
  }
  .ecc-sidebar-item:hover {
    background: rgba(242, 185, 13, 0.05);
    color: rgba(242, 185, 13, 0.85);
  }
  .ecc-sidebar-item.is-active {
    background: rgba(242, 185, 13, 0.10);
    color: #f2b90d;
  }
  .ecc-sidebar-icon {
    font-size: 22px;
    font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24;
  }
  .ecc-sidebar-item.is-active .ecc-sidebar-icon {
    font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    filter: drop-shadow(0 0 8px rgba(242, 185, 13, 0.35));
  }
  .ecc-sidebar-label {
    font-size: 14px;
    font-weight: 600;
    letter-spacing: .02em;
  }
  .active-indicator {
    position: absolute;
    right: 12px;
    width: 6px;
    height: 6px;
    background: #f2b90d;
    border-radius: 50%;
    box-shadow: 0 0 10px #f2b90d;
  }
</style>
