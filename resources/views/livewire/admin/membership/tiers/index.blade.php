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

            <div class="card" id="customerList">
                @include('livewire.admin.membership.tiers.partials._filters')
                
                @include('livewire.admin.membership.tiers.partials._table')
            </div>
        </div>
    </div>

    @include('livewire.admin.membership.tiers.partials._tier-modal')

    @include('livewire.admin.membership.tiers.partials._scripts')
</div>
