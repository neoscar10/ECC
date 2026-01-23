<div class="row g-3">
    <div class="col-12">
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" id="antiSniping" wire:model.live="anti_sniping_enabled">
            <label class="form-check-label fw-bold" for="antiSniping">Enable Anti-Sniping (Soft Close)</label>
        </div>
        <div class="alert alert-info bg-info-subtle text-info border-0">
            If a bid is placed within the <b>Trigger Window</b> before the scheduled end time, the auction will be extended by the <b>Extension Time</b>.
        </div>
    </div>

    @if($anti_sniping_enabled)
    <div class="col-md-4">
        <label class="form-label">Trigger Window (Seconds)</label>
        <input type="number" class="form-control" wire:model="trigger_window_seconds" placeholder="e.g. 120">
        <small class="text-muted">Time before end to check for bids (e.g. last 2 mins)</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">Extend By (Seconds)</label>
        <input type="number" class="form-control" wire:model="extend_by_seconds" placeholder="e.g. 60">
        <small class="text-muted">Time added to end date (e.g. 1 min)</small>
    </div>
    <div class="col-md-4">
        <label class="form-label">Max Extensions</label>
        <input type="number" class="form-control" wire:model="max_extensions" placeholder="e.g. 5">
        <small class="text-muted">Max times to extend (leave empty for infinite)</small>
    </div>
    @endif
</div>
