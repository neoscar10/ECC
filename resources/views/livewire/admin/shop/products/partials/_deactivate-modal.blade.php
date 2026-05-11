<div class="modal fade" id="deactivateProductModal" tabindex="-1" aria-labelledby="deactivateProductModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title" id="deactivateProductModalLabel">Deactivate Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form wire:submit.prevent="confirmDeactivation">
                <div class="modal-body">
                    <p>You are about to deactivate this product. It will no longer be visible or purchasable by customers.</p>
                    <div class="mb-3">
                        <label for="deactivation_reason" class="form-label">Reason for Deactivation <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deactivation_reason" wire:model="deactivation_reason" rows="3" placeholder="Please provide a brief reason (e.g., Temporarily out of stock, Discontinued, Seasonal, etc.)" required></textarea>
                        @error('deactivation_reason') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmDeactivation">Deactivate Product</span>
                        <span wire:loading wire:target="confirmDeactivation">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
