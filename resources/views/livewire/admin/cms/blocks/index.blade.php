<div>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">CMS Blocks</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">CMS</a></li>
                            <li class="breadcrumb-item active">Blocks</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card" id="blockList">
                    <div class="card-header border-0">
                        <div class="row g-4 align-items-center">
                            <div class="col-sm-3">
                                <h5 class="card-title mb-0 flex-grow-1">Blocks List</h5>
                            </div>
                            <div class="col-sm-auto ms-auto">
                                <div class="hstack gap-2">
                                    <button type="button" class="btn btn-primary add-btn" wire:click="create"><i class="ri-add-line align-bottom me-1"></i> Add Block</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body border border-dashed border-end-0 border-start-0">
                         <div class="row g-3">
                            <div class="col-xxl-5 col-sm-6">
                                <div class="search-box">
                                    <input type="text" class="form-control search" placeholder="Search for blocks..." wire:model.live.debounce.500ms="search">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-xxl-2 col-sm-4">
                                <div>
                                    <select class="form-control" wire:model.live="filterType">
                                        <option value="">All Types</option>
                                        <option value="card">Card</option>
                                        <option value="banner">Banner</option>
                                    </select>
                                </div>
                            </div>
                             <div class="col-xxl-2 col-sm-4">
                                <div>
                                    <select class="form-control" wire:model.live="filterStatus">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                         <div class="table-responsive table-card mb-1">
                            <table class="table align-middle table-nowrap" id="customerTable">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th scope="col" style="width: 50px;">Sort</th>
                                        <th class="sort" data-sort="customer_name">Block</th>
                                        <th class="sort" data-sort="email">Type</th>
                                        <th class="sort" data-sort="phone">Visibility</th>
                                        <th class="sort" data-sort="date">Status</th>
                                        <th class="sort" data-sort="action">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="list form-check-all">
                                    @forelse($blocks as $block)
                                        <tr>
                                            <td>{{ $block->sort_order }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if(isset($block->content['image_url']))
                                                        <div class="flex-shrink-0 me-2">
                                                            <img src="{{ $block->content['image_url'] }}" alt="" class="avatar-xs rounded-3">
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h5 class="fs-14 mb-0"><a href="javascript:void(0);" class="text-body">{{ $block->title }}</a></h5>
                                                        <p class="text-muted mb-0">{{ Str::limit($block->content['body'] ?? '', 30) }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-info-subtle text-info text-uppercase">{{ $block->type }}</span></td>
                                            <td>
                                                @if($block->restriction_mode === 'public')
                                                    <span class="badge bg-success-subtle text-success">Public</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning">Restricted</span>
                                                    @if($block->blur_enabled)
                                                        <span class="badge bg-secondary-subtle text-secondary">+ Blur</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if($block->is_active)
                                                    <span class="badge badge-soft-success text-uppercase">Active</span>
                                                @else
                                                    <span class="badge badge-soft-danger text-uppercase">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <div class="edit">
                                                        <button class="btn btn-sm btn-success edit-item-btn" wire:click="edit({{ $block->id }})">Edit</button>
                                                    </div>
                                                    <div class="remove">
                                                        <button class="btn btn-sm btn-danger remove-item-btn" wire:confirm="Are you sure you want to delete this block?" wire:click="delete({{ $block->id }})">Remove</button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No blocks found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end">
                            {{ $blocks->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modals -->
    @include('livewire.admin.cms.blocks.partials._create-modal')

    <!-- Script for Modal Handling -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            var createModal = new bootstrap.Modal(document.getElementById('createBlockModal'));
            Livewire.on('show-create-modal', () => { createModal.show(); });
            Livewire.on('hide-create-modal', () => { createModal.hide(); });
            Livewire.on('refresh-blocks', () => { createModal.hide(); });
        });
    </script>
</div>
