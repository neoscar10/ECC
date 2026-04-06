<div class="combinations-matrix">
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info border-info mb-0">
                <i class="ri-information-line me-1"></i>
                Set individual price and stock for each combination. The <strong>Base Price</strong> ({{ $currency }} {{ number_format($base_price, 2) }}) is the default for new combinations.
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-nowrap align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 250px;">Combination</th>
                    <th scope="col" style="width: 150px;">SKU</th>
                    <th scope="col" style="width: 120px;">Price ({{ $currency }})</th>
                    <th scope="col" style="width: 100px;">Stock</th>
                    <th scope="col" class="text-center" style="width: 80px;">Default</th>
                </tr>
            </thead>
            <tbody>
                @forelse($combinations as $key => $combo)
                    <tr class="{{ $combo['is_default'] ? 'table-success-subtle' : '' }}">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="fs-14 mb-1">
                                        @foreach($combo['labels'] as $label)
                                            <span class="badge bg-light text-dark border me-1">{{ $label }}</span>
                                        @endforeach
                                    </h6>
                                </div>
                            </div>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" 
                                wire:model.blur="combinations.{{ $key }}.sku" placeholder="Optional SKU">
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">{{ $currency }}</span>
                                <input type="number" step="0.01" class="form-control" 
                                    wire:model.blur="combinations.{{ $key }}.price">
                            </div>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm" 
                                wire:model.blur="combinations.{{ $key }}.stock">
                        </td>
                        <td class="text-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                    name="combo_default"
                                    wire:model.live="combinations.{{ $key }}.is_default"
                                    value="true"
                                    @if($combo['is_default']) checked @endif
                                    wire:click="$dispatch('set-combo-default', { key: '{{ $key }}' })">
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            No combinations generated. Please check your variation groups.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
