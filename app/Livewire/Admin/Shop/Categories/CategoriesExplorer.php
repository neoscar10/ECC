<?php

namespace App\Livewire\Admin\Shop\Categories;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Shop\ShopCategory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

#[Layout('layouts.admin')]
class CategoriesExplorer extends Component
{
    // --- State ---
    public $currentFolderId = null;
    public $expandedIds = [];
    public $search = '';

    // --- Modal State ---
    public $showCreateModal = false;
    public $showRenameModal = false;
    public $showMoveModal = false;
    public $showDeleteModal = false;

    // --- Product Modal Compatibility State ---
    public $isEditMode = false;
    public $createStep = 1;
    public $variationsOnlyMode = false;
    public $showCreateProductModal = false;

    public $selectedCategoryId;
    
    // --- Form Fields ---
    public $name;
    public $slug; // Auto-generated or manual
    public $targetParentId;
    public $is_active = true;

    public function hasCategoryDefaults($category = null): bool
    {
        $cat = $category ?? $this->currentFolder;
        if (!$cat) return false;

        return (bool) ($cat->has_defaults ?? false);
    }

    // --- Lifecycle ---

    public function mount()
    {
        // Default to root
        $this->currentFolderId = null;
    }

    // --- Computed Properties ---

    public function getCurrentFolderProperty()
    {
        return $this->currentFolderId ? ShopCategory::find($this->currentFolderId) : null;
    }

    public function getBreadcrumbsProperty()
    {
        if (!$this->currentFolderId) {
            return [];
        }

        $breadcrumbs = [];
        $category = $this->currentFolder;
        
        while ($category) {
            array_unshift($breadcrumbs, $category);
            $category = $category->parent;
        }

        return $breadcrumbs;
    }

    public function getFolderContentsProperty()
    {
        $query = ShopCategory::query();

        if ($this->search) {
            // If searching, search recursively within current folder? 
            // Or just search everything if root?
            // "search within current folder" requested.
            
            if ($this->currentFolderId) {
                // Should we search ALL descendants or just children?
                // "Search within current folder" usually implies recursive or flat list of matches.
                // Let's stick to immediate children matching name for now, or maybe distinct behavior.
                // Explorer usually filters the current view.
                $query->where('parent_id', $this->currentFolderId)
                      ->where('name', 'like', '%' . $this->search . '%');
            } else {
                 $query->whereNull('parent_id')
                       ->where('name', 'like', '%' . $this->search . '%');
            }
        } else {
            if ($this->currentFolderId) {
                $query->where('parent_id', $this->currentFolderId);
            } else {
                $query->whereNull('parent_id');
            }
        }

        return $query->withCount('children')->orderBy('sort_order')->orderBy('name')->get();
    }

    public function getTreeRootsProperty()
    {
        // Eager load implicit children for the tree? 
        // For infinite depth, we might need a recursive component or just load 1 level.
        // Let's load roots. The recursive blade component will handle children relation.
        // To avoid N+1, we can eager load one level or use lazy loading on expand.
        return ShopCategory::whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get();
    }
    
    // --- Actions ---

    public function openFolder($id)
    {
        $this->currentFolderId = $id;
        $this->search = ''; // Clear search on nav? Usually yes.
        // Also expand this node in tree if not expanded
        if (!in_array($id, $this->expandedIds)) {
            $this->expandedIds[] = $id;
        }
    }

    public function toggleExpand($id)
    {
        if (in_array($id, $this->expandedIds)) {
            $this->expandedIds = array_diff($this->expandedIds, [$id]);
        } else {
            $this->expandedIds[] = $id;
        }
    }

    public function navigateUp()
    {
        if ($this->currentFolder) {
            $this->currentFolderId = $this->currentFolder->parent_id;
        }
    }

    public function toggleActive($id)
    {
        $category = ShopCategory::find($id);
        if ($category) {
            $category->update(['is_active' => !$category->is_active]);
        }
    }

    // --- CRUD: Create ---

    public function initiateCreate()
    {
        $this->reset(['name', 'slug', 'is_active']);
        $this->is_active = true;
        $this->showCreateModal = true;
        // Dispatch browser event if using bootstrap modal manually
        $this->dispatch('show-create-modal');
    }

    public function store()
    {
        $this->validate([
            'name' => 'required|string|max:100',
        ]);

        $parent = $this->currentFolderId;
        
        // Generate unique slug in scope
        $slug = Str::slug($this->name);
        $originalSlug = $slug;
        $count = 1;
        while (ShopCategory::where('parent_id', $parent)->where('slug', $slug)->exists()) {
             $slug = $originalSlug . '-' . $count++;
        }

        ShopCategory::create([
            'parent_id' => $parent,
            'name' => $this->name,
            'slug' => $slug,
            'is_active' => $this->is_active,
        ]);

        $this->showCreateModal = false;
        $this->dispatch('hide-create-modal');
        $this->dispatch('refresh-tree'); // Optional signal
        session()->flash('success', 'Folder created successfully.');
    }

    // --- CRUD: Rename ---

    public function initiateRename($id)
    {
        $this->selectedCategoryId = $id;
        $category = ShopCategory::find($id);
        $this->name = $category->name;
        // Slug editing ignored for now as per plan, unless we want to allow it.
        $this->showRenameModal = true;
        $this->dispatch('show-rename-modal');
    }

    public function updateRename()
    {
        $this->validate([
            'name' => 'required|string|max:100',
        ]);

        $category = ShopCategory::find($this->selectedCategoryId);

        if ($category->name !== $this->name) {
             // Update slug? "choose stable slug unless explicitly changed"
             // Prompt said: "name (+ auto slug update or keep slug stable; choose stable slug unless explicitly changed)"
             // Let's keep slug stable for SEO stability unless we add a specific slug field. 
             // Simplicity: just update name.
             $category->update(['name' => $this->name]);
        }

        $this->showRenameModal = false;
        $this->dispatch('hide-rename-modal');
        session()->flash('success', 'Folder renamed.');
    }

    // --- CRUD: Move ---

    public function initiateMove($id)
    {
        $this->selectedCategoryId = $id;
        $this->targetParentId = null; // Default selection?
        $this->showMoveModal = true;
        $this->dispatch('show-move-modal');
    }
    
    public function updateMove()
    {
        $this->validate([
            'targetParentId' => 'nullable|exists:shop_categories,id',
        ]);
        
        $category = ShopCategory::find($this->selectedCategoryId);
        $targetId = $this->targetParentId === '' ? null : $this->targetParentId;

        // Validation: Cannot move into self or descendant
        if ($targetId == $category->id) {
             $this->addError('targetParentId', 'Cannot move a folder into itself.');
             return;
        }
        
        if ($targetId) {
            $target = ShopCategory::find($targetId);
            // Check if target is a descendant of category
            $parent = $target;
            while ($parent) {
                if ($parent->id == $category->id) {
                    $this->addError('targetParentId', 'Cannot move a folder into its own subfolder.');
                    return;
                }
                $parent = $parent->parent;
            }
        }
        
        $category->update(['parent_id' => $targetId]);
        
        $this->showMoveModal = false;
        $this->dispatch('hide-move-modal');
        session()->flash('success', 'Folder moved.');
    }

    // --- CRUD: Delete ---

    public function initiateDelete($id)
    {
        $this->selectedCategoryId = $id;
        $this->showDeleteModal = true;
        $this->dispatch('show-delete-modal');
    }

    public function destroy()
    {
        $category = ShopCategory::find($this->selectedCategoryId);
        
        if ($category->children()->exists()) {
            $this->addError('delete', 'Cannot delete folder that has subfolders. Please delete them first.');
            return;
        }

        // Future: Check products
        // if ($category->products()->exists()) { ... }

        $category->delete();
        
        $this->showDeleteModal = false;
        $this->dispatch('hide-delete-modal');
        session()->flash('success', 'Folder deleted.');
    }

    public function render()
    {
        return view('livewire.admin.shop.categories.categories-explorer');
    }
}
