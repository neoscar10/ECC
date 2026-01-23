<div class="row">
    <!-- Header -->
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Auction Lot Details</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.auctions.index') }}">Auctions</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.auctions.index') }}">Auction Lots</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Hero Card (Image + Status) -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">{{ $lot->title }}</h5>
                         <p class="text-muted mb-0">
                            @if($lot->status == 'upcoming')
                                Starts: {{ $lot->starts_at?->format('d M Y, h:i A') }}
                            @elseif($lot->status == 'live')
                                Ends: {{ $lot->ends_at?->format('d M Y, h:i A') }}
                            @else
                                Ended: {{ $lot->ends_at?->format('d M Y, h:i A') }}
                            @endif
                         </p>
                    </div>
                    <div>
                         @if($lot->status == 'live')
                             <span class="badge bg-success fs-12">LIVE</span>
                        @elseif($lot->status == 'upcoming')
                             <span class="badge bg-info fs-12">UPCOMING</span>
                        @elseif($lot->status == 'ended')
                             <span class="badge bg-secondary fs-12">ENDED</span>
                        @elseif($lot->status == 'cancelled')
                             <span class="badge bg-danger fs-12">CANCELLED</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 text-center">
                         @php
                            $mainImg = $lot->images->sortBy('sort_order')->first();
                            $path = $mainImg ? preg_replace('#^public/#', '', str_replace('\\','/',$mainImg->path)) : null;
                        @endphp
                        <div class="bg-light rounded p-4 mb-3" style="height: 350px;">
                            @if($path)
                                <img src="{{ Storage::url($path) }}" class="img-fluid h-100 object-fit-contain" alt="Main Image">
                            @else
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">No Main Image</div>
                            @endif
                        </div>
                        
                        <!-- Thumbnails -->
                        @if($lot->images->count() > 1)
                            <div class="d-flex gap-2 justify-content-center overflow-auto py-2">
                                @foreach($lot->images as $img)
                                    @php $subPath = preg_replace('#^public/#', '', str_replace('\\','/',$img->path)); @endphp
                                    <div class="border rounded p-1" style="width: 60px; height: 60px; cursor: pointer;">
                                         <img src="{{ Storage::url($subPath) }}" class="img-fluid h-100 object-fit-contain">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Auction Details -->
        <div class="card">
             <div class="card-header">
                <h5 class="card-title mb-0">Auction Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-borderless table-sm">
                                <tbody>
                                    <tr><td class="text-muted">Status</td><td class="fw-medium">{{ ucfirst($lot->status) }}</td></tr>
                                    <tr><td class="text-muted">Starting Price</td><td class="fw-medium">{{ $lot->currency }} {{ number_format($lot->starting_price) }}</td></tr>
                                    <tr><td class="text-muted">Reserve Price</td><td class="fw-medium">{{ $lot->min_selling_price ? $lot->currency . ' ' . number_format($lot->min_selling_price) : '-' }}</td></tr>
                                    <tr><td class="text-muted">Min Increment</td><td class="fw-medium">{{ $lot->currency }} {{ number_format($lot->min_increment) }}</td></tr>
                                    <tr><td class="text-muted">Start Time</td><td class="fw-medium">{{ $lot->starts_at?->format('d M H:i') }}</td></tr>
                                    <tr><td class="text-muted">End Time</td><td class="fw-medium">{{ $lot->ends_at?->format('d M H:i') }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <h6 class="fs-13 text-uppercase text-muted mb-2">Access Settings</h6>
                         <div class="mb-3">
                             <div class="d-flex align-items-center mb-1">
                                 <i class="ri-shield-user-line me-2"></i> Mode: 
                                 <span class="fw-bold ms-1">{{ $this->accessSummary }}</span>
                             </div>
                             @if($lot->restriction_mode == 'restricted')
                                 <div class="d-flex flex-wrap gap-1 mt-1">
                                     @foreach($lot->visibilityTiers as $tier)
                                         <span class="badge bg-primary-subtle text-primary">{{ $tier->name }}</span>
                                     @endforeach
                                 </div>
                             @endif
                         </div>
                         
                         <h6 class="fs-13 text-uppercase text-muted mb-2">Anti-Sniping</h6>
                         <p class="mb-1 fs-13"><span class="text-muted">Enabled:</span> {{ $lot->anti_sniping_enabled ? 'Yes' : 'No' }}</p>
                         @if($lot->anti_sniping_enabled)
                             <p class="mb-1 fs-13"><span class="text-muted">Window:</span> {{ $lot->trigger_window_seconds }}s</p>
                             <p class="mb-1 fs-13"><span class="text-muted">Extend:</span> {{ $lot->extend_by_seconds }}s</p>
                             <p class="mb-0 fs-13"><span class="text-muted">Max:</span> {{ $lot->max_extensions }} (Used: {{ $lot->extensions_used ?? 0 }})</p>
                         @endif
                    </div>
                </div>
                
                @if($lot->earlyAccessWindows->isNotEmpty())
                    <hr>
                    <h6 class="fs-13 text-uppercase text-muted mb-2">Early Access Windows</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Tier</th><th>Access Starts</th></tr></thead>
                            <tbody>
                                @foreach($lot->earlyAccessWindows as $ea)
                                    <tr>
                                        <td>{{ $ea->tier->name }}</td>
                                        <td>{{ $ea->access_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Activity Timeline</h5></div>
            <div class="card-body">
                <div class="acitivity-timeline py-3" style="max-height: 400px; overflow-y: auto;">
                    @forelse($timelineEvents as $event)
                        <div class="acitivity-item d-flex">
                            <div class="flex-shrink-0">
                                <i class="ri-checkbox-circle-fill text-success fs-16 align-middle"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</h6>
                                <p class="text-muted mb-1 fs-12">
                                    By {{ $event->actor_type ?? 'System' }} 
                                    @if(!empty($event->payload)) 
                                        - <small class="text-muted">{{ Str::limit(json_encode($event->payload), 60) }}</small>
                                    @endif
                                </p>
                                <small class="mb-0 text-muted">{{ $event->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">No activity recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Summary / Controls -->
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                     <h6 class="text-uppercase text-muted mb-2">Highest Bid</h6>
                     <h2 class="text-success mb-3">{{ $lot->currency }} {{ number_format($lot->current_highest_bid ?? $lot->starting_price) }}</h2>
                     @if($lot->status == 'live')
                        <h5 class="text-danger mb-0" id="countdown_timer">--:--:--</h5>
                        <small class="text-muted">Time Remaining</small>
                     @else
                        <h5 class="text-muted">{{ UCFIRST($lot->status) }}</h5>
                     @endif
                </div>
                
                <div class="d-flex justify-content-between border-top border-bottom py-3 mb-4">
                     <div class="text-center">
                         <h5 class="fs-15 mb-0">{{ $bidCount }}</h5>
                         <p class="text-muted mb-0">Bids</p>
                     </div>
                     <div class="text-center">
                         <h5 class="fs-15 mb-0">{{ $lot->images->count() }}</h5>
                         <p class="text-muted mb-0">Images</p>
                     </div>
                     <div class="text-center">
                         <h5 class="fs-15 mb-0">{{ $lot->attachments_count ?? 0 }}</h5>
                         <p class="text-muted mb-0">Docs</p>
                     </div>
                </div>

                <div class="d-grid gap-2">
                     <a href="{{ route('admin.auctions.index') }}" class="btn btn-soft-dark waves-effect waves-light">Back to List</a>
                     
                     <!-- Edit redirects to Index with Edit Modal trigger seems simpler? 
                          Or we use Query Param ?action=edit. 
                          For now, I'll link to index and user can click edit there, OR use a direct link like this if I implemented query param handler in Index.
                          Since I haven't implemented query handler, I'll rely on the user navigating or I can try to trigger it.
                          Actually, requirement said "Edit remains existing edit modal". 
                          Let's just make it a link to index for now or a button that says "Go to Index to Edit".
                          Better: Link to Index with a hash? No.
                          Let's keep it simple: Link to Index.
                      -->
                     <a href="{{ route('admin.auctions.index') }}" class="btn btn-soft-primary waves-effect waves-light">Edit Details</a>

                     @if($lot->status == 'live')
                        <button class="btn btn-soft-warning waves-effect waves-light" wire:click="$set('showExtendModal', true)">Extend Auction</button>
                        <button class="btn btn-soft-danger waves-effect waves-light" wire:click="cancelAuction" wire:confirm="Are you sure you want to cancel this auction? This cannot be undone.">Cancel Auction</button>
                     @endif
                </div>
            </div>
        </div>

        <!-- Bid List -->
        <div class="card" style="min-height: 400px;">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">Last 10 Bids</h4>
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-soft-info btn-sm" wire:click="openBidsModal">View All</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-nowrap table-sm align-middle mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col">User</th>
                                <th scope="col" class="text-end">Amount</th>
                                <th scope="col" class="text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lastBids as $bid)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="fs-13 mb-0">{{ $bid->user->name }}</h6>
                                                @if($bid->is_auto) <span class="badge bg-info-subtle text-info fs-10">Auto</span> @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-medium">{{ $lot->currency }} {{ number_format($bid->amount) }}</td>
                                    <td class="text-end text-muted fs-11" title="{{ $bid->placed_at }}">{{ $bid->placed_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No bids placed yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <!-- Extend Modal -->
    <div class="modal fade @if($showExtendModal) show @endif" id="extendModal" tabindex="-1" @if($showExtendModal) style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" @endif>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Extend Auction</h5>
                    <button type="button" class="btn-close" wire:click="$set('showExtendModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Extend By (Minutes)</label>
                        <input type="number" class="form-control" wire:model="extendMinutes" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="$set('showExtendModal', false)">Cancel</button>
                    <button type="button" class="btn btn-warning" wire:click="extendAuction">Confirm Extension</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- All Bids Modal -->
    <div class="modal fade @if($showBidsModal) show @endif" id="bidsModal" tabindex="-1" @if($showBidsModal) style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog" @endif>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bid History</h5>
                    <button type="button" class="btn-close" wire:click="closeBidsModal"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-striped table-hover mb-0 sticky-top-header">
                         <thead class="table-light">
                             <tr>
                                 <th>User</th>
                                 <th>Amount</th>
                                 <th>Type</th>
                                 <th>Placed At</th>
                             </tr>
                         </thead>
                         <tbody>
                             @foreach($allBids as $bid)
                                 <tr>
                                     <td>{{ $bid->user->name }} <br> <small class="text-muted">{{ $bid->user->email }}</small></td>
                                     <td class="fw-bold">{{ $lot->currency }} {{ number_format($bid->amount) }}</td>
                                     <td>
                                         @if($bid->is_auto) <span class="badge bg-secondary">Auto</span> @else <span class="badge bg-light text-dark">Manual</span> @endif
                                     </td>
                                     <td>{{ $bid->placed_at->format('d M Y, H:i:s') }}</td>
                                 </tr>
                             @endforeach
                         </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="closeBidsModal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    @if($lot->status == 'live' && $lot->ends_at)
    setInterval(function() {
        var end = new Date("{{ $lot->ends_at->toIso8601String() }}").getTime();
        var now = new Date().getTime();
        var d = end - now;
        if (d < 0) { document.getElementById("countdown_timer").innerHTML = "ENDED"; return; }
        
        var h = Math.floor((d % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var m = Math.floor((d % (1000 * 60 * 60)) / (1000 * 60));
        var s = Math.floor((d % (1000 * 60)) / 1000);
        
        var el = document.getElementById("countdown_timer");
        if(el) el.innerHTML = (h<10?"0"+h:h) + ":" + (m<10?"0"+m:m) + ":" + (s<10?"0"+s:s);
    }, 1000);
    @endif
    
    // Simple polling for realtime updates
    setInterval(() => {
        @this.call('loadLot');
    }, 5000);
</script>
