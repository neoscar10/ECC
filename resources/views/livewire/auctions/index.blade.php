@php
  $liveAuctionItems = collect($lots ?? [])->filter(fn($l) => $l['is_star_lot'] ?? false);
  $listingItems = collect($lots ?? []);
  $isLiveTab = ($activeTab ?? 'live') === 'live';
  $isUpcomingTab = ($activeTab ?? 'live') === 'upcoming';
@endphp

<div class="ecc-auctions-page">
    {{-- SECTION 1: LIVE AUCTIONS (Using Star Lots for Rail) --}}
    @include('livewire.auctions.partials.index.live-rail')

    {{-- SECTION 2: EXPLORE COLLECTIONS --}}
    @include('livewire.auctions.partials.index.explore-catalog')

    {{-- Premium Access Upgrade Modal --}}
    @include('components.shared.premium-access-modal')
</div>

@push('scripts')
@include('livewire.auctions.partials.index.scripts')
@endpush
