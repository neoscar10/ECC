<div class="combinations-matrix">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded mb-3 border">
                <div>
                    <h6 class="mb-1"><i class="ri-truck-line align-bottom me-1"></i> Requires Delivery / Shipping</h6>
                    <p class="text-muted mb-0 fs-13">If disabled, users will not be asked for shipping details and shipping will be free.</p>
                </div>
                <div class="form-check form-switch form-switch-lg form-switch-success">
                    <input class="form-check-input" type="checkbox" role="switch" wire:model.live="requires_shipping">
                </div>
            </div>

            <div class="alert alert-info border-info mb-0">
                <i class="ri-information-line me-1"></i>
                Set individual price and stock for each combination. The <strong>Base Price</strong> ({{ $currency }} {{ number_format($base_price, 2) }}) is the default for new combinations.
            </div>
        </div>
    </div>

    @if($has_variants)
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
                        @if($requires_shipping)
                        <tr class="bg-light-subtle">
                            <td colspan="5" class="py-2 px-4 border-bottom">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto">
                                        <span class="badge bg-soft-primary text-primary fs-11"><i class="ri-truck-line me-1"></i> Shipping:</span>
                                    </div>
                                    <div class="col">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fs-11">Weight (kg)</span>
                                            <input type="number" step="0.001" class="form-control form-control-sm" wire:model.live="combinations.{{ $key }}.weight_kg" placeholder="0.000">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fs-11">Length (cm)</span>
                                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="combinations.{{ $key }}.length_cm" placeholder="0.0">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fs-11">Breadth (cm)</span>
                                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="combinations.{{ $key }}.breadth_cm" placeholder="0.0">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fs-11">Height (cm)</span>
                                            <input type="number" step="0.1" class="form-control form-control-sm" wire:model.live="combinations.{{ $key }}.height_cm" placeholder="0.0">
                                        </div>
                                    </div>
                                    <div class="col-12 mt-2">
                                        @php
                                            $divisor = (float) config('shipping.volumetric_divisor', 5000);
                                            $volumetric = 0;
                                            $volume = 0;
                                            $w = $combinations[$key]['weight_kg'] ?? 0;
                                            $l = $combinations[$key]['length_cm'] ?? 0;
                                            $b = $combinations[$key]['breadth_cm'] ?? 0;
                                            $h = $combinations[$key]['height_cm'] ?? 0;
                                            if ($l && $b && $h) {
                                                $volume = (float)$l * (float)$b * (float)$h;
                                                $volumetric = round($volume / $divisor, 3);
                                            }
                                            $chargeable = max((float)$w, $volumetric);
                                        @endphp
                                        <div class="d-flex align-items-center gap-4 ps-1">
                                            <span class="text-muted fs-11">Volume: <strong>{{ number_format($volume, 1) }} cm³</strong></span>
                                            <span class="text-muted fs-11">Vol. Weight: <strong>{{ $volumetric ?: '0.000' }} kg</strong></span>
                                            <span class="text-muted fs-11">Chargeable: <strong>{{ $chargeable ?: '0.000' }} kg</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif

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
    @else
        <div class="card border-dashed">
            <div class="card-header bg-light-subtle">
                <h6 class="card-title mb-0"><i class="ri-truck-line align-bottom me-1"></i> Shipping Dimensions</h6>
            </div>
            @if($requires_shipping)
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Weight (kg)</label>
                        <input type="number" step="0.001" class="form-control" wire:model.live="weight_kg" placeholder="0.000">
                        @error('weight_kg') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Length (cm)</label>
                        <input type="number" step="0.1" class="form-control" wire:model.live="length_cm" placeholder="0.0">
                        @error('length_cm') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Breadth (cm)</label>
                        <input type="number" step="0.1" class="form-control" wire:model.live="breadth_cm" placeholder="0.0">
                        @error('breadth_cm') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Height (cm)</label>
                        <input type="number" step="0.1" class="form-control" wire:model.live="height_cm" placeholder="0.0">
                        @error('height_cm') <span class="text-danger text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-12 mt-2">
                        <div class="d-flex gap-3 text-muted small">
                            @php
                                $divisor = (float) config('shipping.volumetric_divisor', 5000);
                                $volumetric = 0;
                                if ($weight_kg || ($length_cm && $breadth_cm && $height_cm)) {
                                    if ($length_cm && $breadth_cm && $height_cm) {
                                        $volumetric = round(((float)$length_cm * (float)$breadth_cm * (float)$height_cm) / $divisor, 3);
                                    }
                                }
                                $chargeable = max((float)$weight_kg, $volumetric);
                            @endphp
                            <span><i class="ri-information-line align-bottom"></i> Volumetric Weight: <strong>{{ $volumetric ?: '0.000' }} kg</strong></span>
                            <span><i class="ri-scales-3-line align-bottom"></i> Chargeable Weight: <strong>{{ $chargeable ?: '0.000' }} kg</strong></span>
                        </div>
                        <p class="text-muted small mt-1 mb-0">Used for courier rate calculation. Volumetric weight is calculated as L × B × H ÷ {{ $divisor }}.</p>
                    </div>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="alert alert-success mb-0 border-0">
                    <i class="ri-checkbox-circle-line me-1 align-middle"></i> Free Shipping enabled. No weight or dimensions required.
                </div>
            </div>
            @endif
        </div>
    @endif
</div>
