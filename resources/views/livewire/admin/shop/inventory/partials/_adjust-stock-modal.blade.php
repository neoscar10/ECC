<!-- Stock Adjustment Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" aria-labelledby="adjustStockLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title" id="adjustStockLabel">
                    Adjust Inventory: 
                    @if($editingProduct)
                        <span class="text-primary">{{ $editingProduct->title }}</span>
                        @if($editingProduct->variationGroups->count() > 0)
                            <span class="badge bg-secondary ms-2 align-middle">Variant</span>
                        @else
                            <span class="badge bg-primary ms-2 align-middle">Simple</span>
                        @endif
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeAdjustStockModal"></button>
            </div>
            <div class="modal-body p-4">
                @if($editingProduct)
                    
                    {{-- Simple Product Mode --}}
                    @if($editingProduct->variationGroups->isEmpty())
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label text-muted text-uppercase fw-semibold small">Current Quantity</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" class="form-control" wire:model="editingStockQty" min="0">
                                    <span class="input-group-text">units</span>
                                </div>
                                <div class="mt-2">
                                     @error('editingStockQty') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6 border-start">
                                <div class="ps-3">
                                    <h6 class="text-muted fw-semibold">Quick Actions</h6>
                                    <div class="d-flex gap-2 mt-2">
                                        <button class="btn btn-sm btn-soft-secondary" wire:click="$set('editingStockQty', {{ (int)$editingStockQty + 10 }})">+10</button>
                                         <button class="btn btn-sm btn-soft-secondary" wire:click="$set('editingStockQty', {{ (int)$editingStockQty + 50 }})">+50</button>
                                        <button class="btn btn-sm btn-soft-danger" wire:click="$set('editingStockQty', 0)">Set to 0</button>
                                    </div>
                                    <div class="alert alert-info border-0 bg-info-subtle text-info mb-0 mt-3 p-2 small">
                                        <i class="ri-information-line me-1"></i> Simple products have a single stock count.
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- Variant Product Mode --}}
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-nowrap align-middle mb-0">
                                <thead class="table-light text-muted small uppercase">
                                    <tr>
                                        <th class="ps-4">Combination</th>
                                        <th style="width: 150px;">SKU</th>
                                        <th style="width: 150px;">New Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($editingProduct->variants as $variant)
                                        <tr wire:key="inv-variant-{{ $variant->id }}">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    @php
                                                        $labels = $variant->optionValues->pluck('caption')->join(' / ');
                                                    @endphp
                                                    <span class="{{ $variant->stock_qty == 0 ? 'text-danger fw-medium' : '' }}">
                                                        {{ $labels }}
                                                    </span>
                                                    @if($variant->stock_qty == 0)
                                                        <span class="badge bg-danger-subtle text-danger ms-2">Out of Stock</span>
                                                    @else 
                                                         <span class="badge bg-light text-muted ms-2 px-2 border">Current: {{ $variant->stock_qty }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted small">{{ $variant->sku ?: 'No SKU' }}</span>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" 
                                                       wire:model="editingVariationStock.{{ $variant->id }}" min="0">
                                                @error("editingVariationStock.{$variant->id}") 
                                                    <div class="text-danger extra-small mt-1">{{ $message }}</div> 
                                                @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                @else
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer bg-light p-3">
                <button type="button" class="btn btn-link link-secondary text-decoration-none fw-medium" data-bs-dismiss="modal" wire:click="closeAdjustStockModal">Cancel</button>
                <button type="button" class="btn btn-primary" wire:click="saveStockAdjustment" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveStockAdjustment">Save Changes</span>
                    <span wire:loading wire:target="saveStockAdjustment"><i class="ri-loader-4-line ri-spin me-1"></i> Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const modalEl = document.getElementById('adjustStockModal');
        const modal = new bootstrap.Modal(modalEl);

        Livewire.on('show-adjust-stock-modal', () => {
            modal.show();
        });

        Livewire.on('hide-adjust-stock-modal', () => {
            modal.hide();
        });
    });
</script>
