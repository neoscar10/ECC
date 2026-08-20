<div>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Address Groups</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Address Settings</a></li>
                        <li class="breadcrumb-item active">Groups</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Shipping Address Groups</h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary add-btn" wire:click="openCreateModal">
                                <i class="ri-add-line align-bottom me-1"></i> Create Group
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body border border-dashed border-end-0 border-start-0 mt-3">
                    <div class="row g-3">
                        <div class="col-xxl-5 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Search groups..." wire:model.live.debounce.500ms="search">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card mb-1">
                        <table class="table align-middle table-nowrap">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Name</th>
                                    <th>Fields Collected</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groups as $group)
                                <tr>
                                    <td>
                                        <h5 class="fs-14 mb-1">{{ $group->name }}</h5>
                                    </td>
                                    <td>
                                        @if(is_array($group->fields))
                                            {{ count($group->fields) }} fields
                                        @else
                                            0 fields
                                        @endif
                                    </td>
                                    <td>
                                        @if($group->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item">
                                                <button type="button" class="btn btn-sm btn-soft-primary" wire:click="editGroup({{ $group->id }})">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                            </li>
                                            <li class="list-inline-item">
                                                <button type="button" class="btn btn-sm btn-soft-danger" 
                                                    wire:click="deleteGroup({{ $group->id }})" 
                                                    wire:confirm="Are you sure you want to delete this group?">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted p-4">
                                        <i class="ri-map-pin-line fs-24 d-block mb-2"></i>
                                        No address groups found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        {{ $groups->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="addressGroupModal" tabindex="-1" aria-labelledby="addressGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addressGroupModalLabel">{{ $isEditMode ? 'Edit' : 'Create' }} Address Group</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-9">
                                <label for="name" class="form-label">Group Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" class="form-control" wire:model="name" placeholder="e.g. Standard International">
                                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch form-switch-lg mt-1" dir="ltr">
                                    <input type="checkbox" class="form-check-input" id="is_active" wire:model="is_active">
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <h6 class="fs-14 mb-3">Configure Fields</h6>
                                <p class="text-muted small">Select which fields to collect from the user, and whether they are required.</p>
                                
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Field</th>
                                                <th class="text-center">Collect?</th>
                                                <th class="text-center">Required?</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($availableFields as $key => $label)
                                                <tr>
                                                    <td>{{ $label }} <code>{{ $key }}</code></td>
                                                    <td class="text-center">
                                                        <div class="form-check d-inline-block">
                                                            <input class="form-check-input" type="checkbox" wire:model.live="fields.{{ $key }}.is_collected">
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check d-inline-block">
                                                            <input class="form-check-input" type="checkbox" wire:model="fields.{{ $key }}.is_required" @if(!($fields[$key]['is_collected'] ?? false)) disabled @endif>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">Save Group</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-modal', (id) => {
                let modal = new bootstrap.Modal(document.getElementById(id[0]));
                modal.show();
            });

            Livewire.on('hide-modal', (id) => {
                let modalEl = document.getElementById(id[0]);
                let modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) {
                    modal.hide();
                }
            });
            
            Livewire.on('notify', (event) => {
                const data = event[0];
                Toastify({
                    text: data.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: data.type === 'error' ? "#f06548" : "#0ab39c",
                }).showToast();
            });
        });
    </script>
    @endpush
</div>
