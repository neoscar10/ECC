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
    
    // Auction Lots Picker UX
    public $lotsDropdownOpen = false;
    public $lotSearchResults = []; // Specific for the dropdown
    public $lotSearch = ''; // Bound to the input

    // UX / Layout Fields
    public $textPosition = 'below'; // above, below (for banners/sliders)
    public $previewData = []; // { category_name: string, items: array }

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
        // Load options if source pre-set
        if ($this->sliderSource) {
            $this->loadSourceOptions();
        }
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
        }
    }

    public function getBuilderSummaryProperty()
    {
        $summary = [];
        $summary['Type'] = ucfirst($this->type);
        
        if ($this->type === 'slider') {
            $summary['Mode'] = ucfirst($this->sliderMode);
            if ($this->sliderMode === 'category') {
                $summary['Source'] = ucfirst($this->sliderSource);
                $summary['Limit'] = $this->sliderLimit . ' items';
                if ($this->sliderCategoryId) {
                     $cat = collect($this->sourceCategories)->firstWhere('id', $this->sliderCategoryId);
                     $selectedName = is_array($cat) ? ($cat['name'] ?? 'Selected') : 'Selected';
                     $summary['Category'] = strip_tags($selectedName); // Strip nesting dashes
                }
            } elseif ($this->sliderMode === 'manual') {
                $summary['Items'] = count($this->selectedSliderItems) . ' selected';
            } elseif ($this->sliderMode === 'images') {
                $summary['Slides'] = count($this->sliderImages) . ' added';
            }
        } elseif ($this->type === 'banner' || $this->type === 'card') {
             $summary['Layout'] = 'Text ' . ucfirst($this->textPosition);
        }

        if ($this->hasDetailPage) {
            $summary['Detail Page'] = 'Enabled';
            $summary['CTA'] = $this->contentCtaText;
        }

        return $summary;
    }

    public function updatedSliderCategoryId()
    {
        // Update Preview Data
        $this->updateCategoryPreview();
    }
    
    public function updateCategoryPreview()
    {
        $this->previewData = [];
        
        if (!$this->sliderCategoryId && $this->sliderSource !== 'auctions') return;
        
        $categoryName = 'Selected Category';
        $items = [];
        
        if ($this->sliderSource === 'shop') {
             $cat = \App\Models\Shop\ShopCategory::find($this->sliderCategoryId);
             if ($cat) {
                 $categoryName = $cat->name;
                 $limit = (int) ($this->sliderLimit ?? 10);
                 $items = (new \App\Services\Cms\ContentBlockPreviewService())->resolveSliderItems(
                     'shop', 'category', $cat->id, [], $limit
                 );
             }
        } elseif ($this->sliderSource === 'archive') {
             $cat = \App\Models\Archive\ArchiveCategory::find($this->sliderCategoryId);
             if ($cat) {
                 $categoryName = $cat->title; // Archive Category uses title? Let's check model. 
                 // Previous code used $cat->title. ArchiveCategory likely has title.
                 $limit = (int) ($this->sliderLimit ?? 10);
                 $items = (new \App\Services\Cms\ContentBlockPreviewService())->resolveSliderItems(
                     'archive', 'category', $cat->id, [], $limit
                 );
             }
        } elseif ($this->sliderSource === 'auctions') {
            // New logic for auctions
            $categoryName = 'Selected Lots';
            // Need to pass selected lot IDs. In Index.php, we have separate property?
            // Checking Index.php... 'selectedSliderItems' holds the items. 
            // Wait, selectedSliderItems is an array of items, already resolved? 
            // In the "Manual" mode part of Index.php, it uses selectedSliderItems.
            // But for Auctions source in Category mode (which is kinda weird but user says "Sources: Shop, Archive, Auctions"), 
            // the UI allows selecting lots directly if source=auctions and mode=category?
            // Looking at _step3-builder.blade.php:
            // if($sliderSource === 'auctions') -> "Select Auction Lots". User selects lots.
            // These lots are stored in `selectedSliderItems`.
            // The service expects IDs.
            // Let's extract IDs from selectedSliderItems if they are arrays with 'id'.
            // OR if selectedSliderItems is just IDs? 
            // Looking at `addSliderItem($id)` in Index.php might clarify.
            // But let's assume selectedSliderItems contains the data.
            // Actually, if we already have selectedSliderItems, we might just use them directly if they are formatted correctly?
            // BUT the user wants "Preview Must Render REAL Items... with Correct Images + Prices".
            // If `selectedSliderItems` only has minimal data for the list, we might need to re-fetch or format.
            // Let's pass the IDs to the service to be sure we get fresh data + images + correct price format.
            $lotIds = collect($this->selectedSliderItems)->pluck('id')->toArray();
            $items = (new \App\Services\Cms\ContentBlockPreviewService())->resolveSliderItems(
                'auctions', 'category', null, $lotIds
            );
        }
        
        $this->previewData = [
            'category_name' => $categoryName,
            'items' => $items
        ];
    }



    public function updatedType($value)
    {
        // Auto-clear irrelevant fields based on Type
        $this->resetValidation();
        
        // Reset specific fields that might persist weirdly
        $this->sliderSource = '';
        $this->sliderMode = 'category';
        $this->contentBody = ''; // Banner doesn't use this, others might
        $this->contentImage = null;
        $this->existingContentImage = null;
        
        if ($value === 'text') {
             $this->textPosition = null; // Not applicable
        } else {
             $this->textPosition = 'below';
        }
    }

    public function updatedSliderMode($value)
    {
        $this->resetValidation();
        $this->sliderSource = '';
        $this->sliderCategoryId = null;
        $this->selectedSliderItems = [];
        $this->sliderImages = [];
        $this->searchResults = [];
        $this->searchResults = [];
        $this->previewData = [];
        
        // Reset Auction Picker
        $this->lotSearch = '';
        $this->lotSearchResults = [];
        $this->lotsDropdownOpen = false;

        // Clear non-effective fields for Category Mode
        if ($value === 'category') {
             $this->contentSubtitle = null;
             $this->contentBadge = null;
             $this->textPosition = 'below'; // Default or null?
             $this->hasDetailPage = false;
             $this->contentCtaText = null;
             $this->contentMarkdown = null;
        }
    }

    public function isSliderCategoryMode()
    {
        return $this->type === 'slider' && $this->sliderMode === 'category';
    }

    public function updatedSliderSource($value)
    {
        // Reset category/items when source changes
        $this->sliderCategoryId = null;
        $this->selectedSliderItems = [];
        $this->itemSearchQuery = '';
        $this->itemSearchQuery = '';
        $this->searchResults = [];
        
        // Reset Auction Picker
        $this->lotSearch = '';
        $this->lotSearchResults = [];
        $this->lotsDropdownOpen = false;
        
        $this->loadSourceOptions();
    }
    
    public function loadSourceOptions()
    {
        $this->sourceCategories = [];

        if ($this->sliderSource === 'shop') {
             // Load Shop Categories (Nested)
             $categories = \App\Models\Shop\ShopCategory::orderBy('sort_order')->get();
             $this->sourceCategories = $this->flattenCategories($categories);

        } elseif ($this->sliderSource === 'archive') {
             // Load Archive Categories
             $this->sourceCategories = \App\Models\Archive\ArchiveCategory::orderBy('title')->get()
                ->map(fn($c) => ['id' => $c->id, 'name' => $c->title])
                ->toArray();
        }
        // Auctions = no categories
    }

    private function flattenCategories($categories, $parentId = null, $depth = 0)
    {
        $result = [];
        foreach ($categories->where('parent_id', $parentId) as $category) {
            $prefix = str_repeat('— ', $depth);
            $result[] = ['id' => $category->id, 'name' => $prefix . $category->name];
            
            // Recurse
            $children = $this->flattenCategories($categories, $category->id, $depth + 1);
            $result = array_merge($result, $children);
        }
        return $result;
    }
    
    // --- Auction Lots Picker Logic ---

    public function updatedLotSearch()
    {
        $this->lotsDropdownOpen = true;
        $this->searchLots();
    }

    public function focusLotsSearch()
    {
        $this->lotsDropdownOpen = true;
        if (empty($this->lotSearch) && empty($this->lotSearchResults)) {
            $this->loadDefaultLots();
        }
    }

    public function blurLotsSearch()
    {
        // Delay to allow clicking items
        // In Livewire, we might assume Alpine handles the click-away closing mostly, 
        // but we can have a method here if needed. 
        // For now, let's leave this and handle closing via Alpine or specific action.
        // Actually, we can just set it false on specific events if needed.
    }
    
    public function loadDefaultLots()
    {
        // Load recent/active lots
        $this->lotSearchResults = \App\Models\Auctions\AuctionLot::query()
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->map(fn($lot) => $this->formatLotForPicker($lot))
            ->toArray();
    }

    public function searchLots()
    {
        if (strlen($this->lotSearch) > 1) {
             $query = $this->lotSearch;
             $this->lotSearchResults = \App\Models\Auctions\AuctionLot::where(function($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                      ->orWhere('lot_no', 'like', '%' . $query . '%');
                 })
                 ->take(30)
                 ->get()
                 ->map(fn($lot) => $this->formatLotForPicker($lot))
                 ->toArray();
        } else {
             $this->loadDefaultLots();
        }
    }

    private function formatLotForPicker($lot)
    {
        $price = $lot->current_highest_bid > 0 
            ? 'INR ' . number_format($lot->current_highest_bid)
            : 'INR ' . number_format($lot->starting_price);
            
        return [
            'id' => $lot->id, 
            'name' => "Lot " . $lot->lot_no . " - " . $lot->title,
            'status' => $lot->status, // live, upcoming, ended
            'price' => $price,
            'image' => $lot->images->first()?->path // relationship
        ];
    }
    
    public function addSliderItem($id) // Used for Manual AND Auctions now?
    {
        // Add to selectedSliderItems if not exists
        $exists = collect($this->selectedSliderItems)->contains('id', $id);
        if (!$exists) {
            // Check formatted list first
            $item = collect($this->lotSearchResults)->firstWhere('id', $id);
            
            // If not found (searched manual mode?), check manual search results
            if (!$item) {
                 $item = collect($this->searchResults)->firstWhere('id', $id);
            }
            
            // Fail-safe fetch if still missing
            if (!$item && $this->sliderSource === 'auctions') {
                 $lot = \App\Models\Auctions\AuctionLot::find($id);
                 if ($lot) $item = $this->formatLotForPicker($lot);
            }

            if ($item) {
                // Ensure name is clean
                if (!isset($item['name']) && isset($item['title'])) $item['name'] = $item['title'];
                
                $this->selectedSliderItems[] = $item;
                $this->updateCategoryPreview(); // Trigger preview update
            }
        }
        
        $this->itemSearchQuery = ''; 
        $this->searchResults = [];
        $this->lotSearch = ''; // Clear search
        $this->lotSearchResults = []; // Clear specific picker results so next focus loads defaults
        $this->lotsDropdownOpen = false; // Close dropdown
    }

    public function removeSliderItem($index)
    {
        unset($this->selectedSliderItems[$index]);
        $this->selectedSliderItems = array_values($this->selectedSliderItems);
        $this->updateCategoryPreview(); // Trigger preview update
    }
    
    public function updateSliderItemOrder($list)
    {
        $ordered = [];
        foreach($list as $item) {
            $ordered[] = collect($this->selectedSliderItems)->firstWhere('id', $item['value']);
        }
        $this->selectedSliderItems = $ordered;
        $this->updateCategoryPreview(); // Trigger preview update
    }

    // Deprecated / Manual old search (kept for safety if Manual mode uses it)
    public function updatedItemSearchQuery()
    {
        if (strlen($this->itemSearchQuery) > 2) {
             $query = $this->itemSearchQuery;
             // ... old logic ...
             if ($this->sliderMode === 'manual') {
                 // Mock Search for Manual Items
                 $this->searchResults = [
                     ['id' => 101, 'name' => 'Suggest: ' . $query . ' (1)', 'image' => null],
                     ['id' => 102, 'name' => 'Suggest: ' . $query . ' (2)', 'image' => null],
                 ];
             }
        }
    }

    public function addSlide()
    {
        $this->validate([
            'newSlideImage' => 'required|image|mimes:jpeg,png|max:5120',
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

            if ($this->type === 'banner' || $this->type === 'card' || ($this->type === 'slider' && $this->sliderMode !== 'category')) {
                 if ($this->type === 'banner' && !$this->existingContentImage) {
                     $rules['contentImage'] = 'required|image|mimes:jpeg,png|max:10240';
                 } else {
                     $rules['contentImage'] = 'nullable|image|mimes:jpeg,png|max:10240';
                 }
            }
            
            if ($this->type === 'slider') {
                $rules['sliderSource'] = 'required_unless:sliderMode,images';
                
                if ($this->sliderMode === 'category') {
                    if ($this->sliderSource === 'auctions') {
                         $rules['selectedSliderItems'] = 'required|array|min:1';
                    } else {
                         $rules['sliderCategoryId'] = 'required';
                    }
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
        // Allow navigation if in Edit Mode OR if going backward
        if ($this->isEditMode || $step < $this->createStep) {
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
             
             // Load options once source is set
             $this->loadSourceOptions();

             if ($this->sliderMode === 'category') {
                  if ($this->sliderSource === 'auctions') {
                       $this->selectedSliderItems = $typeConfig['items'] ?? []; 
                  } else {
                       $this->sliderCategoryId = $typeConfig['category_id'] ?? null;
                       $this->sliderLimit = $typeConfig['limit'] ?? 10;
                  }
             } elseif ($this->sliderMode === 'manual') {
                  $this->selectedSliderItems = $typeConfig['items'] ?? [];
             } elseif ($this->sliderMode === 'images') {
                  $this->sliderImages = $typeConfig['slides'] ?? [];
             }
        }
        
        // Add Common UX Config
        if ($this->type === 'banner' || $this->type === 'slider' || $this->type === 'card') {
             // Ensure Text Position is set (default below if not present)
             $this->textPosition = $typeConfig['text_position'] ?? 'below';
        }
        
        // Preview Real Data
        if ($this->type === 'slider' && $this->sliderMode === 'category') {
             if ($this->sliderCategoryId || $this->sliderSource === 'auctions') {
                  $this->updateCategoryPreview();
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
            'has_detail_page' => $this->hasDetailPage,
            'detail_markdown' => $this->contentMarkdown,
            'image_url' => $imageUrl,
        ];
        
        // Prune fields for Slider Category Mode
        if ($this->type === 'slider' && $this->sliderMode === 'category') {
             $contentPayload['subtitle'] = null;
             $contentPayload['badge'] = null;
             $contentPayload['has_detail_page'] = false;
             $contentPayload['cta_text'] = null;
             $contentPayload['detail_markdown'] = null;
             // text_position is handled in type config below but logic there assumes it applies. 
             // We should ensure it's not saved or ignored if pruned.
             // Actually request says: Text Position controls are hidden. 
             // We can enforce null or default in type_config if needed, or just let UI hide it.
        }

        // Build Type Config
        $typeConfigPayload = null;
        if ($this->type === 'slider') {
            $typeConfigPayload = [
                'mode' => $this->sliderMode,
                'source' => $this->sliderSource,
            ];
            if ($this->sliderMode === 'category') {
                if ($this->sliderSource === 'auctions') {
                     // Save items for auctions
                     $typeConfigPayload['items'] = $this->selectedSliderItems;
                     $typeConfigPayload['lot_ids'] = collect($this->selectedSliderItems)->pluck('id')->toArray(); 
                } else {
                     $typeConfigPayload['category_id'] = $this->sliderCategoryId;
                     $typeConfigPayload['limit'] = $this->sliderLimit;
                     $typeConfigPayload['sort'] = 'newest'; // Defaulting
                }
                // Ensure text_position is not saved for Category mode if irrelevant
                // The request says "Text Position (Below Image / Overlay Image)" is hidden.
                // So we shouldn't save it in type_config either, or save as null?
                // Logic below adds it for slider. Let's prevent that.
            } elseif ($this->sliderMode === 'manual') {
                $typeConfigPayload['items'] = $this->selectedSliderItems;
            } elseif ($this->sliderMode === 'images') {
                $typeConfigPayload['slides'] = $this->sliderImages;
            }
        }

        // Add Common UX Config
        if ($this->type === 'banner' || $this->type === 'card' || ($this->type === 'slider' && $this->sliderMode !== 'category')) {
             if (is_null($typeConfigPayload)) $typeConfigPayload = [];
             $typeConfigPayload['text_position'] = $this->textPosition;
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
        
        $this->textPosition = 'below';
        $this->previewData = [];
    }
}
