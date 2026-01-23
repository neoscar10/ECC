<div class="modal fade" id="earlyAccessModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configure Early Access (Auction Lot)</h5>
                <button type="button" class="btn-close" wire:click="$set('showEarlyAccessModal', false)" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Note:</strong> Early access dates must be sooner than the lot Go Live date ({{ $earlyAccessGoLiveDate }}).
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40%">Tier</th>
                                <th style="width: 40%">Access Starts At</th>
                                <th style="width: 20%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($earlyAccessRows as $index => $row)
                                @php
                                    // Check if current row tier is valid (allowed)
                                    $isRowInvalid = !empty($row['tier_id']) && collect($earlyAccessAllowedTiers)->doesntContain('id', $row['tier_id']);
                                @endphp
                                <tr class="{{ $isRowInvalid ? 'table-danger' : '' }}">
                                    <td>
                                        <select class="form-select {{ $isRowInvalid || $errors->has("earlyAccessRows.{$index}.tier_id") ? 'is-invalid' : '' }}" wire:model="earlyAccessRows.{{ $index }}.tier_id">
                                            <option value="">Select Tier</option>
                                            @foreach($earlyAccessAllowedTiers as $tier)
                                                @php
                                                    $isEligible = $tier['has_early_access'] ?? false;
                                                @endphp
                                                <option value="{{ $tier['id'] }}" @disabled(!$isEligible)>
                                                    {{ $tier['name'] }} {{ !$isEligible ? '(Not Eligible)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($isRowInvalid)
                                            <div class="invalid-feedback d-block">
                                                Tier not allowed. Please remove.
                                            </div>
                                        @endif
                                        @error("earlyAccessRows.{$index}.tier_id") <span class="text-danger small">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <input type="datetime-local" class="form-control {{ $errors->has("earlyAccessRows.{$index}.access_at") ? 'is-invalid' : '' }}" wire:model="earlyAccessRows.{{ $index }}.access_at">
                                        @error("earlyAccessRows.{$index}.access_at") <span class="text-danger small">{{ $message }}</span> @enderror
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-soft-danger btn-sm" wire:click="removeEarlyAccessRow({{ $index }})">
                                            <i class="ri-delete-bin-line"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-soft-primary btn-sm" wire:click="addEarlyAccessRow">
                    <i class="ri-add-line align-bottom"></i> Add Tier Window
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="saveEarlyAccess">Save Configuration</button>
            </div>
        </div>
    </div>
</div>
