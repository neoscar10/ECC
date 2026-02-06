<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Status Management</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="paymentStatus" class="form-label">Payment Status</label>
            <select wire:model="paymentStatus" id="paymentStatus" class="form-select">
                <option value="unpaid">Unpaid</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="failed">Failed</option>
                <option value="refunded">Refunded</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="fulfillmentStatus" class="form-label">Fulfillment Status</label>
            <select wire:model="fulfillmentStatus" id="fulfillmentStatus" class="form-select">
                <option value="placed">Placed</option>
                <option value="processing">Processing</option>
                <option value="packed">Packed</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
                <option value="returned">Returned</option>
            </select>
            @error('fulfillmentStatus') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>
        <button wire:click="updateStatuses" wire:loading.attr="disabled" class="btn btn-primary w-100">
            Update Status
        </button>
    </div>
</div>
