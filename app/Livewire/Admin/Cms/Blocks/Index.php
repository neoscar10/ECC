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


    // Data for dropdowns
    public $membershipTiers = [];

    // Modal States
    public $showCreateModal = false;
    public $isEditMode = false;
    public $blockId;
    public $confirmingDelete = false;

    // --- Form Fields ---
    public $title;
    public $placement = 'explore'; // Fixed to explore
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
    // Target Fields (for Banner/Card)
    public $hasTarget = false;
    public $targetKind = null; // category, item
    public $targetSource = null; // shop, archive, auctions
    public $targetId = null;
    public $targetLabel = null;
    public $targetSearch = '';
    public $targetSearchResults = [];
    public array $browseResults = [];
    
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

    public $createStep = 1; 

    // Preview
    public $previewTierId = null; // null = Guest
    public string $previewScopeId = '';

    public function mount()
    {
        $this->placement = 'explore';
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
        
        // Sanitize
        if ($this->blurEnabled) {
             if ($this->restrictedMinTierId && !in_array((string)$this->restrictedMinTierId, $this->computedVisibilityTierIds)) {
                 $this->restrictedMinTierId = null;
             }
             if ($this->restrictedPrivateTierId && !in_array((string)$this->restrictedPrivateTierId, $this->computedVisibilityTierIds)) {
                 $this->restrictedPrivateTierId = null;
             }
             if (!empty($this->selectedRandomTiers)) {
                 $this->selectedRandomTiers = array_values(array_intersect($this->selectedRandomTiers, $this->computedVisibilityTierIds));
             }
        }
    }

    public function updatedPreviewTierId()
    {
        $this->dispatch('cms-preview-updated', scopeId: $this->previewScopeId);
    }

    public function updated($property)
    {
        // Re-init swiper if we are on step 4 and any state changes
        if ($this->createStep == 4) {
            $this->dispatch('cms-preview-updated', scopeId: $this->previewScopeId);
        }
    }

    public function updatedHasTarget($value)
    {
        if ($value) {
            $this->hasDetailPage = false;
            $this->contentMarkdown = null;
            
            if (empty($this->targetKind)) {
                $this->targetKind = 'category';
            }
            if (empty($this->targetSource)) {
                $this->targetSource = 'shop';
            }
            
            $this->loadBrowseResults();
        }
    }

    public function updatedHasDetailPage($value)
    {
        if ($value) {
            $this->hasTarget = false;
            $this->clearTargetState();
        }
    }

    public function updatedTargetKind()
    {
        $this->targetId = null;
        $this->targetLabel = null;
        $this->targetSearch = '';
        $this->targetSearchResults = [];
        $this->loadBrowseResults();
    }

    public function updatedTargetSource()
    {
        $this->targetId = null;
        $this->targetLabel = null;
        $this->targetSearch = '';
        $this->targetSearchResults = [];
        $this->loadBrowseResults();
    }

    public function updatedTargetSearch()
    {
        if (strlen($this->targetSearch) < 2) {
            $this->targetSearchResults = [];
            $this->loadBrowseResults();
            return;
        }

        $query = $this->targetSearch;
        $results = [];

        if ($this->targetKind === 'category') {
            if ($this->targetSource === 'shop') {
                $categories = \App\Models\Shop\ShopCategory::where('name', 'like', "%{$query}%")->limit(10)->get();
                foreach ($categories as $cat) {
                     $results[] = [
                         'id' => $cat->id,
                         'label' => strip_tags($cat->name),
                         'image' => null,
                         'meta' => 'Shop Category'
                     ];
                }
            } elseif ($this->targetSource === 'archive') {
                $categories = \App\Models\Archive\ArchiveCategory::where('title', 'like', "%{$query}%")->limit(10)->get();
                foreach ($categories as $cat) {
                     $results[] = [
                         'id' => $cat->id,
                         'label' => $cat->title,
                         'image' => null,
                         'meta' => 'Archive Category'
                     ];
                }
            }
        } elseif ($this->targetKind === 'item') {
            if ($this->targetSource === 'shop') {
                $products = \App\Models\Shop\ShopProduct::with('images')
                            ->where(function($q) use ($query) {
                                $q->where('title', 'like', "%{$query}%");
                                if (is_numeric($query)) {
                                    $q->orWhere('id', $query);
                                }
                            })->limit(10)->get();
                foreach ($products as $prod) {
                     $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                     $results[] = [
                         'id' => $prod->id,
                         'label' => $prod->title,
                         'image' => $img,
                         'meta' => $prod->price > 0 ? 'INR ' . number_format($prod->price) : null
                     ];
                }
            } elseif ($this->targetSource === 'archive') {
                $products = \App\Models\Archive\ArchiveProduct::with('images')->where('title', 'like', "%{$query}%")
                            ->orWhere('code', 'like', "%{$query}%")->limit(10)->get();
                foreach ($products as $prod) {
                     $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                     $results[] = [
                         'id' => $prod->id,
                         'label' => $prod->title,
                         'image' => $img,
                         'meta' => null
                     ];
                }
            } elseif ($this->targetSource === 'auctions') {
                $lots = \App\Models\Auctions\AuctionLot::with('images')->where('title', 'like', "%{$query}%")
                            ->orWhere('lot_no', 'like', "%{$query}%")->limit(10)->get();
                foreach ($lots as $lot) {
                     $price = $lot->current_highest_bid > 0 ? $lot->current_highest_bid : $lot->starting_price;
                     $img = $lot->images->first()?->path ? \Illuminate\Support\Facades\Storage::url($lot->images->first()->path) : null;
                     $results[] = [
                         'id' => $lot->id,
                         'label' => 'Lot ' . $lot->lot_no . ' - ' . $lot->title,
                         'image' => $img,
                         'meta' => 'INR ' . number_format((float)$price)
                     ];
                }
            }
        }

        $this->targetSearchResults = $results;
    }

    public function loadBrowseResults()
    {
        $this->browseResults = [];

        if (!$this->hasTarget || !$this->targetKind || !$this->targetSource) {
            return;
        }

        if ($this->targetKind === 'category') {
            if ($this->targetSource === 'shop') {
                $categories = \App\Models\Shop\ShopCategory::orderBy('name', 'asc')->limit(12)->get();
                foreach ($categories as $cat) {
                    $this->browseResults[] = [
                        'id' => $cat->id,
                        'label' => strip_tags($cat->name),
                        'image' => null,
                        'meta' => 'Shop Category'
                    ];
                }
            } elseif ($this->targetSource === 'archive') {
                $categories = \App\Models\Archive\ArchiveCategory::orderBy('title', 'asc')->limit(12)->get();
                foreach ($categories as $cat) {
                    $this->browseResults[] = [
                        'id' => $cat->id,
                        'label' => $cat->title,
                        'image' => null,
                        'meta' => 'Archive Category'
                    ];
                }
            }
        } elseif ($this->targetKind === 'item') {
            if ($this->targetSource === 'shop') {
                $products = \App\Models\Shop\ShopProduct::with('images')->orderBy('created_at', 'desc')->limit(12)->get();
                foreach ($products as $prod) {
                    $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                    $this->browseResults[] = [
                        'id' => $prod->id,
                        'label' => $prod->title,
                        'image' => $img,
                        'meta' => $prod->price > 0 ? 'INR ' . number_format($prod->price) : null
                    ];
                }
            } elseif ($this->targetSource === 'archive') {
                $products = \App\Models\Archive\ArchiveProduct::with('images')->orderBy('created_at', 'desc')->limit(12)->get();
                foreach ($products as $prod) {
                    $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                    $this->browseResults[] = [
                        'id' => $prod->id,
                        'label' => $prod->title,
                        'image' => $img, 
                        'meta' => null
                    ];
                }
            } elseif ($this->targetSource === 'auctions') {
                $lots = \App\Models\Auctions\AuctionLot::with('images')->orderBy('created_at', 'desc')->limit(12)->get();
                foreach ($lots as $lot) {
                    $price = $lot->current_highest_bid > 0 ? $lot->current_highest_bid : $lot->starting_price;
                    $img = $lot->images->first()?->path ? \Illuminate\Support\Facades\Storage::url($lot->images->first()->path) : null;
                    $this->browseResults[] = [
                        'id' => $lot->id,
                        'label' => $lot->title,
                        'image' => $img,
                        'meta' => 'Lot ' . $lot->lot_no . ' • INR ' . number_format((float)$price)
                    ];
                }
            }
        }
    }

    public function selectTarget($id, $label)
    {
        $this->targetId = (int) $id;
        $this->targetLabel = $label;
        $this->targetSearch = '';
        $this->targetSearchResults = [];
    }

    public function clearTargetState()
    {
        $this->targetKind = null;
        $this->targetSource = null;
        $this->targetId = null;
        $this->targetLabel = null;
        $this->targetSearch = '';
        $this->targetSearchResults = [];
    }

    public function getResolvedPreviewProperty()
    {
        // 1. Build an in-memory block from current form state
        $imageUrl = $this->existingContentImage;
        if ($this->contentImage) {
            try {
                $imageUrl = $this->contentImage->temporaryUrl();
            } catch (\Exception $e) {
                // Ignore if not fully uploaded
            }
        }

        $block = new CmsBlock([
            'title' => $this->title ?? 'Draft Block',
            'placement' => 'explore',
            'type' => $this->type ?? 'card',
            'restriction_mode' => $this->restrictionMode,
            'restriction_type' => $this->restrictionMode === 'public' ? 'hierarchical' : ($this->restrictionType ?: 'hierarchical'),
            'restricted_min_tier_id' => $this->restrictedMinTierId,
            'restricted_private_tier_id' => $this->restrictedPrivateTierId,
            'blur_enabled' => $this->blurEnabled,
            'blur_strategy' => $this->restrictionMode === 'public' ? 'hierarchical' : ($this->restrictionType ?: 'hierarchical'),
            'min_clear_view_tier_id' => $this->restrictedMinTierId,
        ]);

        // Mock relations for memory arrays
        if ($this->restrictionMode === 'restricted') {
             $block->setRelation('visibilityTiers', MembershipTier::whereIn('id', $this->selectedVisibilityTiers)->get());
             if ($this->blurEnabled && $this->restrictionType === 'random') {
                 $block->setRelation('clearViewTiers', MembershipTier::whereIn('id', $this->selectedRandomTiers)->get());
             }
        }

        $block->content = [
            'title' => $this->contentTitle,
            'subtitle' => $this->contentSubtitle,
            'body' => $this->contentBody,
            'badge' => $this->contentBadge,
            'cta_text' => $this->contentCtaText,
            'has_detail_page' => $this->hasDetailPage,
            'has_target' => $this->hasTarget,
            'image_url' => $imageUrl,
        ];
        
        if ($this->hasTarget) {
            $block->content = array_merge($block->content, [
                'target' => [
                    'kind' => $this->targetKind,
                    'source' => $this->targetSource,
                    'id' => (int) $this->targetId,
                    'label' => $this->targetLabel ?: null,
                ]
            ]);
        }

        $typeConfig = ['text_position' => $this->textPosition];
        if ($this->type === 'slider') {
            $typeConfig['mode'] = $this->sliderMode;
            $typeConfig['source'] = $this->sliderSource;
            if ($this->sliderMode === 'category') {
                $typeConfig['category_id'] = $this->sliderCategoryId;
                $typeConfig['limit'] = $this->sliderLimit;
                if ($this->sliderSource === 'auctions') {
                     $typeConfig['items'] = $this->selectedSliderItems;
                     $typeConfig['lot_ids'] = collect($this->selectedSliderItems)->pluck('id')->toArray();
                }
            } elseif ($this->sliderMode === 'manual') {
                $typeConfig['items'] = $this->selectedSliderItems;
            } elseif ($this->sliderMode === 'images') {
                $typeConfig['slides'] = $this->sliderImages;
            }
        }
        $block->type_config = $typeConfig;

        // 2. Pass virtual block to the MobileResolver
        $resolver = app(\App\Services\Cms\ContentBlockMobileResolver::class);
        return $resolver->resolveForTier($block, $this->previewTierId ? (int)$this->previewTierId : null);
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
        } elseif ($this->hasTarget) {
            $summary['Target'] = ucfirst($this->targetKind) . ' (' . ucfirst($this->targetSource) . ')';
            $summary['CTA'] = $this->contentCtaText ?: 'Open';
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
        
        // Reset Target specifically on type change if not banner/card
        if ($value !== 'banner' && $value !== 'card') {
            $this->hasTarget = false;
            $this->clearTargetState();
            $this->hasDetailPage = false;
            $this->contentMarkdown = null;
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
            'title' => "Lot " . $lot->lot_no . " - " . $lot->title,
            'status' => $lot->status, // live, upcoming, ended
            'price' => $price,
            'image' => $lot->images->first()?->path ? \Illuminate\Support\Facades\Storage::url($lot->images->first()->path) : null
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
                $this->selectedSliderItems[] = [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? ($item['name'] ?? 'Untitled'),
                    'image' => $item['image'] ?? null,
                    'price' => $item['price'] ?? ($item['meta'] ?? null),
                ];
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

    public function focusItemSearch()
    {
        $this->lotsDropdownOpen = true;
        if (empty($this->itemSearchQuery)) {
            $this->searchItems();
        }
    }

    public function loadDefaultItems()
    {
        $this->searchResults = [];
        $results = [];

        if ($this->sliderSource === 'shop') {
            $products = \App\Models\Shop\ShopProduct::with('images')
                ->active()
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();
            foreach ($products as $prod) {
                $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                $results[] = [
                    'id' => $prod->id,
                    'title' => $prod->title,
                    'image' => $img,
                    'price' => $prod->base_price > 0 ? 'INR ' . number_format($prod->base_price) : 'No Price'
                ];
            }
        } elseif ($this->sliderSource === 'archive') {
            $products = \App\Models\Archive\ArchiveProduct::active()
                ->with('images')
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();
            foreach ($products as $prod) {
                $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                $results[] = [
                    'id' => $prod->id,
                    'title' => $prod->title,
                    'image' => $img,
                    'price' => $prod->code ?: 'Archive Item'
                ];
            }
        } elseif ($this->sliderSource === 'auctions') {
            $lots = \App\Models\Auctions\AuctionLot::with('images')
                ->orderBy('created_at', 'desc')
                ->limit(15)
                ->get();
            foreach ($lots as $lot) {
                $price = $lot->current_highest_bid > 0 ? $lot->current_highest_bid : $lot->starting_price;
                $img = $lot->images->first()?->path ? \Illuminate\Support\Facades\Storage::url($lot->images->first()->path) : null;
                $results[] = [
                    'id' => $lot->id,
                    'title' => 'Lot ' . $lot->lot_no . ' - ' . $lot->title,
                    'image' => $img,
                    'price' => 'INR ' . number_format((float)$price)
                ];
            }
        }

        $this->searchResults = $results;
    }

    public function updatedItemSearchQuery()
    {
        $this->searchItems();
    }

    public function searchItems()
    {
        if (empty($this->itemSearchQuery)) {
            $this->loadDefaultItems();
            return;
        }

        if (strlen($this->itemSearchQuery) < 2) {
            $this->searchResults = [];
            return;
        }

        $query = $this->itemSearchQuery;
        $results = [];

        if ($this->sliderSource === 'shop') {
            $products = \App\Models\Shop\ShopProduct::with('images')
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%");
                    if (is_numeric($query)) {
                        $q->orWhere('id', $query);
                    }
                })
                ->limit(15)
                ->get();
            foreach ($products as $prod) {
                $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                $results[] = [
                    'id' => $prod->id,
                    'title' => $prod->title,
                    'image' => $img,
                    'price' => $prod->base_price > 0 ? 'INR ' . number_format($prod->base_price) : 'No Price'
                ];
            }
        } elseif ($this->sliderSource === 'archive') {
            $products = \App\Models\Archive\ArchiveProduct::with('images')
                ->where('title', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%")
                ->limit(15)
                ->get();
            foreach ($products as $prod) {
                $img = $prod->images->first()?->image_path ? \Illuminate\Support\Facades\Storage::url($prod->images->first()->image_path) : null;
                $results[] = [
                    'id' => $prod->id,
                    'title' => $prod->title,
                    'image' => $img,
                    'price' => $prod->code ?: 'Archive Item'
                ];
            }
        } elseif ($this->sliderSource === 'auctions') {
            $lots = \App\Models\Auctions\AuctionLot::with('images')
                ->where('title', 'like', "%{$query}%")
                ->orWhere('lot_no', 'like', "%{$query}%")
                ->limit(15)
                ->get();
            foreach ($lots as $lot) {
                $price = $lot->current_highest_bid > 0 ? $lot->current_highest_bid : $lot->starting_price;
                $img = $lot->images->first()?->path ? \Illuminate\Support\Facades\Storage::url($lot->images->first()->path) : null;
                $results[] = [
                    'id' => $lot->id,
                    'title' => 'Lot ' . $lot->lot_no . ' - ' . $lot->title,
                    'image' => $img,
                    'price' => 'INR ' . number_format((float)$price)
                ];
            }
        }

        $this->searchResults = $results;
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
                'placement' => 'required|string|in:' . implode(',', array_keys(config('cms.placements'))),
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
                if ($this->hasTarget && $this->hasDetailPage) {
                    $this->addError('target_conflict', 'A block cannot have both a Target and a Detail Page. Choose one.');
                    return;
                }
                
                if ($this->hasTarget) {
                    $rules['targetKind'] = 'required|in:category,item';
                    $rules['targetSource'] = 'required|string';
                    $rules['targetId'] = 'required|integer';
                }
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
                'restrictedMinTierId' => ['exclude_unless:restrictionMode,restricted', 'exclude_if:blurEnabled,false', 'exclude_unless:restrictionType,hierarchical', 'required', 'exists:membership_tiers,id'],
                'restrictedPrivateTierId' => ['exclude_unless:restrictionMode,restricted', 'exclude_if:blurEnabled,false', 'exclude_unless:restrictionType,private', 'required', 'exists:membership_tiers,id'],
                'selectedRandomTiers' => ['exclude_unless:restrictionMode,restricted', 'exclude_if:blurEnabled,false', 'exclude_unless:restrictionType,random', 'required', 'array', 'min:1'],
                'selectedRandomTiers.*' => ['exists:membership_tiers,id'],
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
        if ($this->createStep == 4) {
            $this->dispatch('cms-preview-step-changed', step: 4, scopeId: $this->previewScopeId);
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
            if ($step == 4) {
                $this->dispatch('cms-preview-step-changed', step: 4, scopeId: $this->previewScopeId);
            }
        }
    }

    // --- CRUD ---

    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->createStep = 1;
        $this->previewTierId = null;
        $this->previewScopeId = 'cmsPrev_' . \Illuminate\Support\Str::uuid()->toString();
        $this->showCreateModal = true;
        $this->dispatch('show-create-modal');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $this->blockId = $id;
        $this->previewScopeId = 'cmsPrev_' . \Illuminate\Support\Str::uuid()->toString();

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
        
        $this->hasTarget = (bool) data_get($content, 'has_target', false);
        $this->targetKind = data_get($content, 'target.kind');
        $this->targetSource = data_get($content, 'target.source');
        $this->targetId = data_get($content, 'target.id');
        $this->targetLabel = data_get($content, 'target.label');
        
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
        $this->previewTierId = null;
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
            'has_target' => $this->hasTarget,
            'detail_markdown' => $this->hasDetailPage ? $this->contentMarkdown : null,
            'image_url' => $imageUrl,
        ];
        
        if ($this->hasTarget) {
            $contentPayload['target'] = [
                'kind' => $this->targetKind,
                'source' => $this->targetSource,
                'id' => (int) $this->targetId,
                'label' => $this->targetLabel ?: null,
            ];
            $contentPayload['has_detail_page'] = false;
            $contentPayload['detail_markdown'] = null;
        }
        
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
                'restricted_min_tier_id' => ($this->restrictionMode === 'public' || !$this->blurEnabled) ? null : $this->restrictedMinTierId,
                'restricted_private_tier_id' => ($this->restrictionMode === 'public' || !$this->blurEnabled) ? null : $this->restrictedPrivateTierId,
                'blur_enabled' => $this->blurEnabled,
                'blur_strategy' => $this->restrictionMode === 'public' ? 'hierarchical' : ($this->restrictionType ?: 'hierarchical'),
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

        // Sync Tiers & Clear View Tiers
        // Sync Tiers & Clear View Tiers
        if ($this->restrictionMode === 'restricted') {
            $block->visibilityTiers()->sync($this->selectedVisibilityTiers);
            
            // Sync Clear View Tiers
            $finalClearTiers = [];
            if ($this->blurEnabled) {
                 if ($this->restrictionType === 'hierarchical' && $this->restrictedMinTierId) { // Check if min tier selected
                      // We need to resolve all tiers >= min tier that are also in visibility set
                      $min = MembershipTier::find($this->restrictedMinTierId);
                      if ($min) {
                          $potential = MembershipTier::where('level', '>=', $min->level)->pluck('id')->toArray();
                          $finalClearTiers = array_intersect($potential, $this->computedVisibilityTierIds);
                      }
                 } elseif ($this->restrictionType === 'random') {
                      $finalClearTiers = $this->selectedRandomTiers;
                 } elseif ($this->restrictionType === 'private' && $this->restrictedPrivateTierId) {
                      $finalClearTiers = [$this->restrictedPrivateTierId];
                 }
                 $block->clearViewTiers()->sync($finalClearTiers);
            } else {
                 $block->clearViewTiers()->detach();
            }
        } else {
            $block->visibilityTiers()->detach();
            $block->clearViewTiers()->detach();
        }

        DB::commit();
        
        $this->dispatch('hide-create-modal');
        $this->resetForm();
        session()->flash('success', $this->isEditMode ? 'Block updated successfully.' : 'Block created successfully.');

    } catch (\Throwable $e) {
        DB::rollBack();
        session()->flash('error', 'Error saving block: ' . $e->getMessage());
    }
    }

    public function updateOrder($orderedIds)
    {
        if (!is_array($orderedIds)) return;
        
        DB::transaction(function() use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                // $index is 0-based, so sort_order = index + 1
                CmsBlock::where('id', $id)->update(['sort_order' => $index + 1]);
            }
        });
        
        session()->flash('success', 'Order updated successfully.');
    }

    public function delete($id)
    {
        $this->blockId = $id;
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        if ($this->blockId) {
            $block = CmsBlock::find($this->blockId);
            if ($block) {
                $block->delete();
                session()->flash('success', 'Block deleted successfully.');
            }
        }
        $this->dispatch('hide-delete-modal');
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
        $this->placement = 'explore';
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
        
        $this->hasTarget = false;
        $this->clearTargetState();
        
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
