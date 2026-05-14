<div>
    @if(!$modalsOnly)
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Products</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Products</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    @include('livewire.admin.partials._alerts')
    <div class="card">
        <div class="card-header border-0">
            <div class="row g-4">
                <div class="col-sm">
                    <div class="d-flex justify-content-sm-end">
                        
                        <div>
                            <button wire:click="create" class="btn btn-success"><i class="ri-add-line align-bottom me-1"></i> Add Product</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Filters --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="search-box ms-2">
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search Products...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterCategory">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterStock">
                        <option value="">All Stock Status</option>
                        <option value="in_stock">In Stock</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive table-card mb-1">
                <table class="table align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th>Product</th>
                            <th>Categories</th>
                            <th>Tags</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr class="{{ !$product->is_active ? 'table-light opacity-75' : '' }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                         @if($product->images->first())
                                             <img src="{{ Storage::url($product->images->first()->image_path) }}" class="avatar-xs rounded-circle me-2 object-fit-cover" alt="{{ $product->title }}">
                                         @else
                                             <div class="avatar-xs me-2">
                                                 <span class="avatar-title rounded-circle bg-light text-primary">
                                                     {{ substr($product->title, 0, 1) }}
                                                 </span>
                                             </div>
                                         @endif
                                         <div>
                                             <h5 class="fs-14 m-0"><a href="{{ route('admin.shop.products.show', $product->id) }}" class="text-dark">{{ $product->title }}</a></h5>
                                             <p class="text-muted mb-0" style="font-size: 11px;">{{ $product->display_id }}</p>
                                         </div>
                                    </div>
                                </td>
                                <td>
                                    @foreach($product->categories as $cat)
                                        <span class="badg-info text-info">{{ $cat->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($product->tags as $tag)
                                        <span class="badge badge-secondary text-secondary">{{ $tag->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @php
                                        $stock = $product->computed_stock;
                                        $isLow = $stock > 0 && $stock <= 10;
                                        $isOut = $stock === 0;
                                        $badgeClass = $isOut ? 'bg-danger' : ($isLow ? 'bg-warning text-dark' : 'bg-success');
                                        $stockLabel = $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'In Stock');
                                    @endphp
                                    
                                    <div>
                                        <span class="badge {{ $badgeClass }}">{{ $stockLabel }}</span>
                                        <div class="mt-1 small">
                                            <span class="fw-semibold">{{ $stock }}</span> units
                                            @if($product->variation_groups_count > 0)
                                                <span class="text-muted ms-1" title="Effective stock based on variations">(Var)</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-body fw-medium">{{ $product->currency }} {{ number_format($product->base_price, 2) }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-switch form-switch-md" dir="ltr">
                                            <input type="checkbox" class="form-check-input" 
                                                wire:click.prevent="toggleStatus({{ $product->id }}, {{ $product->is_active ? 'true' : 'false' }})" 
                                                {{ $product->is_active ? 'checked' : '' }}>
                                        </div>
                                        @if(!$product->is_active && $product->deactivation_reason)
                                            <i class="ri-information-line text-muted ms-2 fs-15" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $product->deactivation_reason }}" style="cursor: help;"></i>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item edit-item-btn" wire:click="edit({{ $product->id }})">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                </button>
                                            </li>
                                            @if($product->variation_groups_count > 0)
                                            <li>
                                                <button class="dropdown-item" wire:click="openVariationsOnly({{ $product->id }})">
                                                    <i class="ri-layout-grid-line align-bottom me-2 text-muted"></i> Variations
                                                </button>
                                            </li>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                            <li>
                                                <button class="dropdown-item remove-item-btn" wire:click="confirmDelete({{ $product->id }})">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-danger"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="noresult">
                                        <div class="text-center">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                            <h5 class="mt-2">Sorry! No Result Found</h5>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-2">
                {{ $products->links() }}
            </div>
        </div>
    </div>
    @endif

    {{-- Create/Edit Modal Placeholder (Will be separate file included) --}}
    @include('livewire.admin.shop.products.create')
    @include('livewire.admin.shop.products.partials._delete-confirm')
    @include('livewire.admin.shop.products.partials._deactivate-modal')

</div>

@push('scripts')
<!-- Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        // Modal Instances
        const createModal = new bootstrap.Modal(document.getElementById('createProductModal'));
        const variationGalleryModal = new bootstrap.Modal(document.getElementById('variationGalleryModal'));
        const deleteProductModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
        const deactivateProductModal = new bootstrap.Modal(document.getElementById('deactivateProductModal'));

        // Sortable Initialization
        const initShopSortable = () => {
            const elEx = document.getElementById('shop-existing-images');
            if (elEx) {
                new Sortable(elEx, {
                    animation: 150,
                    onEnd: function (evt) {
                        let orderedIds = Array.from(elEx.children).map(el => el.dataset.id);
                        @this.call('reorderImages', orderedIds);
                    }
                });
            }

            const elNew = document.getElementById('shop-new-images');
            if (elNew) {
                new Sortable(elNew, {
                    animation: 150,
                    onEnd: function (evt) {
                        let indices = Array.from(elNew.children).map(el => el.dataset.index);
                        @this.call('reorderNewImages', indices);
                    }
                });
            }
        };

        // Create/Edit Modal Events
        Livewire.on('show-create-modal', () => {
            createModal.show();
            setTimeout(initShopSortable, 500);
        });

        Livewire.on('hide-create-modal', () => { createModal.hide(); });
        Livewire.on('shop-product-created', () => { createModal.hide(); });
        Livewire.on('shop-product-updated', () => { createModal.hide(); });

        // Variation Gallery Events
        Livewire.on('show-variation-gallery-modal', () => {
            variationGalleryModal.show();
        });

        Livewire.on('hide-variation-gallery-modal', () => {
            variationGalleryModal.hide();
        });

        // Delete Modal Events
        Livewire.on('show-product-delete-modal', () => {
            deleteProductModal.show();
        });

        Livewire.on('hide-product-delete-modal', () => {
            deleteProductModal.hide();
            setTimeout(() => {
                if (!document.querySelector('.modal.show')) {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                }
            }, 350);
        });

        // Deactivation Modal Events
        Livewire.on('show-deactivation-modal', () => {
            deactivateProductModal.show();
        });

        Livewire.on('hide-deactivation-modal', () => {
            deactivateProductModal.hide();
            setTimeout(() => {
                if (!document.querySelector('.modal.show')) {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                }
            }, 350);
        });

        // Re-init Sortable on Livewire updates
        Livewire.hook('request', ({ succeed }) => {
            succeed(() => {
                queueMicrotask(() => {
                    initShopSortable();
                });
            });
        });

        // Initial Sortable Init
        initShopSortable();
    });
</script>
@endpush
