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
