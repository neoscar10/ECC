<div>
    {{-- Breadcrumbs --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Size Guides</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Size Guides</li>
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
                    <div class="d-flex justify-content-sm-end gap-2">
                        <button type="button" class="btn btn-success" wire:click="openCreateModal">
                            <i class="ri-add-line align-bottom me-1"></i> Create Size Guide
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{-- Filters --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="search-box ms-2">
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search guides...">
                        <i class="ri-search-line search-icon"></i>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive table-card mb-1">
                <table class="table align-middle">
                    <thead class="table-light text-muted">
                        <tr>
                            <th>Name</th>
                            <th>Products</th>
                            <th>Categories</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guides as $guide)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="{{ route('admin.shop.size-guides.show', $guide->id) }}" class="fw-medium link-primary fs-14 m-0">{{ $guide->name }}</a>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $guide->products()->count() }} Products</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $guide->categories()->count() }} Categories</span>
                                </td>
                                <td>
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ri-more-fill align-middle"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="{{ route('admin.shop.size-guides.show', $guide->id) }}" class="dropdown-item">
                                                    <i class="ri-settings-4-fill align-bottom me-2 text-muted"></i> Configure / Edit
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" wire:click.prevent="editGuide({{ $guide->id }})">
                                                    <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Rename / Info
                                                </button>
                                            </li>
                                            <div class="dropdown-divider"></div>
                                            <li>
                                                <button class="dropdown-item remove-item-btn" wire:click.prevent="deleteGuide({{ $guide->id }})" wire:confirm="Are you sure you want to delete this guide?">
                                                    <i class="ri-delete-bin-fill align-bottom me-2 text-danger"></i> Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">
                                    <div class="noresult">
                                        <div class="text-center py-4">
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
                {{ $guides->links() }}
            </div>
        </div>
    </div>

    {{-- SIZE GUIDE CREATE/EDIT MODAL --}}
    <div class="modal fade" id="sizeGuideModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-dark">
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-bold text-dark">{{ $isEditMode ? 'Edit Size Guide Info' : 'Create Size Guide' }}</h5>
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-white p-4">
                    <form wire:submit.prevent="saveGuide" id="sizeGuideForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-control-label fw-semibold text-muted text-uppercase fs-11">Guide Name</label>
                                <input type="text" class="form-control" wire:model="name" placeholder="e.g. Men's shirts & tops sizing" required>
                                @error('name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-control-label fw-semibold text-muted text-uppercase fs-11">Description (Optional)</label>
                            <textarea class="form-control" wire:model="description" rows="3" placeholder="Additional details about this size guide..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="sizeGuideForm" class="btn btn-primary btn-sm">
                        {{ $isEditMode ? 'Update Guide' : 'Save Guide' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        let sizeGuideModal = new bootstrap.Modal(document.getElementById('sizeGuideModal'));

        Livewire.on('show-guide-modal', () => {
            sizeGuideModal.show();
        });

        Livewire.on('hide-guide-modal', () => {
            sizeGuideModal.hide();
        });
    });
</script>
@endpush
