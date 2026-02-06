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

    <!-- Filters -->
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-xxl-3 col-sm-4">
                    <div class="search-box">
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search products...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
                <div class="col-xxl-2 col-sm-4">
                    <select class="form-select" wire:model.live="filterStatus">
                        <option value="all">Status: All</option>
                        <option value="in_stock">In Stock</option>
                        <option value="low_stock">Low Stock (≤ {{ $lowStockThreshold }})</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-4">
                    <select class="form-select" wire:model.live="filterType">
                        <option value="all">Type: All</option>
                        <option value="simple">Simple</option>
                        <option value="variant">Variant</option>
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-4">
                    <select class="form-select" wire:model.live="sortField">
                        <option value="updated_at">Recently Updated</option>
                        <option value="title">Name</option>
                        <option value="stock">Stock Quantity</option>
                    </select>
                </div>
                <div class="col-xxl-2 col-sm-4">
                     <select class="form-select" wire:model.live="sortDirection">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
                 <div class="col-xxl-1 col-sm-4 d-flex align-items-center justify-content-end">
                    <span class="text-muted small">Total: {{ $products->total() }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-nowrap align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 50px;"></th>
                            <th scope="col">Product</th>
                            <th scope="col">Type</th>
                            <th scope="col">Total Stock</th>
                            <th scope="col">Status</th>
                            <th scope="col">Last Updated</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            @php
                                $totalStock = $product->total_computed_stock;
                                $isExpanded = in_array($product->id, $expandedRows);
                                $statusBadge = $totalStock == 0 ? 'bg-danger' : ($totalStock <= $lowStockThreshold ? 'bg-warning text-dark' : 'bg-success');
                                $statusLabel = $totalStock == 0 ? 'Out of Stock' : ($totalStock <= $lowStockThreshold ? 'Low Stock' : 'In Stock');
                            @endphp
                            <tr class="{{ $totalStock <= $lowStockThreshold ? ($totalStock == 0 ? 'table-danger-subtle' : 'table-warning-subtle') : '' }}">
                                <td>
                                    <button type="button" class="btn btn-sm btn-ghost-primary p-1" wire:click="toggleExpand({{ $product->id }})">
                                        <i class="ri-arrow-{{ $isExpanded ? 'down' : 'right' }}-s-line fs-16"></i>
                                    </button>
                                </td>
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
                                    @if($product->variation_groups_count > 0)
                                        <span class="badge border border-primary text-primary">Variant</span>
                                    @else
                                        <span class="badge border border-secondary text-secondary">Simple</span>
                                    @endif
                                </td>
                                <td>
                                    <h5 class="fs-14 mb-0">{{ $totalStock }}</h5>
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
                                                <button class="dropdown-item" wire:click="toggleExpand({{ $product->id }})">
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
                            
                            <!-- Expanded Detail Row -->
                            @if($isExpanded)
                            <tr>
                                <td colspan="7" class="p-0 bg-light-subtle">
                                    <div class="p-4 border-bottom">
                                        <div class="card mb-0 border shadow-none">
                                            <div class="card-header bg-light-subtle">
                                                <h6 class="card-title mb-0">Stock Management: {{ $product->title }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <!-- Simple Product Edit -->
                                                @if($product->variation_groups_count == 0)
                                                    <div class="row align-items-end">
                                                        <div class="col-sm-4">
                                                            <label class="form-label">Current Quantity</label>
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" wire:model="simpleStockValues.{{ $product->id }}" min="0">
                                                                <button class="btn btn-primary" wire:click="saveSimpleStock({{ $product->id }})" wire:loading.attr="disabled">
                                                                    <span wire:loading.remove wire:target="saveSimpleStock({{ $product->id }})">Save</span>
                                                                    <span wire:loading wire:target="saveSimpleStock({{ $product->id }})"><i class="ri-loader-4-line ri-spin"></i></span>
                                                                </button>
                                                            </div>
                                                            @error("simpleStockValues.{$product->id}") <span class="text-danger small">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div class="col-sm-8">
                                                            <p class="text-muted mb-0 mt-3"><i class="ri-information-line me-1"></i> Simple products have a single stock count.</p>
                                                        </div>
                                                    </div>
                                                @else
                                                <!-- Variant Product Edit -->
                                                    <div class="vstack gap-3">
                                                        @foreach($product->variationGroups as $group)
                                                            <div class="border rounded p-3">
                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                    <h6 class="mb-0 fw-semibold">{{ $group->name }} <span class="text-muted fw-normal ms-1">({{ $group->presentation_type }})</span></h6>
                                                                </div>
                                                                
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-borderless align-middle mb-0">
                                                                        <thead class="text-muted">
                                                                            <tr>
                                                                                <th>Variation</th>
                                                                                <th>Price Modifier</th>
                                                                                <th style="width: 200px;">Stock</th>
                                                                                <th style="width: 100px;">Action</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($group->values as $value)
                                                                                <tr>
                                                                                    <td>
                                                                                        <div class="d-flex align-items-center">
                                                                                            @if($value->presentation_image_path)
                                                                                                <img src="{{ asset('storage/'.$value->presentation_image_path) }}" class="avatar-xxs rounded me-2">
                                                                                            @elseif($value->color_hex)
                                                                                                <span class="avatar-xxs rounded me-2 d-inline-block" style="background-color: {{ $value->color_hex }}; border: 1px solid #ddd;"></span>
                                                                                            @endif
                                                                                            {{ $value->caption }}
                                                                                            @if($value->is_default) <span class="badge bg-info-subtle text-info ms-2">Default</span> @endif
                                                                                        </div>
                                                                                    </td>
                                                                                    <td>
                                                                                        {{ $product->currency }} {{ $value->price > 0 ? $value->price : $product->base_price }}
                                                                                    </td>
                                                                                    <td>
                                                                                        <input type="number" class="form-control form-control-sm" 
                                                                                               wire:model="variationStockValues.{{ $value->id }}" 
                                                                                               min="0">
                                                                                        @error("variationStockValues.{$value->id}") <span class="text-danger small d-block mt-1">{{ $message }}</span> @enderror
                                                                                    </td>
                                                                                    <td>
                                                                                         <button class="btn btn-sm btn-soft-success" wire:click="saveVariationStock({{ $value->id }})">
                                                                                            <i class="ri-save-line align-middle"></i>
                                                                                        </button>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
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
</div>
