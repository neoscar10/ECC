<div class="table-card mb-1">
    <table class="table align-middle" id="auctionTable">
        <thead class="table-light text-muted">
            <tr>
                <th>Lot No</th>
                <th>Item</th>
                <th>Status</th>
                <th>Current Bid</th>
                <th>Schedule</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody class="list form-check-all">
            @forelse($lots as $lot)
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
                                    <a href="{{ route('admin.auctions.detail', $lot->id) }}" class="text-reset">{{ $lot->title }}</a>
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
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a href="{{ route('admin.auctions.detail', $lot->id) }}" class="dropdown-item">
                                        <i class="ri-eye-fill align-bottom me-2 text-muted"></i> View Details
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item edit-item-btn" wire:click="edit({{ $lot->id }})">
                                        <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" wire:click="manageAttachments({{ $lot->id }})">
                                        <i class="ri-attachment-2 align-bottom me-2 text-muted"></i> Attachments
                                        @if($lot->attachments_count > 0)
                                            <span class="badge bg-success-subtle text-success ms-auto">{{ $lot->attachments_count }}</span>
                                        @endif
                                    </button>
                                </li>
                                @if(!$lot->goLiveNow && $lot->status != 'live' && $lot->early_access_enabled)
                                    <li>
                                        <button class="dropdown-item" wire:click="configureEarlyAccess({{ $lot->id }})">
                                            <i class="ri-calendar-event-fill align-bottom me-2 text-muted"></i> Early Access
                                        </button>
                                    </li>
                                @endif
                                <div class="dropdown-divider"></div>
                                <li>
                                    <button class="dropdown-item remove-item-btn" wire:click="confirmDelete({{ $lot->id }})">
                                        <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="noresult">
                            <div class="text-center">
                                <h5 class="mt-2">No lots found</h5>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-end">
    {{ $lots->links() }}
</div>
