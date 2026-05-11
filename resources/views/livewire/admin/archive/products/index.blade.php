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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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

            // Sortable Initialization
            const initSortable = () => {
                // Existing Main Images
                const elExMain = document.getElementById('existing-main-images');
                if (elExMain) {
                    new Sortable(elExMain, {
                        animation: 150,
                        onEnd: function (evt) {
                            let orderedIds = Array.from(elExMain.children).map(el => el.dataset.id);
                            @this.call('reorderImages', orderedIds, 'main');
                        }
                    });
                }

                // New Main Images
                const elNewMain = document.getElementById('new-main-images');
                if (elNewMain) {
                    new Sortable(elNewMain, {
                        animation: 150,
                        onEnd: function (evt) {
                            let indices = Array.from(elNewMain.children).map(el => el.dataset.index);
                            @this.call('reorderNewImages', indices, 'main');
                        }
                    });
                }

                // Existing 360 Images
                const elEx360 = document.getElementById('existing-360-images');
                if (elEx360) {
                    new Sortable(elEx360, {
                        animation: 150,
                        onEnd: function (evt) {
                            let orderedIds = Array.from(elEx360.children).map(el => el.dataset.id);
                            @this.call('reorderImages', orderedIds, '360');
                        }
                    });
                }

                // New 360 Images
                const elNew360 = document.getElementById('new-360-images');
                if (elNew360) {
                    new Sortable(elNew360, {
                        animation: 150,
                        onEnd: function (evt) {
                            let indices = Array.from(elNew360.children).map(el => el.dataset.index);
                            @this.call('reorderNewImages', indices, '360');
                        }
                    });
                }
            };

            // Re-init on Livewire update
            Livewire.hook('request', (({ component, commit, respond, succeed, fail }) => {
                succeed(({ snapshot, effect }) => {
                    queueMicrotask(() => {
                        initSortable();
                    });
                })
            }));

            // Initial init
            initSortable();
        });
    </script>
</div>
