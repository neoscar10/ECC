<?php

namespace App\Livewire\Admin\Archive\Categories;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Archive\ArchiveCategory;
use App\Models\MembershipTier;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $search = '';
    public $filterVisibility = '';

    // Modal State
    public $showModal = false;
    public $isEditMode = false;
    public $categoryId;
    // existingImage is already declared further down

    // existingImage is already declared further down

    // Form Fields
    public $title;
    public $slug;
    public $description;
    public $visibility = 'public'; // public, restricted
    public $is_active = true;
    public $image;
    public $existingImage;
    public $selectedTiers = []; // Array of tier IDs

    // Data
    public $membershipTiers = [];

    // Validation Rules
    protected function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,restricted',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:10240', // 10MB
            'selectedTiers' => 'required_if:visibility,restricted|array',
            'selectedTiers.*' => 'exists:membership_tiers,id',
        ];
    }

    public function mount()
    {
        $this->membershipTiers = MembershipTier::where('is_active', true)->get();
    }

    // --- Search & Pagination ---
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterVisibility()
    {
        $this->resetPage();
    }

    // --- Modal Actions ---
    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->showModal = true;
        // Dispatch event to show modal via JS
        $this->dispatch('show-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $this->categoryId = $id;

        $category = ArchiveCategory::with('tiers')->findOrFail($id);
        
        $this->title = $category->title;
        $this->description = $category->description;
        $this->visibility = $category->visibility;
        $this->is_active = (bool) $category->is_active;
        $this->existingImage = $category->image_path;
        $this->selectedTiers = $category->tiers->pluck('id')->toArray();

        $this->showModal = true;
        $this->dispatch('show-modal');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('hide-modal');
    }

    // --- CRUD Operations ---
    public function store()
    {
        $this->validate();

        $slug = Str::slug($this->title);
        // Ensure unique slug
        $originalSlug = $slug;
        $count = 1;
        while (ArchiveCategory::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $imagePath = null;
        if ($this->image) {
            $path = $this->image->store('archive/categories', 'public');
            $imagePath = str_replace('\\', '/', $path);
        }

        $category = ArchiveCategory::create([
            'title' => $this->title,
            'slug' => $slug,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'is_active' => $this->is_active,
            'image_path' => $imagePath,
            'sort_order' => 0, // Default for now
        ]);

        if ($this->visibility === 'restricted') {
            $category->tiers()->sync($this->selectedTiers);
        }

        session()->flash('success', 'Category created successfully.');
        $this->closeModal();
    }

    public function update()
    {
        $this->validate();

        $category = ArchiveCategory::findOrFail($this->categoryId);

        // Slug update only if title changed (optional, but good practice).
        // For simplicity, we keep original slug or generate new if empty.
        // Let's keep existing slug logic unless we want to force update.
        if ($category->title !== $this->title) {
             $slug = Str::slug($this->title);
             $originalSlug = $slug;
             $count = 1;
             while (ArchiveCategory::where('slug', $slug)->where('id', '!=', $this->categoryId)->exists()) {
                 $slug = $originalSlug . '-' . $count++;
             }
             $category->slug = $slug;
        }

        if ($this->image) {
            // Delete old image if exists
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $path = $this->image->store('archive/categories', 'public');
            $category->image_path = str_replace('\\', '/', $path);
        }

        $category->title = $this->title;
        $category->description = $this->description;
        $category->visibility = $this->visibility;
        $category->is_active = $this->is_active;
        $category->save();

        if ($this->visibility === 'restricted') {
            $category->tiers()->sync($this->selectedTiers);
        } else {
            $category->tiers()->detach();
        }

        session()->flash('success', 'Category updated successfully.');
        $this->closeModal();
    }

    public function delete($id)
    {
        $category = ArchiveCategory::findOrFail($id);
        
        // Optional: delete image from storage (if not soft deleted, but we are soft deleting so maybe keep it?)
        // If we hard delete later, we clean up. For now, SoftDelete is active.
        
        $category->delete();
        session()->flash('success', 'Category deleted successfully.');
    }

    public function resetForm()
    {
        $this->reset(['title', 'slug', 'description', 'visibility', 'is_active', 'image', 'existingImage', 'selectedTiers', 'categoryId', 'isEditMode']);
        $this->visibility = 'public';
        $this->is_active = true;
    }

    public function render()
    {
        $query = ArchiveCategory::query()
            ->withCount('tiers'); // For listing badge "+N more"

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        if ($this->filterVisibility) {
            $query->where('visibility', $this->filterVisibility);
        }

        $categories = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.admin.archive.categories.index', [
            'categories' => $categories
        ]);
    }
}
