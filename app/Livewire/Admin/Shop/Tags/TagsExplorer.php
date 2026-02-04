<?php

namespace App\Livewire\Admin\Shop\Tags;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Shop\ShopTagGroup;
use App\Models\Shop\ShopTag;
use Illuminate\Support\Str;

#[Layout('layouts.admin')]
class TagsExplorer extends Component
{
    use WithPagination;

    // State
    public $currentGroupId = null;
    public $searchGroups = '';
    public $searchTags = '';
    
    // Form Inputs
    public $name = '';
    public $targetGroupId = null; // For Move
    
    // Edit tracking
    public $editingGroupId = null;
    public $editingTagId = null;
    public $deletingGroupId = null;
    public $deletingTagId = null;

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        // Auto-select first group if available
        $first = ShopTagGroup::orderBy('sort_order')->orderBy('name')->first();
        if ($first) {
            $this->currentGroupId = $first->id;
        }
    }

    public function selectGroup($id)
    {
        $this->currentGroupId = $id;
        $this->resetPage();
        $this->searchTags = '';
    }

    // --- GROUP ACTIONS ---

    public function initiateCreateGroup()
    {
        $this->reset(['name', 'editingGroupId']);
        $this->dispatch('show-create-group-modal');
    }

    public function storeGroup()
    {
        $this->validate(['name' => 'required|string|max:100|unique:shop_tag_groups,name']);

        ShopTagGroup::create([
            'name' => $this->name,
            'slug' => Str::slug($this->name),
            'is_active' => true,
        ]);

        $this->dispatch('hide-create-group-modal');
        $this->reset(['name']);
        session()->flash('success', 'Tag Group created successfully.');
    }

    public function initiateRenameGroup($id)
    {
        $group = ShopTagGroup::findOrFail($id);
        $this->editingGroupId = $id;
        $this->name = $group->name;
        $this->dispatch('show-rename-group-modal');
    }

    public function updateGroup()
    {
        $this->validate(['name' => 'required|string|max:100|unique:shop_tag_groups,name,' . $this->editingGroupId]);

        $group = ShopTagGroup::findOrFail($this->editingGroupId);
        $group->update([
            'name' => $this->name,
            'slug' => Str::slug($this->name),
        ]);

        $this->dispatch('hide-rename-group-modal');
        session()->flash('success', 'Tag Group renamed successfully.');
    }

    public function initiateDeleteGroup($id)
    {
        $this->deletingGroupId = $id;
        $this->dispatch('show-delete-group-modal');
    }

    public function destroyGroup()
    {
        $group = ShopTagGroup::withCount('tags')->findOrFail($this->deletingGroupId);

        if ($group->tags_count > 0) {
            $this->addError('delete_group', 'Cannot delete group containing tags. Please delete or move tags first.');
            return;
        }

        $group->delete();
        $this->dispatch('hide-delete-group-modal');
        
        if ($this->currentGroupId == $this->deletingGroupId) {
            $this->currentGroupId = null;
            $this->mount(); // Reset selection
        }
        
        session()->flash('success', 'Tag Group deleted successfully.');
    }

    public function toggleGroupActive($id)
    {
        $group = ShopTagGroup::findOrFail($id);
        $group->update(['is_active' => !$group->is_active]);
    }

    // --- TAG ACTIONS ---

    public function initiateCreateTag()
    {
        if (!$this->currentGroupId) return;
        $this->reset(['name', 'editingTagId']);
        $this->dispatch('show-create-tag-modal');
    }

    public function storeTag()
    {
        $this->validate([
            'name' => 'required|string|max:100',
        ]);
        
        // Custom uniqueness check within group
        $slug = Str::slug($this->name);
        if (ShopTag::where('group_id', $this->currentGroupId)->where('slug', $slug)->exists()) {
            $this->addError('name', 'Tag name already exists in this group.');
            return;
        }

        ShopTag::create([
            'group_id' => $this->currentGroupId,
            'name' => $this->name,
            'slug' => $slug,
            'is_active' => true,
        ]);

        $this->dispatch('hide-create-tag-modal');
        $this->reset(['name']);
        session()->flash('success', 'Tag created successfully.');
    }

    public function initiateRenameTag($id)
    {
        $tag = ShopTag::findOrFail($id);
        $this->editingTagId = $id;
        $this->name = $tag->name;
        $this->dispatch('show-rename-tag-modal');
    }

    public function updateTag()
    {
        $this->validate(['name' => 'required|string|max:100']);
        
        $tag = ShopTag::findOrFail($this->editingTagId);
        $slug = Str::slug($this->name);
        
        if (ShopTag::where('group_id', $tag->group_id)
                   ->where('slug', $slug)
                   ->where('id', '!=', $tag->id)->exists()) {
            $this->addError('name', 'Tag name already exists in this group.');
            return;
        }

        $tag->update([
            'name' => $this->name,
            'slug' => $slug,
        ]);

        $this->dispatch('hide-rename-tag-modal');
        session()->flash('success', 'Tag renamed successfully.');
    }

    public function initiateMoveTag($id)
    {
        $this->editingTagId = $id;
        $this->targetGroupId = null;
        $this->dispatch('show-move-tag-modal');
    }

    public function moveTag()
    {
        $this->validate(['targetGroupId' => 'required|exists:shop_tag_groups,id']);
        
        $tag = ShopTag::findOrFail($this->editingTagId);
        
        if ($tag->group_id == $this->targetGroupId) {
            $this->dispatch('hide-move-tag-modal');
            return;
        }

        // Check for collision in target group
        if (ShopTag::where('group_id', $this->targetGroupId)->where('slug', $tag->slug)->exists()) {
             $this->addError('targetGroupId', 'A tag with this name already exists in the destination group.');
             return;
        }

        $tag->update(['group_id' => $this->targetGroupId]);
        
        $this->dispatch('hide-move-tag-modal');
        session()->flash('success', 'Tag moved successfully.');
    }

    public function initiateDeleteTag($id)
    {
        $this->deletingTagId = $id;
        $this->dispatch('show-delete-tag-modal');
    }

    public function destroyTag()
    {
        ShopTag::findOrFail($this->deletingTagId)->delete();
        $this->dispatch('hide-delete-tag-modal');
        session()->flash('success', 'Tag deleted successfully.');
    }

    public function toggleTagActive($id)
    {
        $tag = ShopTag::findOrFail($id);
        $tag->update(['is_active' => !$tag->is_active]);
    }

    // --- COMPUTED ---

    public function getGroupsProperty()
    {
        return ShopTagGroup::withCount('tags')
            ->when($this->searchGroups, fn($q) => $q->where('name', 'like', '%'.$this->searchGroups.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTagsProperty()
    {
        if (!$this->currentGroupId) return [];

        return ShopTag::where('group_id', $this->currentGroupId)
            ->when($this->searchTags, fn($q) => $q->where('name', 'like', '%'.$this->searchTags.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);
    }
    
    public function getCurrentGroupProperty()
    {
        if (!$this->currentGroupId) return null;
        return ShopTagGroup::find($this->currentGroupId);
    }

    public function render()
    {
        return view('livewire.admin.shop.tags.tags-explorer', [
            'groups' => $this->groups,
            'tags' => $this->currentGroupId ? $this->tags : [],
            'currentGroup' => $this->currentGroup,
        ]);
    }
}
