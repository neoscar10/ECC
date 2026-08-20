<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Ownership Tracking</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">The Archive</a></li>
                        <li class="breadcrumb-item active">Ownership</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm-auto">
                            <h5 class="card-title mb-0">Tracked Items</h5>
                        </div>
                        <div class="col-sm">
                            <div class="d-flex justify-content-sm-end gap-2">
                                <div class="search-box ms-2">
                                    <input wire:model.live.debounce.300ms="search" type="text" class="form-control" placeholder="Search products...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive table-card mb-3">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 50px;">ID</th>
                                    <th class="sort">Product</th>
                                    <th class="sort text-center">Remaining Stock</th>
                                    <th class="sort text-center">Total Sold</th>
                                    <th class="sort text-center">Total Owners</th>
                                    <th class="sort text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.archive.ownership.show', $product->id) }}" class="fw-medium link-primary">
                                                #{{ $product->id }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-2">
                                                    @if($product->image_url)
                                                        <img src="{{ $product->image_url }}" alt="" class="avatar-xs rounded-circle">
                                                    @else
                                                        <div class="avatar-xs bg-light rounded-circle"></div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-0">
                                                        <a href="{{ route('admin.archive.ownership.show', $product->id) }}" class="text-reset">
                                                            {{ Str::limit($product->title, 50) }}
                                                        </a>
                                                    </h5>
                                                    @if($product->trashed())
                                                        <span class="badge bg-danger-subtle text-danger mt-1">Deleted</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($product->trashed())
                                                <span class="text-muted">-</span>
                                            @else
                                                <span class="badge bg-light text-body">{{ $product->quantity }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">
                                            @if($product->total_sold > 0)
                                                <span class="text-success">{{ $product->total_sold }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">
                                            @if($product->total_owners > 0)
                                                <span class="text-primary">{{ $product->total_owners }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.archive.ownership.show', $product->id) }}" class="btn btn-sm btn-soft-primary">
                                                View Breakdown
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <div class="noresult py-4">
                                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                                <h5 class="mt-2">No Products Found</h5>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
