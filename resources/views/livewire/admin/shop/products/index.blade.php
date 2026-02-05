<div>
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
                <div class="col-md-3">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
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
                            <th>Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
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
                                             <h5 class="fs-14 m-0"><a href="#" class="text-dark">{{ $product->title }}</a></h5>
                                         </div>
                                    </div>
                                </td>
                                <td>
                                    @foreach($product->categories as $cat)
                                        <span class="badge badge-soft-info text-info">{{ $cat->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @foreach($product->tags as $tag)
                                        <span class="badge badge-soft-secondary text-secondary">{{ $tag->name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="text-body fw-medium">{{ $product->currency }} {{ number_format($product->base_price, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-{{ $product->is_active ? 'success' : 'danger' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
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
                                            <div class="dropdown-divider"></div>
                                            <li>
                                                <a class="dropdown-item remove-item-btn" href="javascript:void(0);">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-danger"></i> Delete
                                                </a>
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

    {{-- Create/Edit Modal Placeholder (Will be separate file included) --}}
    @include('livewire.admin.shop.products.create')
    @include('livewire.admin.shop.products.partials._delete-confirm')

</div>

@push('scripts')
<script>
    window.addEventListener('show-create-modal', event => {
        var myModal = new bootstrap.Modal(document.getElementById('createProductModal'));
        myModal.show();
    });

    window.addEventListener('hide-create-modal', event => {
        var myModalEl = document.getElementById('createProductModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        if (modal) {
            modal.hide();
        }
    });

    window.addEventListener('show-product-delete-modal', event => {
        var myModal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
        myModal.show();
    });

    window.addEventListener('hide-product-delete-modal', event => {
        var myModalEl = document.getElementById('deleteProductModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        if (modal) {
            modal.hide();
        }
    });
</script>
@endpush
