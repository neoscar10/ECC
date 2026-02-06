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
                        <div class="accordion" id="stockAccordion">
                            @foreach($editingProduct->variationGroups as $group)
                                <div class="accordion-item border mb-2 overflow-hidden" wire:key="inv-group-{{ $group->id }}">
                                    <h2 class="accordion-header" id="heading-{{ $group->id }}">
                                        <button class="accordion-button p-3 bg-light-subtle shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $group->id }}" aria-expanded="true">
                                            <span class="fw-semibold text-dark">{{ $group->name }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $group->values->count() }} values</span>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $group->id }}" class="accordion-collapse collapse show" data-bs-parent="#stockAccordion">
                                        <div class="accordion-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-nowrap align-middle mb-0">
                                                    <thead class="table-light text-muted small uppercase">
                                                        <tr>
                                                            <th class="ps-4">Variation Value</th>
                                                            <th style="width: 150px;">New Stock</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($group->values as $value)
                                                            <tr wire:key="inv-val-{{ $value->id }}">
                                                                <td class="ps-4">
                                                                    <div class="d-flex align-items-center">
                                                                         @if($value->presentation_image_path)
                                                                            <img src="{{ asset('storage/'.$value->presentation_image_path) }}" class="avatar-xxs rounded me-2">
                                                                        @elseif($value->color_hex)
                                                                            <span class="avatar-xxs rounded me-2 d-inline-block border" style="background-color: {{ $value->color_hex }};"></span>
                                                                        @endif
                                                                        <span class="{{ $value->stock_qty == 0 ? 'text-danger fw-medium' : '' }}">
                                                                            {{ $value->caption }}
                                                                        </span>
                                                                        @if($value->stock_qty == 0)
                                                                            <span class="badge bg-danger-subtle text-danger ms-2">Out of Stock</span>
                                                                        @else 
                                                                             <span class="badge bg-light text-muted ms-2 px-2 border">Current: {{ $value->stock_qty }}</span>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control form-control-sm" 
                                                                           wire:model="editingVariationStock.{{ $value->id }}" min="0">
                                                                    @error("editingVariationStock.{$value->id}") 
                                                                        <div class="text-danger extra-small mt-1">{{ $message }}</div> 
                                                                    @enderror
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
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
