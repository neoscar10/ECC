<div>
    <!-- Page Title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-sm-0">Inventory</h4>
                    <p class="text-muted mb-0">Track and adjust stock across products and variations.</p>
                </div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Inventory</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters (Single Row) -->
    <div class="card">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-md-row flex-wrap gap-3 align-items-center">
                {{-- Search --}}
                <div class="search-box flex-grow-1" style="min-width: 200px;">
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search products...">
                    <i class="ri-search-line search-icon"></i>
                </div>

                {{-- Status --}}
                <div style="min-width: 140px;">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="all">Status: All</option>
                        <option value="in_stock">In Stock</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>

                {{-- Type --}}
                <div style="min-width: 130px;">
                    <select class="form-select" wire:model.live="filterType">
                        <option value="all">Type: All</option>
                        <option value="simple">Simple</option>
                        <option value="variant">Variant</option>
                    </select>
                </div>

                {{-- Sort Group (Sort Field + Direction) --}}
                <div style="min-width: 260px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted small text-uppercase fw-semibold">Sort</span>
                        <select class="form-select" wire:model.live="sortField" aria-label="Sort by">
                            <option value="updated_at">Recently Updated</option>
                            <option value="title">Product Name</option>
                            <option value="stock">Total Stock</option>
                        </select>
                        <button type="button" class="btn {{ $sortDirection === 'asc' ? 'btn-primary' : 'btn-outline-primary' }}" 
                                wire:click="$set('sortDirection', 'asc')"
                                title="Ascending">
                            Asc
                        </button>
                         <button type="button" class="btn {{ $sortDirection === 'desc' ? 'btn-primary' : 'btn-outline-primary' }}" 
                                wire:click="$set('sortDirection', 'desc')"
                                title="Descending">
                            Desc
                        </button>
                    </div>
                </div>

                {{-- Total Count --}}
                 <div class="text-nowrap ms-md-2 text-muted fw-medium">
                    Total: <span class="text-primary">{{ $products->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Type</th>
                            <th scope="col">Total Stock</th>
                            <th scope="col">Status</th>
                            <th scope="col">Last Updated</th>
                            <th scope="col" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php
                                $totalStock = $product->total_computed_stock;
                                $isLow = $product->is_low_stock;
                                $isOut = $product->is_out_of_stock;
                                $statusBadge = $isOut ? 'bg-danger' : ($isLow ? 'bg-warning text-dark' : 'bg-success');
                                $statusLabel = $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'In Stock');
                                $isVariant = $product->variation_groups_count > 0;
                            @endphp
                            <tr class="{{ $isOut ? 'table-danger-subtle' : ($isLow ? 'table-warning-subtle' : '') }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-2">
                                            @if($product->images->first())
                                                <img src="{{ asset('storage/' . $product->images->first()->image_path) }}" alt="" class="avatar-sm rounded bg-light">
                                            @else
                                                <div class="avatar-sm bg-light rounded d-flex align-items-center justify-content-center">
                                                    <i class="ri-image-2-line fs-20 text-muted"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="fs-14 mb-1">
                                                <a href="{{ route('admin.shop.products') }}" class="text-dark">{{ $product->title }}</a>
                                            </h5>
                                            <p class="text-muted mb-0 small">{{ $product->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($isVariant)
                                        <span class="badge bg-secondary">Variant</span>
                                    @else
                                        <span class="badge bg-primary">Simple</span>
                                    @endif
                                </td>
                                <td>
                                    <h5 class="fs-14 mb-0 fw-bold">{{ $totalStock }}</h5>
                                </td>
                                <td>
                                    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                </td>
                                <td>{{ $product->updated_at->format('d M, Y h:i A') }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" wire:click="openAdjustStockModal({{ $product->id }})">
                                                    <i class="ri-pencil-line align-bottom me-2 text-muted"></i> Adjust Stock
                                                </button>
                                            </li>
                                             <li>
                                                <a href="{{ route('admin.shop.orders') }}?search={{ $product->title }}" class="dropdown-item">
                                                    <i class="ri-list-check align-bottom me-2 text-muted"></i> View Orders
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="ri-search-2-line fs-24"></i>
                                        <p class="mt-2 text-muted">No items found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    @include('livewire.admin.shop.inventory.partials._adjust-stock-modal')
</div>
