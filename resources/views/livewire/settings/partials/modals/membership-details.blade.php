<!-- Membership Details Modal -->
<div class="modal fade {{ $showMembershipDetailsModal ? 'show d-block' : '' }}" 
     tabindex="-1"
     @if($showMembershipDetailsModal) style="background: rgba(0,0,0,.85);" @else style="display:none;" @endif>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ecc-settings-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="ecc-settings-modal-kicker mb-2">MEMBERSHIP</div>
                    <h5 class="ecc-settings-modal-title mb-1">Membership Details</h5>
                    <p class="ecc-settings-modal-subtitle mb-0">Review your current tier and renewal information.</p>
                </div>

                <button type="button" 
                        class="btn-close btn-close-white" 
                        wire:click="closeMembershipDetailsModal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="d-flex flex-column gap-3">
                    <div class="ecc-membership-detail-row">
                        <span class="ecc-membership-detail-label">Tier</span>
                        <strong>{{ $membershipTierLabel }}</strong>
                    </div>

                    @if($membershipStatusLabel)
                        <div class="ecc-membership-detail-row">
                            <span class="ecc-membership-detail-label">Status</span>
                            <strong>{{ $membershipStatusLabel }}</strong>
                        </div>
                    @endif

                    @if($membershipRenewalLabel)
                        <div class="ecc-membership-detail-row">
                            <span class="ecc-membership-detail-label">Renewal</span>
                            <strong>{{ $membershipRenewalLabel }}</strong>
                        </div>
                    @endif

                    @if($membershipDetailsText)
                        <div class="ecc-membership-detail-note">
                            {{ $membershipDetailsText }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 d-flex gap-2">
                <button type="button" 
                        class="btn ecc-btn-outline-light px-4"
                        wire:click="closeMembershipDetailsModal">
                    Close
                </button>
                @if($hasUpgradeAvailable)
                    <button type="button" 
                            class="btn ecc-btn-primary px-4"
                            wire:click="openUpgradeModal">
                        Upgrade Membership
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
