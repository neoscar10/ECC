@if(isset($row['restriction_mode']) && $row['restriction_mode'] === 'restricted')
    <div class="mt-2 p-2 bg-white border rounded">
        <label class="form-label fs-11 text-muted text-uppercase mb-1">Restriction Config</label>
        
        <select class="form-select form-select-sm mb-2" wire:model="attachmentRows.{{ $index }}.restriction_type">
            <option value="">Select Type...</option>
            <option value="hierarchical">Hierarchical (Min Tier)</option>
            <option value="random">Random (Specific Tiers)</option>
            <option value="private">Private (Single Tier)</option>
        </select>

        @if(isset($row['restriction_type']))
            @if($row['restriction_type'] === 'hierarchical')
                <select class="form-select form-select-sm" wire:model="attachmentRows.{{ $index }}.restricted_min_tier_id">
                    <option value="">Select Minimum Tier...</option>
                    @foreach($attachmentAllowedTiers as $tier)
                        <option value="{{ $tier['id'] }}">{{ $tier['name'] }}</option>
                    @endforeach
                </select>
                @error("attachmentRows.{$index}.restricted_min_tier_id") <span class="text-danger fs-11">{{ $message }}</span> @enderror
            
            @elseif($row['restriction_type'] === 'random')
                <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                    @if(count($attachmentAllowedTiers) > 0)
                        @foreach($attachmentAllowedTiers as $tier)
                            <div class="form-check form-check-sm mb-1">
                                <input class="form-check-input" type="checkbox" value="{{ $tier['id'] }}" wire:model="attachmentRows.{{ $index }}.selected_tiers" id="att-rand-{{ $index }}-{{ $tier['id'] }}">
                                <label class="form-check-label fs-12" for="att-rand-{{ $index }}-{{ $tier['id'] }}">{{ $tier['name'] }}</label>
                            </div>
                        @endforeach
                    @else
                        <span class="text-muted fs-12">No allowed tiers available.</span>
                    @endif
                </div>
                @error("attachmentRows.{$index}.selected_tiers") <span class="text-danger fs-11">{{ $message }}</span> @enderror

            @elseif($row['restriction_type'] === 'private')
                <select class="form-select form-select-sm" wire:model="attachmentRows.{{ $index }}.restricted_private_tier_id">
                    <option value="">Select Private Tier...</option>
                    @foreach($attachmentAllowedTiers as $tier)
                        <option value="{{ $tier['id'] }}">{{ $tier['name'] }}</option>
                    @endforeach
                </select>
                @error("attachmentRows.{$index}.restricted_private_tier_id") <span class="text-danger fs-11">{{ $message }}</span> @enderror
            @endif
        @endif
        
        @error("attachmentRows.{$index}.restriction_type") <span class="text-danger fs-11">{{ $message }}</span> @enderror
    </div>
@endif
