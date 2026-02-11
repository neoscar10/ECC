<?php

namespace App\Livewire\Admin\Cms\Blocks;

use App\Models\Cms\CmsBlock;
use App\Models\MembershipTier;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $filterPlacement = '';

    // Data for dropdowns
    public $membershipTiers = [];

    // Modal States
    public $showCreateModal = false;
    public $isEditMode = false;
    public $blockId;
    public $confirmingDelete = false;

    // --- Form Fields ---
    public $title;
    public $placement = ''; // home, explore, profile, announcements
    public $type = 'card'; // card, banner, slider, text
    public $isActive = true;
    public $sortOrder = 0; // Auto-managed, but kept in state if needed
    
    // Content Fields
    public $contentTitle;
    public $contentSubtitle;
    public $contentBody;
    public $contentBadge;
    public $contentCtaText;
    // contentCtaUrl REMOVED
    public $contentImage; // file upload
    public $existingContentImage; // url string
    public $hasDetailPage = false;
    public $contentMarkdown;
    
    // Slider Config Fields
    public $sliderMode = 'category'; // category, manual, images
    public $sliderSource = ''; // shop, archive, auctions
    public $sliderCategoryId;
    public $sourceCategories = []; // Mock or load
    public $sliderLimit = 10;
    
    public $itemSearchQuery = '';
    public $searchResults = [];
    public $selectedSliderItems = []; // [{id, name, image...}]

    public $sliderImages = []; // [{image_path, title, subtitle, sort}]
    public $newSlideImage; // Temporary upload for new slide

    // Restriction Fields
    public $restrictionMode = 'public';
    public $restrictionType; // hierarchical, private, random
    public $restrictedMinTierId;
    public $restrictedPrivateTierId;
    public $selectedVisibilityTiers = [];
    public $selectedRandomTiers = []; 
    
    // Blur Fields
    public $blurEnabled = false;
    
    // Helpers
    public $computedVisibilityTierIds = [];

    // Wizard State
    public $createStep = 1; 

    public function mount()
    {
        $this->membershipTiers = MembershipTier::where('is_active', true)->orderBy('sort_order')->get();
        // Mock Categories for now or load real ones if models exist
        $this->sourceCategories = [
            ['id' => 1, 'name' => 'Bats'],
            ['id' => 2, 'name' => 'Gloves'],
            ['id' => 3, 'name' => 'Memorabilia'],
        ];
    }

    public function render()
    {
        $query = CmsBlock::query();

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }
        if ($this->filterPlacement) {
            $query->where('placement', $this->filterPlacement);
        }
        if ($this->filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($this->filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        return view('livewire.admin.cms.blocks.index', [
            'blocks' => $query->orderBy('placement')->orderBy('sort_order')->paginate(10)
        ]);
    }

    // --- Wizard Logic ---

    public function updatedRestrictionMode($value)
    {
        if ($value === 'public') {
            $this->selectedVisibilityTiers = [];
        }
        $this->computeEligibleBlurTiers();
    }

    public function updatedSelectedVisibilityTiers()
    {
        $this->computeEligibleBlurTiers();
    }

    public function computeEligibleBlurTiers()
    {
        if ($this->restrictionMode === 'public') {
             $this->computedVisibilityTierIds = $this->membershipTiers->pluck('id')->map(fn($id)=>(string)$id)->toArray();
        } else {
             $this->computedVisibilityTierIds = array_map(fn($id)=>(string)$id, $this->selectedVisibilityTiers);
        }
    }

    public function updatedSliderSource($value)
    {
        // Reset category/items when source changes
        $this->sliderCategoryId = null;
        $this->selectedSliderItems = [];
        $this->itemSearchQuery = '';
        $this->searchResults = [];
    }
    
    public function updatedSliderMode()
    {
         // Reset mode specific fields?
    }

    public function updatedItemSearchQuery()
    {
        if (strlen($this->itemSearchQuery) > 2) {
            // Mock Search - Replace with real search logic based on $this->sliderSource
            $this->searchResults = [
                ['id' => 101, 'name' => 'Suggest: ' . $this->itemSearchQuery . ' (1)', 'image' => null],
                ['id' => 102, 'name' => 'Suggest: ' . $this->itemSearchQuery . ' (2)', 'image' => null],
            ];
        } else {
            $this->searchResults = [];
        }
    }

    public function addSliderItem($id)
    {
        // Add to selectedSliderItems if not exists
        $exists = collect($this->selectedSliderItems)->contains('id', $id);
        if (!$exists) {
            // Find item details from search results or DB
            $item = collect($this->searchResults)->firstWhere('id', $id);
            if ($item) {
                $this->selectedSliderItems[] = $item;
            }
        }
        $this->itemSearchQuery = ''; 
        $this->searchResults = [];
    }

    public function removeSliderItem($index)
    {
        unset($this->selectedSliderItems[$index]);
        $this->selectedSliderItems = array_values($this->selectedSliderItems);
    }
    
    public function updateSliderItemOrder($list)
    {
        $ordered = [];
        foreach($list as $item) {
            $ordered[] = collect($this->selectedSliderItems)->firstWhere('id', $item['value']);
        }
        $this->selectedSliderItems = $ordered;
    }

    public function addSlide()
    {
        $this->validate([
            'newSlideImage' => 'required|image|max:5120',
        ]);

        $path = $this->newSlideImage->store('cms/slides', 'public');
        $url = Storage::url($path);

        $this->sliderImages[] = [
            'image_path' => $path,
            'image_url' => $url,
            'title' => '',
            'subtitle' => '',
            'sort' => count($this->sliderImages) + 1
        ];
        
        $this->newSlideImage = null;
    }

    public function removeSlide($index)
    {
        unset($this->sliderImages[$index]);
        $this->sliderImages = array_values($this->sliderImages);
    }

    public function validateStep($step)
    {
        if ($step === 1) { // Basic
            $this->validate([
                'placement' => 'required|string',
                'title' => 'required|string|max:255',
                'isActive' => 'boolean',
            ]);
        } elseif ($step === 2) { // Type
            $this->validate([
                'type' => 'required|in:card,banner,slider,text', 
                'sliderMode' => 'required_if:type,slider|in:category,manual,images',
            ]);
        } elseif ($step === 3) { // Builder
            $rules = [
                'contentTitle' => 'required|string',
                'hasDetailPage' => 'boolean',
            ];
            
            if ($this->hasDetailPage) {
                $rules['contentCtaText'] = 'required|string';
                $rules['contentMarkdown'] = 'required|string';
            }

            if ($this->type === 'banner' || $this->type === 'card') {
                 // Image optional for card/text, required for banner? User said Image required for Banner.
                 if ($this->type === 'banner' && !$this->existingContentImage) {
                     $rules['contentImage'] = 'required|image|max:10240';
                 } else {
                     $rules['contentImage'] = 'nullable|image|max:10240';
                 }
            }
            
            if ($this->type === 'slider') {
                $rules['sliderSource'] = 'required_unless:sliderMode,images';
                
                if ($this->sliderMode === 'category') {
                    $rules['sliderCategoryId'] = 'required';
                } elseif ($this->sliderMode === 'manual') {
                    $rules['selectedSliderItems'] = 'required|array|min:1';
                } elseif ($this->sliderMode === 'images') {
                    $rules['sliderImages'] = 'required|array|min:1';
                }
            }
            
            $this->validate($rules);

        } elseif ($step === 4) { // Access
             $this->validate([
                'restrictionMode' => 'required|in:public,restricted',
                'selectedVisibilityTiers' => ['exclude_unless:restrictionMode,restricted', 'required', 'array', 'min:1'],
                'restrictionType' => ['exclude_unless:restrictionMode,restricted', 'exclude_if:blurEnabled,false', 'exclude_unless:blurEnabled,true', 'required_if:blurEnabled,true', 'in:hierarchical,random,private'],
                'restrictedMinTierId' => ['exclude_unless:restrictionType,hierarchical', 'required', 'exists:membership_tiers,id'],
                'restrictedPrivateTierId' => ['exclude_unless:restrictionType,private', 'required', 'exists:membership_tiers,id'],
                'blurEnabled' => 'boolean',
            ]);
        }
    }

    public function nextStep()
    {
        $this->validateStep($this->createStep);
        if ($this->createStep < 5) {
            $this->createStep++;
        }
    }

    public function prevStep()
    {
        if ($this->createStep > 1) {
            $this->createStep--;
        }
    }
    
    public function goToStep($step)
    {
        // Can only jump specific steps if previous valid? 
        // Auction Modal allows jumping BACK but not forward past current progress.
        if ($step < $this->createStep) {
            $this->createStep = $step;
        }
    }

    // --- CRUD ---

    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->createStep = 1;
        $this->showCreateModal = true;
        $this->dispatch('show-create-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $this->blockId = $id;

        $block = CmsBlock::with(['visibilityTiers', 'clearViewTiers'])->findOrFail($id);

        $this->title = $block->title;
        $this->placement = $block->placement;
        $this->type = $block->type;
        $this->isActive = $block->is_active;
        $this->sortOrder = $block->sort_order;
        
        // Content
        $content = $block->content ?? [];
        $this->contentTitle = $content['title'] ?? '';
        $this->contentSubtitle = $content['subtitle'] ?? '';
        $this->contentBody = $content['body'] ?? '';
        $this->contentBadge = $content['badge'] ?? '';
        $this->contentCtaText = $content['cta_text'] ?? '';
        $this->hasDetailPage = $content['has_detail_page'] ?? false;
        $this->contentMarkdown = $content['detail_markdown'] ?? '';
        $this->existingContentImage = $content['image_url'] ?? null;
        
        // Type Config (Slider)
        $typeConfig = $block->type_config ?? [];
        if ($this->type === 'slider') {
             $this->sliderMode = $typeConfig['mode'] ?? 'category';
             $this->sliderSource = $typeConfig['source'] ?? '';
             if ($this->sliderMode === 'category') {
                  $this->sliderCategoryId = $typeConfig['category_id'] ?? null;
                  $this->sliderLimit = $typeConfig['limit'] ?? 10;
             } elseif ($this->sliderMode === 'manual') {
                  $this->selectedSliderItems = $typeConfig['items'] ?? [];
             } elseif ($this->sliderMode === 'images') {
                  $this->sliderImages = $typeConfig['slides'] ?? [];
             }
        }

        // Access
        $this->restrictionMode = $block->restriction_mode;
        $this->restrictionType = $block->restriction_type;
        $this->restrictedMinTierId = $block->restricted_min_tier_id;
        $this->restrictedPrivateTierId = $block->restricted_private_tier_id;
        $this->blurEnabled = $block->blur_enabled;
        
        if ($block->restriction_mode === 'restricted') {
            $this->selectedVisibilityTiers = $block->visibilityTiers->pluck('id')->toArray();
        } else {
            $this->selectedVisibilityTiers = [];
        }
        
        $this->computeEligibleBlurTiers();
        
        $this->createStep = 1;
        $this->showCreateModal = true;
        $this->dispatch('show-create-modal');
    }

    public function store()
    {
        $this->validateStep(1);
        $this->validateStep(2);
        $this->validateStep(3);
        $this->validateStep(4);

        // Upload Image
        $imageUrl = $this->existingContentImage;
        if ($this->contentImage) {
            $path = $this->contentImage->store('cms/blocks', 'public');
            $imageUrl = Storage::url($path);
        }
        
        $contentPayload = [
            'title' => $this->contentTitle,
            'subtitle' => $this->contentSubtitle,
            'body' => $this->contentBody,
            'badge' => $this->contentBadge,
            'cta_text' => $this->contentCtaText,
            'image_url' => $imageUrl,
            'has_detail_page' => $this->hasDetailPage,
            'detail_markdown' => $this->hasDetailPage ? $this->contentMarkdown : null,
        ];
        
        // Build Type Config
        $typeConfigPayload = null;
        if ($this->type === 'slider') {
            $typeConfigPayload = [
                'mode' => $this->sliderMode,
                'source' => $this->sliderSource,
            ];
            if ($this->sliderMode === 'category') {
                $typeConfigPayload['category_id'] = $this->sliderCategoryId;
                $typeConfigPayload['limit'] = $this->sliderLimit;
                $typeConfigPayload['sort'] = 'newest'; // Defaulting
            } elseif ($this->sliderMode === 'manual') {
                $typeConfigPayload['items'] = $this->selectedSliderItems;
            } elseif ($this->sliderMode === 'images') {
                $typeConfigPayload['slides'] = $this->sliderImages;
            }
        }

        DB::beginTransaction();
        try {
            // Sort Order
            if (!$this->isEditMode) {
                 $maxSort = CmsBlock::where('placement', $this->placement)->max('sort_order');
                 $this->sortOrder = $maxSort ? $maxSort + 1 : 1;
            }

            $data = [
                'title' => $this->title,
                'placement' => $this->placement,
                'type' => $this->type,
                'content' => $contentPayload,
                'type_config' => $typeConfigPayload,
                'is_active' => $this->isActive,
                'sort_order' => $this->sortOrder,
                'restriction_mode' => $this->restrictionMode,
                'restriction_type' => $this->restrictionMode === 'public' ? 'hierarchical' : ($this->restrictionType ?: 'hierarchical'),
                'restricted_min_tier_id' => $this->restrictionMode === 'public' ? null : $this->restrictedMinTierId,
                'restricted_private_tier_id' => $this->restrictionMode === 'public' ? null : $this->restrictedPrivateTierId,
                'blur_enabled' => $this->blurEnabled,
                'blur_strategy' => $this->restrictionMode === 'public' ? 'hierarchical' : ($this->restrictionType ?? 'hierarchical'),
            ];

            if ($this->blurEnabled && $data['restriction_type'] === 'hierarchical') {
                 $data['min_clear_view_tier_id'] = $this->restrictedMinTierId;
            } else {
                 $data['min_clear_view_tier_id'] = null;
            }

            if ($this->isEditMode) {
                $block = CmsBlock::findOrFail($this->blockId);
                $block->update($data);
            } else {
                $block = CmsBlock::create($data);
            }

            // Sync Tiers
            if ($this->restrictionMode === 'restricted') {
                $block->visibilityTiers()->sync($this->selectedVisibilityTiers);
            } else {
                $block->visibilityTiers()->detach();
            }
            
            // Sync Clear View Tiers (Simplified)
            $clearTiers = [];
            if ($this->blurEnabled && $data['restriction_type'] === 'private') {
                 if ($this->restrictedPrivateTierId) $clearTiers = [$this->restrictedPrivateTierId];
            }
            $block->clearViewTiers()->sync($clearTiers);

            DB::commit();
            $this->closeModal();
            $this->dispatch('refresh-blocks'); 
            session()->flash('success', 'Block saved successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('general', $e->getMessage());
        }
    }
    
    public function updateOrder($orderedIds) // Called from Drag & Drop
    {
        if (!is_array($orderedIds)) return;
        
        DB::beginTransaction();
        try {
            foreach ($orderedIds as $index => $id) {
                CmsBlock::where('id', $id)->update(['sort_order' => $index + 1]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    public function delete($id)
    {
        $this->blockId = $id;
        $this->confirmingDelete = true;
    }

    public function confirmDelete()
    {
        CmsBlock::findOrFail($this->blockId)->delete();
        $this->confirmingDelete = false;
        $this->blockId = null;
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->dispatch('hide-create-modal');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->title = '';
        $this->placement = '';
        $this->type = 'card';
        $this->contentTitle = '';
        $this->contentSubtitle = '';
        $this->contentBody = '';
        $this->contentBadge = '';
        $this->contentCtaText = '';
        $this->hasDetailPage = false;
        $this->contentMarkdown = '';
        $this->contentImage = null;
        $this->existingContentImage = null;
        
        $this->sliderMode = 'category';
        $this->sliderSource = '';
        $this->sliderCategoryId = null;
        $this->selectedSliderItems = [];
        $this->sliderImages = [];
        
        $this->restrictionMode = 'public';
        $this->restrictionType = null;
        $this->restrictedMinTierId = null;
        $this->restrictedPrivateTierId = null;
        $this->selectedVisibilityTiers = [];
        $this->blurEnabled = false;
        $this->createStep = 1;
    }
}
