<div class="auction-detail-page">
    @include('livewire.auctions.partials.show.styles')

    @php
        $auctionTitle = $lotPrepared->title ?? 'Untitled Lot';
        $lotNumber = $lotPrepared->lot_number ?? null;
        $highestBid = $lotPrepared->current_bid_display ?? '₹0';
        $timeLeft = $lotPrepared->time_remaining_display ?? '--';
        $biddersCount = (isset($lotPrepared->bid_history) && is_array($lotPrepared->bid_history)) ? count(array_unique(array_column($lotPrepared->bid_history, 'user_id'))) : null;
        $isLive = ($lotPrepared->status_label ?? '') === 'Live Auction';
        $currency = '₹';

        // Extract gallery items correctly mapping the available images relation sent by the controller
        $mainImage = $lotPrepared->hero_image_url ?? 'https://placehold.co/1200x900/17130b/d4af37?text=No+Image';
        
        $galleryImages = $lotPrepared->gallery_images ?? [];
        $galleryItems = count($galleryImages) > 0 ? collect($galleryImages) : collect([$mainImage]);

        $bidHistory = collect($lotPrepared->bid_history ?? []);
        $relatedLots = collect(); // If related lots are added in the future

        $userCurrentBid = null; // No direct mapping found, leaving it visually handled by input later
        
        $metaBadges = $lotPrepared->provenance_badges ?? [];
        $lotAttachments = $lotPrepared->attachments ?? [];
        $increments = $lotPrepared->suggested_increments ?? [];
        
        // Use existing state to map lock logic
        $canAutoBid = $canAutoBid ?? false;
        $hasAutoBidConfigured = $hasAutoBidConfigured ?? false;
    @endphp

    @include('livewire.auctions.partials.show.breadcrumb')

    <div class="row g-4 g-xl-5">
        @include('livewire.auctions.partials.show.left-column')

        @include('livewire.auctions.partials.show.right-column')
    </div>

    @include('livewire.auctions.partials.show.modals')
    
    @include('livewire.auctions.partials.show.scripts')
</div>
