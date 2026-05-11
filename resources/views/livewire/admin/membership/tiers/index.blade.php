<div>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Membership Tiers</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Membership</a></li>
                        <li class="breadcrumb-item active">Tiers</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-12">
            @include('livewire.admin.membership.tiers.partials._alerts')

            @if($this->getOrphanedRestrictionsCount() > 0)
                <div class="alert alert-danger border-0 shadow-sm mb-4 p-4 d-flex align-items-center" role="alert" style="border-radius: 1rem;">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 p-3 rounded-circle me-4">
                        <i class="ri-error-warning-fill fs-32 text-danger"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading text-danger fw-bold mb-1">Broken Product Restrictions Detected</h5>
                        <p class="mb-0 text-muted">There are <strong>{{ $this->getOrphanedRestrictionsCount() }}</strong> items (Auctions, Archive, CMS) referencing deleted tiers. This may cause items to be hidden from all users.</p>
                    </div>
                    <div class="flex-shrink-0 ms-3">
                        <button type="button" class="btn btn-danger btn-label rounded-pill" wire:click="openResolutionModal">
                            <i class="ri-tools-line label-icon align-middle fs-16 me-2"></i> Resolve Now
                        </button>
                    </div>
                </div>
            @endif

            <div class="card" id="customerList">
                @include('livewire.admin.membership.tiers.partials._filters')
                
                @include('livewire.admin.membership.tiers.partials._table')
            </div>
        </div>
    </div>

    @include('livewire.admin.membership.tiers.partials._tier-modal')
    @include('livewire.admin.membership.tiers.partials._migration-modal')
    @include('livewire.admin.membership.tiers.partials._resolution-modal')

    @include('livewire.admin.membership.tiers.partials._scripts')
</div>
