@php
    $events = $this->order->payments->flatMap->events->sortByDesc('created_at');
@endphp

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Payment & Webhook Events</h5>
        <span class="badge bg-primary-subtle text-primary">{{ $events->count() }} Events</span>
    </div>
    
    <div class="card-body">
        @if($events->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-sm">
                    <thead class="table-light text-muted">
                        <tr>
                            <th>Date & Time</th>
                            <th>Event Type</th>
                            <th>Gateway Event ID</th>
                            <th>Signature Valid</th>
                            <th>Status/Processed</th>
                            <th class="text-end">Payload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td>
                                    <small class="fw-medium text-nowrap">
                                        {{ $event->created_at->format('d M Y, h:i A') }}
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info text-capitalize">{{ $event->event_type }}</span>
                                </td>
                                <td>
                                    <small class="text-muted text-nowrap">
                                        {{ $event->gateway_event_id ?: '-' }}
                                    </small>
                                </td>
                                <td>
                                    @if($event->signature_valid)
                                        <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line me-1"></i>Valid</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger"><i class="ri-close-circle-line me-1"></i>Invalid</span>
                                    @endif
                                </td>
                                <td>
                                    @if($event->processed_at)
                                        <span class="badge bg-success-subtle text-success">Processed</span>
                                        <div class="text-muted" style="font-size: 10px;">
                                            {{ $event->processed_at->format('h:i A') }}
                                        </div>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-soft-secondary py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#payload-{{ $event->id }}" aria-expanded="false">
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse-row">
                                <td colspan="6" class="p-0 border-0">
                                    <div class="collapse" id="payload-{{ $event->id }}">
                                        <div class="p-3 bg-light border-bottom text-start">
                                            <h6 class="text-muted mb-2">Raw Webhook Payload (Event #{{ $event->id }})</h6>
                                            <pre class="bg-dark text-white rounded p-3 mb-0" style="max-height: 250px; overflow-y: auto; font-size: 11px;"><code>{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="ri-notification-badge-line fs-2 mb-2"></i>
                <p class="mb-0">No payment or webhook events recorded yet for this order.</p>
            </div>
        @endif
    </div>
</div>
