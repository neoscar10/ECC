<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Activity Timeline</h5>
        <span class="badge bg-secondary-subtle text-secondary">{{ count($timelineEvents) }} Events</span>
    </div>
    <div class="card-body">
        <div class="acitivity-timeline py-3" style="max-height: 420px; overflow-y: auto;">
            @forelse($timelineEvents as $event)
                <div class="acitivity-item d-flex mb-4">
                    <div class="flex-shrink-0">
                        @if($event->event_type == 'bid_placed')
                            <div class="avatar-xs rounded-circle bg-success-subtle border border-success-subtle text-success fs-10 d-flex align-items-center justify-content-center">
                                <i class="ri-auction-line"></i>
                            </div>
                        @elseif($event->event_type == 'extended')
                             <div class="avatar-xs rounded-circle bg-warning-subtle border border-warning-subtle text-warning fs-10 d-flex align-items-center justify-content-center">
                                <i class="ri-time-line"></i>
                            </div>
                        @elseif($event->event_type == 'cancelled')
                             <div class="avatar-xs rounded-circle bg-danger-subtle border border-danger-subtle text-danger fs-10 d-flex align-items-center justify-content-center">
                                <i class="ri-close-line"></i>
                            </div>
                        @elseif($event->event_type == 'updated')
                             <div class="avatar-xs rounded-circle bg-primary-subtle border border-primary-subtle text-primary fs-10 d-flex align-items-center justify-content-center">
                                <i class="ri-file-edit-line"></i>
                            </div>
                        @else
                            <div class="avatar-xs rounded-circle bg-light border border-light text-muted fs-10 d-flex align-items-center justify-content-center">
                                <i class="ri-record-circle-line"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between mb-1">
                             <h6 class="mb-0 fs-14 fw-semibold">{{ $this->formatEventTitle($event) }}</h6>
                             <small class="text-muted ms-2 text-nowrap">{{ $event->created_at->format('d M, h:i A') }}</small>
                        </div>
                        
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-muted fs-12 me-2">By</span>
                            <span class="badge bg-light text-body border">{{ $this->formatActorLabel($event) }}</span>
                            <small class="text-muted ms-2">({{ $event->created_at->diffForHumans() }})</small>
                        </div>
                        
                        @php $details = $this->getTimelineEventDetails($event); @endphp
                        
                        @if(!empty($details))
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($details as $key => $val)
                                    <div class="badge bg-light text-muted border fw-normal">
                                        <span class="fw-semibold text-dark">{{ $key }}:</span> {{ $val }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        

                    </div>
                </div>
            @empty
                <div class="text-center py-4">
                    <div class="avatar-md mx-auto mb-3">
                        <div class="avatar-title bg-light rounded-circle text-muted fs-24">
                            <i class="ri-history-line"></i>
                        </div>
                    </div>
                    <p class="text-muted">No activity recorded yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
