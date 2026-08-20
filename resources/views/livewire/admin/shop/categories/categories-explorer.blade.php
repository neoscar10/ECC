<div>
    {{-- Page Header --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Shop Categories</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Shop</a></li>
                        <li class="breadcrumb-item active">Categories</li>
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
        {{-- Left Pane: Tree View --}}
        <div class="col-lg-3">
            <div class="card" style="height: calc(100vh - 180px);">
                <div class="card-header border-bottom-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title text-uppercase fw-semibold mb-0 flex-grow-1">All Categories</h5>
                        <button class="btn btn-ghost-primary btn-icon btn-sm" wire:click="openFolder(null)" title="Go to Root">
                            <i class="ri-home-4-line"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0" data-simplebar style="height: 100%; overflow-y: auto;">
                    <ul class="nav flex-column p-2">
                        @foreach($this->treeRoots as $root)
                            @include('livewire.admin.shop.categories.partials._tree-node', [
                                'category' => $root, 
                                'currentFolderId' => $currentFolderId, 
                                'expandedIds' => $expandedIds,
                                'depth' => 0
                            ])
                        @endforeach
                        
                        @if($this->treeRoots->isEmpty())
                            <li class="text-muted text-center py-3 fs-12">No categories found.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right Pane: Folder View --}}
        <div class="col-lg-9">
            <div class="card" style="height: calc(100vh - 180px);">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-grow-1 overflow-hidden">
                            {{-- Breadcrumb --}}
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item">
                                        <a href="javascript:void(0);" wire:click="openFolder(null)" class="{{ is_null($currentFolderId) ? 'fw-bold text-primary' : 'text-muted' }}">
                                            <i class="ri-home-4-line align-middle"></i> Root
                                        </a>
                                    </li>
                                    @foreach($this->breadcrumbs as $crumb)
                                        <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                            @if(!$loop->last)
                                                <a href="javascript:void(0);" wire:click="openFolder({{ $crumb->id }})">{{ $crumb->name }}</a>
                                            @else
                                                <span class="fw-medium">{{ $crumb->name }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            </nav>
                        </div>
                        <div class="d-flex gap-2 ms-2">
                            @if($currentFolderId)
                                <button class="btn btn-soft-secondary btn-icon" wire:click="navigateUp" title="Up One Level">
                                    <i class="ri-arrow-up-line"></i>
                                </button>
                            @endif
                            <div class="search-box">
                                <input type="text" class="form-control" placeholder="Search in this folder..." wire:model.live.debounce.300ms="search">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            @if($this->currentFolder && $this->currentFolder->children_count == 0)
                                @php
                                    $defaultsConfigured = $this->hasCategoryDefaults($this->currentFolder);
                                @endphp
                                <span class="d-inline-block" tabindex="0" @if(!$defaultsConfigured) data-bs-toggle="tooltip" data-bs-placement="top" title="Please configure Category Defaults before adding products" @endif>
                                    <button class="btn btn-primary" @if(!$defaultsConfigured) disabled style="pointer-events: none;" @else wire:click="initiateCreateProduct" @endif>
                                        <i class="ri-add-line align-bottom me-1"></i> Add Product
                                    </button>
                                </span>
                            @else
                                <button class="btn btn-success" wire:click="initiateCreate">
                                    <i class="ri-add-line align-bottom me-1"></i> New Category
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body p-0 table-responsive" data-simplebar>
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 50px;">
                                    <i class="ri-folder-3-line text-muted"></i>
                                </th>
                                <th scope="col">Name</th>
                                <th scope="col" style="width: 100px;">Status</th>
                                <th scope="col" style="width: 150px;">Subcategories</th>
                                <th scope="col" style="width: 120px;">Created</th>
                                <th scope="col" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->folderContents as $category)
                                <tr wire:dblclick="openFolder({{ $category->id }})" class="cursor-pointer">
                                    <td class="text-center">
                                        <i class="ri-folder-fill fs-20 text-warning"></i>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <a href="javascript:void(0);" wire:click="openFolder({{ $category->id }})" class="text-reset fw-medium">
                                                {{ $category->name }}
                                            </a>
                                            <span class="text-muted fs-11">/{{ $category->slug }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="switch-{{ $category->id }}" 
                                                   wire:click.stop="toggleActive({{ $category->id }})"
                                                   {{ $category->is_active ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        @if($category->children_count > 0)
                                            <span class="badge bg-secondary-subtle text-secondary">{{ $category->children_count }} items</span>
                                        @else
                                            <span class="text-muted fs-12">-</span>
                                        @endif
                                    </td>
                                    <td class="text-muted fs-12">
                                        {{ $category->created_at->format('d M Y') }}
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" wire:click.stop>
                                                <i class="ri-more-2-fill"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="javascript:void(0);" wire:click.stop="openFolder({{ $category->id }})"><i class="ri-folder-open-line me-2 align-bottom text-muted"></i> Open</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);" wire:click.stop="initiateRename({{ $category->id }})"><i class="ri-pencil-fill me-2 align-bottom text-muted"></i> Rename</a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0);" wire:click.stop="initiateMove({{ $category->id }})"><i class="ri-drag-move-fill me-2 align-bottom text-muted"></i> Move</a></li>
                                                <li class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0);" wire:click.stop="initiateDelete({{ $category->id }})"><i class="ri-delete-bin-fill me-2 align-bottom text-danger"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            @if($this->currentFolder && $this->currentFolder->children_count == 0)
                                                <i class="ri-shopping-bag-line fs-24 mb-2 d-block text-primary"></i>
                                                <p class="mb-3">No products in this category yet.</p>
                                                @php
                                                    $defaultsConfigured = $this->hasCategoryDefaults($this->currentFolder);
                                                @endphp
                                                <span class="d-inline-block" tabindex="0" @if(!$defaultsConfigured) data-bs-toggle="tooltip" data-bs-placement="top" title="Please configure Category Defaults before adding products" @endif>
                                                    <button class="btn btn-primary btn-sm" @if(!$defaultsConfigured) disabled style="pointer-events: none;" @else wire:click="initiateCreateProduct" @endif>
                                                        <i class="ri-add-line align-bottom me-1"></i> Add Product
                                                    </button>
                                                </span>
                                            @else
                                                <i class="ri-folder-add-line fs-24 mb-2 d-block"></i>
                                                <p class="mb-0">This folder is empty.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer py-2 bg-light bg-opacity-50">
                    <span class="text-muted fs-12">{{ $this->folderContents->count() }} items</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div wire:ignore.self class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Folder Name</label>
                        <input type="text" class="form-control" wire:model="name" placeholder="e.g. Bats">
                        @error('name') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Size Guide (Optional)</label>
                        <select class="form-select" wire:model="size_guide_id">
                            <option value="">None</option>
                            @foreach($sizeGuides as $guide)
                                <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Products in this folder will inherit this size guide.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                         <input class="form-check-input" type="checkbox" role="switch" wire:model="is_active" id="createActive">
                         <label class="form-check-label" for="createActive">Active immediately</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="store">Create</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Rename Modal --}}
    <div wire:ignore.self class="modal fade" id="renameCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rename Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" wire:model="name">
                        @error('name') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Size Guide (Optional)</label>
                        <select class="form-select" wire:model="size_guide_id">
                            <option value="">None</option>
                            @foreach($sizeGuides as $guide)
                                <option value="{{ $guide->id }}">{{ $guide->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Products in this folder will inherit this size guide.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="updateRename">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Move Modal --}}
    <div wire:ignore.self class="modal fade" id="moveCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Move Folder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Select destination parent folder:</p>
                    <div class="mb-3">
                         <select class="form-control" wire:model="targetParentId">
                             <option value="">(Root)</option>
                             {{-- Flat list for simplicity in move modal or implement tree dropdown later --}}
                             {{-- For MVP, let's just show root and 1 level deep or just root + direct children of root --}}
                             {{-- Actually, for proper move we need a recursive structure or a searchable select. --}}
                             {{-- Let's just create a quick helper to get list in component if needed, or iterate simply here --}}
                             {{-- Using treeRoots only shows roots. --}}
                             @foreach(\App\Models\Shop\ShopCategory::where('id', '!=', $selectedCategoryId)->orderBy('name')->limit(100)->get() as $cat)
                                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                             @endforeach
                         </select>
                         <div class="form-text">Showing top 100 folders. Search/Tree select coming soon.</div>
                         @error('targetParentId') <span class="text-danger fs-12">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" wire:click="updateMove">Move</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div wire:ignore.self class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="text-end">
                        <button type="button" class="btn-close text-end" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="mt-2">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <h4 class="mb-3 mt-4">Are you sure?</h4>
                        <p class="text-muted fs-15 mb-4">Are you sure you want to delete this folder? This action cannot be undone.</p>
                        @error('delete') 
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger" wire:click="destroy">Yes, Delete It!</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script for Modals --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
             const createModal = new bootstrap.Modal(document.getElementById('createCategoryModal'));
             const renameModal = new bootstrap.Modal(document.getElementById('renameCategoryModal'));
             const moveModal = new bootstrap.Modal(document.getElementById('moveCategoryModal'));
             const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));

             @this.on('show-create-modal', () => createModal.show());
             @this.on('hide-create-modal', () => createModal.hide());

             @this.on('show-rename-modal', () => renameModal.show());
             @this.on('hide-rename-modal', () => renameModal.hide());
             
             @this.on('show-move-modal', () => moveModal.show());
             @this.on('hide-move-modal', () => moveModal.hide());

             @this.on('show-delete-modal', () => deleteModal.show());
             @this.on('hide-delete-modal', () => deleteModal.hide());
        });
    </script>
</div>
