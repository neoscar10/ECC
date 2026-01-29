<!-- Approve Modal -->
<div wire:ignore.self class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this application?</p>
                <p class="text-muted">This will grant <strong>{{ $selectedApplication->selectedTier->name ?? 'Membership' }}</strong> to {{ $selectedApplication->user->name ?? 'User' }}.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" wire:click="approve" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="approve">Confirm Approval</span>
                    <span wire:loading wire:target="approve">Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div wire:ignore.self class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rejection Reason</label>
                    <textarea class="form-control" wire:model="rejectionReason" rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                    @error('rejectionReason') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" wire:click="reject" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="reject">Confirm Rejection</span>
                        <span wire:loading wire:target="reject">Processing...</span>
                </button>
            </div>
        </div>
    </div>
</div>
