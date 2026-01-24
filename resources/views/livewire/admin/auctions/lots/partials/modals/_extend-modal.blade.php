<div wire:ignore.self class="modal fade" id="extendModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Extend Auction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Extend By (Minutes)</label>
                    <input type="number" class="form-control" wire:model.number="extendMinutes" min="1">
                    @error('extendMinutes') <div class="text-danger fs-12 mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label">Reason (Optional)</label>
                    <textarea class="form-control" rows="3" wire:model="extendReason"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" wire:click="extendAuction">
                    Confirm Extension
                </button>
            </div>
        </div>
    </div>
</div>
