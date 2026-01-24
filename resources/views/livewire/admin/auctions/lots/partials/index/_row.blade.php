<tr>
    <td><span class="fw-medium">#{{ $lot->lot_no }}</span></td>
    <td>
        <div class="d-flex align-items-center">
            @php
                $img = $lot->images->sortBy('sort_order')->first();
                $p = $img ? preg_replace('#^public/#', '', str_replace('\\','/',$img->path)) : null;
            @endphp
            <div class="flex-shrink-0 me-3">
                <div class="avatar-sm bg-light rounded p-1">
                    @if($p)
                        <img src="{{ Storage::url($p) }}" class="img-fluid h-100 d-block" alt="">
                    @else
                        <div class="avatar-title bg-soft-light text-muted rounded fs-24">
                            <i class="ri-image-2-line"></i>
                        </div>
                    @endif
                </div>
            </div>
            <div>
                <h5 class="fs-14 mb-1">
                    <a href="{{ route('admin.auctions.lots.show', $lot->id) }}" class="text-reset">{{ $lot->title }}</a>
                </h5>
                <p class="text-muted mb-0">{{ Str::limit(strip_tags($lot->description), 30) }}</p>
            </div>
        </div>
    </td>
    <td>
        @if($lot->status == 'live')
             <span class="badge bg-success-subtle text-success">Live</span>
        @elseif($lot->status == 'upcoming')
             <span class="badge bg-info-subtle text-info">Upcoming</span>
        @elseif($lot->status == 'ended')
             <span class="badge bg-secondary-subtle text-secondary">Ended</span>
        @else
             <span class="badge bg-light text-muted">{{ ucfirst($lot->status) }}</span>
        @endif
    </td>
    <td>
        {{ $lot->currency }} {{ number_format($lot->current_highest_bid ?? $lot->starting_price) }}
        @if($lot->current_highest_bid)
           <small class="text-success d-block">({{ $lot->bids_count }} bids)</small>
        @else 
           <small class="text-muted d-block">No bids</small>
        @endif
    </td>
    <td>
        <div class="d-flex flex-column">
            <small class="text-muted">Start: {{ $lot->starts_at?->format('d M H:i') }}</small>
            <small class="text-muted">End: {{ $lot->ends_at?->format('d M H:i') }}</small>
        </div>
    </td>
    <td>
        @include('livewire.admin.auctions.lots.partials.index._actions', ['lot' => $lot])
    </td>
</tr>
