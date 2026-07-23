<!-- Upgrade Membership Modal -->
<div class="modal fade {{ $showUpgradeModal ? 'show d-block' : '' }}" 
     tabindex="-1"
     @if($showUpgradeModal) style="background: rgba(0,0,0,.85); backdrop-filter: blur(8px); z-index: 1060;" @else style="display:none;" @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content ecc-settings-modal" style="background: var(--ecc-bg-surface); border: 1px solid var(--ecc-border-soft); border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ecc-settings-modal-kicker mb-2">UPGRADE STATUS</div>
                    <h5 class="ecc-settings-modal-title mb-1" style="color: var(--ecc-text-primary);">Select Membership Tier</h5>
                    <p class="ecc-settings-modal-subtitle mb-0" style="color: var(--ecc-text-secondary);">Choose a higher tier to unlock exclusive privileges and premium archive contents.</p>
                </div>

                <button type="button" 
                        class="btn-close btn-close-white" 
                        wire:click="closeUpgradeModal"></button>
            </div>

            <div class="modal-body pt-4">
                @if(empty($upgradeTiers))
                    <div class="text-center py-4">
                        <p class="ecc-text-muted">No upgrade tiers available.</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($upgradeTiers as $t)
                            @php
                                $isSelected = (int) $selectedUpgradeTierId === (int) $t['id'];
                            @endphp
                            <div class="col-12 col-md-6">
                                <div role="button" 
                                     wire:click="selectUpgradeTier({{ $t['id'] }})"
                                     class="ecc-upgrade-tier-card p-3 h-100 {{ $isSelected ? 'is-selected' : '' }}"
                                     style="border: 1px solid {{ $isSelected ? 'var(--ecc-primary)' : 'var(--ecc-border-soft)' }}; background: {{ $isSelected ? 'rgba(199, 167, 90, 0.05)' : 'var(--ecc-bg-surface-2)' }}; border-radius: 12px; cursor: pointer; transition: all 0.25s ease;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 class="fw-bold m-0 text-uppercase-less" style="color: {{ $isSelected ? 'var(--ecc-primary)' : 'var(--ecc-text-primary)' }}; font-size: 1.1rem; letter-spacing: 0.05em;">
                                            {{ $t['name'] }}
                                        </h4>
                                        <div class="d-flex align-items-center">
                                            @if($isSelected)
                                                <span class="material-symbols-outlined ecc-selected-icon" style="color: var(--ecc-primary); font-size: 1.3rem;">check_circle</span>
                                            @else
                                                <span class="material-symbols-outlined" style="color: var(--ecc-text-subtle); font-size: 1.3rem;">radio_button_unchecked</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <p class="small mb-3" style="color: var(--ecc-text-secondary); min-height: 36px; line-height: 1.4;">
                                        {{ $t['short_desc'] }}
                                    </p>

                                    <div class="d-flex justify-content-between align-items-end mt-2 pt-2 border-top border-secondary-subtle">
                                        <span class="small" style="color: var(--ecc-text-subtle);">Annual Fee</span>
                                        <div class="text-end">
                                            <span class="fw-bold" style="color: var(--ecc-primary); font-size: 1.2rem;">{{ $t['price_formatted'] }}</span>
                                            <span class="small" style="color: var(--ecc-text-subtle);">/ Year</span>
                                        </div>
                                    </div>

                                    {{-- Benefits list --}}
                                    @if(!empty($t['benefits_list']))
                                        <div class="mt-3">
                                            <ul class="list-unstyled m-0" style="font-size: 0.8rem; color: var(--ecc-text-secondary);">
                                                @foreach($t['benefits_list'] as $b)
                                                    <li class="d-flex align-items-center mb-1">
                                                        <span class="material-symbols-outlined me-1" style="color: var(--ecc-primary); font-size: 0.95rem;">check</span>
                                                        <span class="text-truncate" style="max-width: 100%;">{{ $b }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                @error('selectedUpgradeTierId')
                    <div class="text-danger small mt-3">{{ $message }}</div>
                @enderror
            </div>

            <div class="modal-footer border-0 pt-0 mt-3 d-flex gap-2">
                <button type="button" 
                        class="btn ecc-btn-outline-light px-4"
                        wire:click="closeUpgradeModal">
                    Cancel
                </button>
                <button type="button" 
                        class="btn ecc-btn-primary px-4"
                        wire:click="confirmUpgrade"
                        @if(!$selectedUpgradeTierId) disabled @endif>
                    Confirm Upgrade
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .ecc-upgrade-tier-card {
        transition: all 0.2s ease-in-out;
    }
    .ecc-upgrade-tier-card:hover {
        border-color: var(--ecc-primary) !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 24px var(--ecc-shadow-soft);
    }
</style>
