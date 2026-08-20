@if($showMarkDeliveryPaidModal)
<div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Shipping as Paid</h5>
                <button type="button" class="btn-close" wire:click="closeMarkDeliveryPaidModal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p>Are you sure you want to mark the negotiated shipping fee for order <strong>{{ $this->order->order_number }}</strong> as paid?</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" wire:click="closeMarkDeliveryPaidModal">Cancel</button>
                <button type="button" class="btn btn-success" wire:click="markDeliveryPaid" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="markDeliveryPaid">Confirm & Mark Paid</span>
                    <span wire:loading wire:target="markDeliveryPaid">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
