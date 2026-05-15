{{-- BREADCRUMB --}}
<nav class="auction-breadcrumb mb-4 mb-lg-5" aria-label="breadcrumb">
    <div class="d-flex flex-wrap align-items-center">
        <a href="{{ url('/') }}">Home</a>
        <span class="auction-breadcrumb-sep">
            <i class="mdi mdi-chevron-right"></i>
        </span>
        
        <a href="{{ route('auctions.index') }}">Auctions</a>
        <span class="auction-breadcrumb-sep">
            <i class="mdi mdi-chevron-right"></i>
        </span>

        <span class="ecc-text-primary">{{ $auctionTitle }}</span>
    </div>
</nav>
