<div>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Navigation Links Settings</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active">Navigation Links</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm mb-0">
                <div class="d-flex align-items-center">
                    <i class="ri-information-line fs-3 me-2"></i>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">About Navigation Links</h6>
                        <p class="mb-0">These labels control the names of the main navigation items in the user frontend and the mobile application. Changing them here will instantly reflect across the platform.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">App Navigation Labels</h5>
                </div>
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form wire:submit="save">
                        <div class="alert alert-warning mb-3">
                            <i class="ri-drag-move-2-line align-middle me-1"></i> Drag and drop the items below to reorder how they appear in the navigation menu.
                        </div>

                        <ul class="list-group mb-4" wire:sortable="updateOrder">
                            @foreach($items as $index => $item)
                                <li class="list-group-item d-flex align-items-center" wire:sortable.item="{{ $item['key'] }}" wire:key="item-{{ $item['key'] }}">
                                    <i class="ri-drag-move-2-line text-muted me-3" wire:sortable.handle style="cursor: grab; font-size: 1.2rem;" title="Drag to reorder"></i>
                                    
                                    <div class="flex-grow-1 row align-items-center mb-0">
                                        <div class="col-md-4">
                                            <label for="item_{{ $item['key'] }}" class="form-label mb-0 fw-semibold text-capitalize">{{ $item['key'] }} Link Label</label>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" id="item_{{ $item['key'] }}" wire:model="items.{{ $index }}.label" placeholder="{{ ucfirst($item['key']) }}" maxlength="15">
                                            @error("items.{$index}.label") <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <span wire:loading.remove wire:target="save">Save Changes</span>
                                <span wire:loading wire:target="save">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
