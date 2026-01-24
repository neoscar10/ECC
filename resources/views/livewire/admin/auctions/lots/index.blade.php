<div>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Auctions</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Admin</a></li>
                            <li class="breadcrumb-item active">Auctions</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @include('livewire.admin.partials._alerts')

        @if ($successMessage)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $successMessage }}
                <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card" id="auctionList">
                    <div class="card-header border-0">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm-3">
                                <h5 class="card-title mb-0 flex-grow-1">Auction Lots</h5>
                            </div>
                            <div class="col-sm-auto ms-auto">
                                <div class="hstack gap-2">
                                    <button type="button" class="btn btn-primary add-btn" wire:click="openCreateModal"><i class="ri-add-line align-bottom me-1"></i> Create Lot</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body border border-dashed border-end-0 border-start-0">
                         @include('livewire.admin.auctions.lots.partials.index._filters')
                    </div>

                    <div class="card-body">
                         @include('livewire.admin.auctions.lots.partials.index._table', ['lots' => $lots])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reusable Edit/Create Modal -->
    <livewire:admin.auctions.lots.lot-form-modal :key="'lot-manager-modal'" />

    <!-- Local Sub-Modals -->
    @include('livewire.admin.auctions.partials._attachments-modal')
    @include('livewire.admin.auctions.partials._early-access-modal')
    @include('livewire.admin.auctions.partials._delete-confirm')

    <!-- Scripts -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Attachments Modal
            var attModal = new bootstrap.Modal(document.getElementById('attachmentsModal'));
            Livewire.on('show-att-modal', () => { attModal.show(); });
            Livewire.on('hide-att-modal', () => { attModal.hide(); });

            // Early Access Modal
            var eaModal = new bootstrap.Modal(document.getElementById('earlyAccessModal'));
            Livewire.on('show-ea-modal', () => { eaModal.show(); });
            Livewire.on('hide-ea-modal', () => { eaModal.hide(); });
            
            // Delete Modal
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteAuctionModal'));
            Livewire.on('show-delete-modal', () => { deleteModal.show(); });
            Livewire.on('hide-delete-modal', () => { deleteModal.hide(); });
        });
    </script>
</div>
