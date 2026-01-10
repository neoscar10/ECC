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
             @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card" id="customerList">
                <div class="card-header border-0">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <div class="search-box">
                                <input type="text" class="form-control search" wire:model.live.debounce.300ms="search" placeholder="Search tiers...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <div class="col-sm-auto ms-auto">
                            @role('super_admin')
                            <div class="hstack gap-2">
                                <button type="button" class="btn btn-success add-btn" wire:click="create"><i class="ri-add-line align-bottom me-1"></i> Add Tier</button>
                            </div>
                            @endrole
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="table-card mb-4">
                        <table class="table align-middle table-nowrap mb-0" id="customerTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Sort</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse ($tiers as $tier)
                                    <tr>
                                        <td>{{ $tier->sort_order }}</td>
                                        <td><h5 class="fs-14 mb-1">{{ $tier->name }}</h5></td>
                                        <td class="text-muted">{{ $tier->code }}</td>
                                        <td>{{ $tier->currency }} {{ number_format($tier->price, 2) }}</td>
                                        <td>{{ $tier->duration_days }} days</td>
                                        <td>
                                            @if($tier->is_active)
                                                <span class="badge bg-success-subtle text-success text-uppercase">Active</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger text-uppercase">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a href="#" role="button" id="dropdownMenuLink{{ $tier->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-2-fill"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink{{ $tier->id }}">
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="edit({{ $tier->id }})"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit/View</a></li>
                                                    @role('super_admin')
                                                    <li><a class="dropdown-item" href="#" wire:click.prevent="confirmDelete({{ $tier->id }})"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete</a></li>
                                                    @endrole
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="noresult">
                                                <div class="text-center">
                                                    <h5 class="mt-2">No tiers found</h5>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        {{ $tiers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Init Modal (Create/Edit) -->
    <div wire:ignore.self class="modal fade" id="initModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $isEditMode ? 'Edit Membership Tier' : 'Add Membership Tier' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="{{ $isEditMode ? 'update' : 'store' }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" wire:model="name" placeholder="E.g. Gold Tier">
                                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Code (Unique Slug)</label>
                                <input type="text" class="form-control" wire:model="code" placeholder="E.g. gold_tier">
                                @error('code') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price ({{ $currency }})</label>
                                <input type="number" step="0.01" class="form-control" wire:model="price">
                                @error('price') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Duration (Days)</label>
                                <input type="number" class="form-control" wire:model="duration_days">
                                @error('duration_days') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" wire:model="sort_order">
                                @error('sort_order') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Upgrade From (Optional)</label>
                                <select class="form-select" wire:model="upgrade_from_id">
                                    <option value="">None / New Base Tier</option>
                                    @foreach($tiers as $tierOption)
                                        @if(!$isEditMode || $tierOption->id != $tierId)
                                            <option value="{{ $tierOption->id }}">{{ $tierOption->name }} ({{ $tierOption->code }})</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('upgrade_from_id') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch form-switch-lg" dir="ltr">
                                    <input type="checkbox" class="form-check-input" id="isActive" wire:model="is_active">
                                    <label class="form-check-label" for="isActive">Active Status</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch form-switch-lg" dir="ltr">
                                    <input type="checkbox" class="form-check-input" id="requiresApproval" wire:model="requires_approval">
                                    <label class="form-check-label" for="requiresApproval">Require Approval</label>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <h6 class="fw-semibold">Privileges</h6>
                                <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                    <div class="row">
                                        @forelse($allPrivileges as $privilege)
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="{{ $privilege->id }}" wire:model="selectedPrivileges" id="priv_{{ $privilege->id }}">
                                                    <label class="form-check-label" for="priv_{{ $privilege->id }}">
                                                        {{ $privilege->name }}
                                                    </label>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12 text-muted">No privileges defined in system.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    @role('super_admin')
                    <button type="button" class="btn btn-primary" wire:click="{{ $isEditMode ? 'update' : 'store' }}">
                        {{ $isEditMode ? 'Update Tier' : 'Create Tier' }}
                    </button>
                    @endrole
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-5">
                     <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#405189,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                    <div class="mt-4">
                        <h4 class="mb-3">Are you sure?</h4>
                        <p class="text-muted mb-4">Are you sure you want to delete this tier? This action cannot be undone.</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" wire:click="delete">Yes, Delete It</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-init-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('initModal'));
                modal.show();
            });
            Livewire.on('open-delete-modal', () => {
                var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                modal.show();
            });
             Livewire.on('close-modals', () => {
                var modals = document.querySelectorAll('.modal.show');
                modals.forEach(function(modalEl) {
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                });
            });
        });
    </script>
</div>
