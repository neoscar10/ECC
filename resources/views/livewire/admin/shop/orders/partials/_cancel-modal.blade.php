@if($showCancelModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Order</h5>
                    <button type="button" class="btn-close" wire:click="$set('showCancelModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This will cancel the order and mark it as Cancelled/Refunded.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Cancellation <span class="text-danger">*</span></label>
                        <textarea wire:model="cancelReason" class="form-control" rows="3" placeholder="e.g. Customer request, Out of stock..."></textarea>
                        @error('cancelReason') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" wire:model="restoreStock" id="restoreStock">
                        <label class="form-check-label" for="restoreStock">
                            Restore Stock (Add items back to inventory)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="$set('showCancelModal', false)">Close</button>
                    <button type="button" class="btn btn-danger" wire:click="cancelOrder">Confirm Cancel</button>
                </div>
            </div>
        </div>
    </div>
@endif
