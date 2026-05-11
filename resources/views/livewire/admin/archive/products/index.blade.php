<div>
    @if(!$modalsOnly)
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Archive Products</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Archive</a></li>
                            <li class="breadcrumb-item active">Products</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Alerts -->
        @include('livewire.admin.partials._alerts')

        <div class="row">
            <div class="col-lg-12">
                <div class="card" id="productList">
                    <div class="card-header border-0">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm-3">
                                <h5 class="card-title mb-0 flex-grow-1">Products List</h5>
                            </div>
                            <div class="col-sm-auto ms-auto">
                                <div class="hstack gap-2">
                                    
                                    <button type="button" class="btn btn-primary add-btn" wire:click="create"><i class="ri-add-line align-bottom me-1"></i> Add Product</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body border border-dashed border-end-0 border-start-0">
                         @include('livewire.admin.archive.products.partials._filters')
                    </div>

                    <div class="card-body">
                         @include('livewire.admin.archive.products.partials._table')
                    </div>
                </div>
            </div>
        </div>

    </div>
    @endif

    <!-- Modals -->
    @include('livewire.admin.archive.products.partials._create-modal')
    @include('livewire.admin.archive.products.partials._early-access-modal')
    @include('livewire.admin.archive.products.partials._attachments-modal')
    @include('livewire.admin.archive.products.partials._delete-confirm')

    <!-- Scripts -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            
            // Create Product Modal
            var createModal = new bootstrap.Modal(document.getElementById('createProductModal'));
            Livewire.on('show-create-modal', () => { createModal.show(); });
            Livewire.on('hide-create-modal', () => { createModal.hide(); });
            // Custom listeners for wizard success
            Livewire.on('archive-product-created', () => { createModal.hide(); });
            Livewire.on('archive-product-updated', () => { createModal.hide(); });

            // Early Access Modal
            var eaModal = new bootstrap.Modal(document.getElementById('earlyAccessModal'));
            Livewire.on('show-ea-modal', () => { eaModal.show(); });
            Livewire.on('hide-ea-modal', () => { eaModal.hide(); });

             // Attachments Modal
            var attModal = new bootstrap.Modal(document.getElementById('attachmentsModal'));
            Livewire.on('show-att-modal', () => { attModal.show(); });
            Livewire.on('hide-att-modal', () => { attModal.hide(); });
            
            // Delete Confirm Modal
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
            Livewire.on('show-product-delete-modal', () => { deleteModal.show(); });
            Livewire.on('hide-product-delete-modal', () => { deleteModal.hide(); });
        });
    </script>
</div>
