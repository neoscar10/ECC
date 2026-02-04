<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Shop Tags</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Tags</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            @include('livewire.admin.partials._alerts')
        </div>
    </div>

    <div class="row">
        {{-- Left Pane: Group List --}}
        <div class="col-lg-3">
            <div class="card" style="height: calc(100vh - 180px);">
                <div class="card-header border-bottom-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title text-uppercase fw-semibold mb-0">Tag Groups</h5>
                        <button class="btn btn-success btn-sm btn-icon" wire:click="initiateCreateGroup" title="Add Group">
                            <i class="ri-add-line"></i>
                        </button>
                    </div>
                </div>
                <div class="p-2 border-bottom">
                     <div class="search-box">
                        <input type="text" class="form-control" placeholder="Search groups..." wire:model.live.debounce.300ms="searchGroups">
                        <i class="ri-search-line search-icon"></i>
                     </div>
                </div>
                <div class="card-body p-0" data-simplebar style="height: 100%; overflow-y: auto;">
                    <ul class="list-group list-group-flush">
                        @forelse($groups as $group)
                            <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center cursor-pointer {{ $currentGroupId == $group->id ? 'bg-light text-primary fw-medium' : '' }}"
                                wire:click="selectGroup({{ $group->id }})">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <i class="ri-folder-3-line fs-16 me-2 {{ $currentGroupId == $group->id ? 'text-primary' : 'text-muted' }}"></i>
                                    <span class="text-truncate">{{ $group->name }}</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-ghost-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" wire:click.stop>
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="javascript:void(0);" wire:click.stop="initiateRenameGroup({{ $group->id }})"><i class="ri-pencil-fill me-2 align-bottom text-muted"></i> Rename</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0);" wire:click.stop="toggleGroupActive({{ $group->id }})">
                                            <i class="{{ $group->is_active ? 'ri-eye-line' : 'ri-eye-off-line' }} me-2 align-bottom text-muted"></i> 
                                            {{ $group->is_active ? 'Deactivate' : 'Activate' }}
                                        </a></li>
                                        <li class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0);" wire:click.stop="initiateDeleteGroup({{ $group->id }})"><i class="ri-delete-bin-fill me-2 align-bottom text-danger"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted text-center py-3 fs-12">No groups found.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right Pane: Tags List --}}
        <div class="col-lg-9">
            <div class="card" style="height: calc(100vh - 180px);">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 overflow-hidden">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item text-muted">All Tag Groups</li>
                                    @if($currentGroup)
                                        <li class="breadcrumb-item active fw-medium">{{ $currentGroup->name }}</li>
                                    @else
                                        <li class="breadcrumb-item active text-muted">(Select a group)</li>
                                    @endif
                                </ol>
                            </nav>
                        </div>
                        @if($currentGroupId)
                            <div class="d-flex gap-2 ms-2">
                                <div class="search-box">
                                    <input type="text" class="form-control" placeholder="Search tags in {{ $currentGroup->name }}..." wire:model.live.debounce.300ms="searchTags">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                                <button class="btn btn-success" wire:click="initiateCreateTag">
                                    <i class="ri-price-tag-3-line align-bottom me-1"></i> New Tag
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0 table-responsive" data-simplebar>
                    @if($currentGroupId)
                        <table class="table table-hover table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 50px;">
                                        <i class="ri-price-tag-3-fill text-muted"></i>
                                    </th>
                                    <th scope="col">Name</th>
                                    <th scope="col" style="width: 100px;">Status</th>
                                    <th scope="col" style="width: 120px;">Created</th>
                                    <th scope="col" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tags as $tag)
                                    <tr>
                                        <td class="text-center">
                                            <i class="ri-price-tag-2-line fs-20 text-info"></i>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-medium">{{ $tag->name }}</span>
                                                <span class="text-muted fs-11">{{ $tag->slug }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" 
                                                       id="switch-tag-{{ $tag->id }}" 
                                                       wire:click="toggleTagActive({{ $tag->id }})"
                                                       {{ $tag->is_active ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td class="text-muted fs-12">
                                            {{ $tag->created_at->format('d M Y') }}
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" wire:click.stop>
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="javascript:void(0);" wire:click="initiateRenameTag({{ $tag->id }})"><i class="ri-pencil-fill me-2 align-bottom text-muted"></i> Rename</a></li>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" wire:click="initiateMoveTag({{ $tag->id }})"><i class="ri-drag-move-fill me-2 align-bottom text-muted"></i> Move</a></li>
                                                    <li class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="javascript:void(0);" wire:click="initiateDeleteTag({{ $tag->id }})"><i class="ri-delete-bin-fill me-2 align-bottom text-danger"></i> Delete</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-price-tag-3-line fs-24 mb-2 d-block"></i>
                                                <p class="mb-0">No tags found in this group.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        <div class="px-3 py-2">
                             {{ $tags->links() }}
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                            <i class="ri-layout-masonry-line fs-48 mb-3"></i>
                            <h5>Select a Tag Group</h5>
                            <p>Choose a group from the left to manage its tags.</p>
                        </div>
                    @endif
                </div>
                @if($currentGroupId && $tags->count() > 0)
                <div class="card-footer py-2 bg-light bg-opacity-50">
                    <span class="text-muted fs-12">{{ $tags->total() }} tags</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- GROUP MODALS --}}

    {{-- Create Group Modal --}}
    <div wire:ignore.self class="modal fade" id="createGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Tag Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Group Name</label>
                        <input type="text" class="form-control" wire:model="name" placeholder="e.g. Brands">
                        @error('name') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="storeGroup">Create</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rename Group Modal --}}
    <div wire:ignore.self class="modal fade" id="renameGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" wire:model="name">
                        @error('name') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateGroup">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Group Modal --}}
    <div wire:ignore.self class="modal fade" id="deleteGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="text-end">
                        <button type="button" class="btn-close text-end" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="mt-2">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <h4 class="mb-3 mt-4">Are you sure?</h4>
                        <p class="text-muted fs-15 mb-4">Delete this tag group?</p>
                        @error('delete_group') 
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" wire:click="destroyGroup">Yes, Delete It!</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- TAG MODALS --}}

    {{-- Create Tag Modal --}}
    <div wire:ignore.self class="modal fade" id="createTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tag Name</label>
                        <input type="text" class="form-control" wire:model="name" placeholder="e.g. Nike">
                        @error('name') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="storeTag">Create</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rename Tag Modal --}}
    <div wire:ignore.self class="modal fade" id="renameTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" wire:model="name">
                        @error('name') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateTag">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Move Tag Modal --}}
    <div wire:ignore.self class="modal fade" id="moveTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Move Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Move to Group:</label>
                        <select class="form-select" wire:model="targetGroupId">
                            <option value="">Select Group...</option>
                            @foreach($groups as $group)
                                @if($group->id != $currentGroupId)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('targetGroupId') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" wire:click="moveTag">Move</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Tag Modal --}}
    <div wire:ignore.self class="modal fade" id="deleteTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="text-end">
                        <button type="button" class="btn-close text-end" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="mt-2">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <h4 class="mb-3 mt-4">Are you sure?</h4>
                        <p class="text-muted fs-15 mb-4">Delete this tag?</p>
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" wire:click="destroyTag">Yes, Delete It!</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
             // Groups
             const createGroupModal = new bootstrap.Modal(document.getElementById('createGroupModal'));
             const renameGroupModal = new bootstrap.Modal(document.getElementById('renameGroupModal'));
             const deleteGroupModal = new bootstrap.Modal(document.getElementById('deleteGroupModal'));

             @this.on('show-create-group-modal', () => createGroupModal.show());
             @this.on('hide-create-group-modal', () => createGroupModal.hide());

             @this.on('show-rename-group-modal', () => renameGroupModal.show());
             @this.on('hide-rename-group-modal', () => renameGroupModal.hide());

             @this.on('show-delete-group-modal', () => deleteGroupModal.show());
             @this.on('hide-delete-group-modal', () => deleteGroupModal.hide());

             // Tags
             const createTagModal = new bootstrap.Modal(document.getElementById('createTagModal'));
             const renameTagModal = new bootstrap.Modal(document.getElementById('renameTagModal'));
             const moveTagModal = new bootstrap.Modal(document.getElementById('moveTagModal'));
             const deleteTagModal = new bootstrap.Modal(document.getElementById('deleteTagModal'));

             @this.on('show-create-tag-modal', () => createTagModal.show());
             @this.on('hide-create-tag-modal', () => createTagModal.hide());

             @this.on('show-rename-tag-modal', () => renameTagModal.show());
             @this.on('hide-rename-tag-modal', () => renameTagModal.hide());

             @this.on('show-move-tag-modal', () => moveTagModal.show());
             @this.on('hide-move-tag-modal', () => moveTagModal.hide());

             @this.on('show-delete-tag-modal', () => deleteTagModal.show());
             @this.on('hide-delete-tag-modal', () => deleteTagModal.hide());
        });
    </script>
</div>
