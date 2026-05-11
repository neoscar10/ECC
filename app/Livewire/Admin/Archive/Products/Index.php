<?php

namespace App\Livewire\Admin\Archive\Products;

use App\Models\Archive\ArchiveCategory;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveProductAttachment;
use App\Models\Archive\ArchiveProductEarlyAccess;
use App\Models\Archive\ArchiveProductImage;
use App\Models\MembershipTier;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $search = '';
    public $filterCategory = '';
    public $filterRestriction = '';
    public $filterStatus = '';
    public $filterEarlyAccess = '';

    // Data for dropdowns
    public $categories = [];
    public $membershipTiers = [];

    // Modal States
    public $showCreateModal = false;
    public $showAttachmentsModal = false;
    public $showEarlyAccessModal = false;
    public $isEditMode = false;
    public $productId;
    public $isHydrating = false;
    public $modalsOnly = false;
    
    // Delete Modal State
    public $confirmingDelete = false;
    public $deleteId = null;

    // --- Product Form Fields ---
    public $title;
    public $categoryId; // selected category
    public $priceMin;
    public $priceMax;
    public $quantity = 1;
    public $currency = 'INR';
    public $descriptionUnlocked;
    public $descriptionLocked;
    public $goLiveNow = true;
    public $goLiveAt;
    public $allowsEarlyAccess = false;
    public $restrictionMode = 'public'; // public, restricted
    public $restrictionType; // hierarchical, random, private
    public $restrictedMinTierId;
    public $restrictedPrivateTierId;
    public $selectedRandomTiers = []; // For product restriction
    public $selectedVisibilityTiers = []; // [NEW] Who can access the product at all
    
    // Gating Logic
    public $categoryAllowedTierIds = []; // [NEW] Tiers allowed by currently selected category

    // Blur / Clear View
    public $blurEnabled = false;
    public $clearViewTierIds = [];
    public $computedVisibilityTierIds = []; // For UI display/validation
    
    // Images
    public $newImages = []; // Uploaded files
    public $existingImages = []; // Existing image records
    
    // 360 Images
    public $new360Images = [];
    public $existing360Images = [];

    // Wizard State
    public $createStep = 1;

    // --- Early Access Form ---
    // Structure: [['tier_id' => X, 'access_at' => Y]]
    public $earlyAccessRows = [];

    // --- Attachments Form ---
    // Structure: [['id' => ?, 'type' => 'line', 'line_text' => '...', 'restriction_mode' => 'inherit', ...]]
    public $attachmentRows = [];

    protected function queryString()
    {
        return [
            'search' => ['except' => ''],
            'filterCategory' => ['except' => ''],
            'filterRestriction' => ['except' => ''],
        ];
    }

    public function mount()
    {
        $this->categories = ArchiveCategory::orderBy('title')->get();
        $this->membershipTiers = MembershipTier::where('is_active', true)->orderBy('sort_order')->get();
    }

    // --- Listings ---
    public function render()
    {
        $query = ArchiveProduct::with(['category', 'category.tiers', 'restrictedMinTier'])
            ->withCount(['attachments']);

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%');
        }
        if ($this->filterCategory) {
            $query->where('archive_category_id', $this->filterCategory);
        }
        if ($this->filterRestriction) {
            $query->where('restriction_mode', $this->filterRestriction);
        }
        if ($this->filterStatus) {
            $now = now();
            if ($this->filterStatus === 'live') {
                $query->where(function ($q) use ($now) {
                    $q->where('go_live_now', true)
                      ->orWhere('go_live_at', '<=', $now);
                });
            } elseif ($this->filterStatus === 'scheduled') {
                $query->where('go_live_now', false)
                      ->where('go_live_at', '>', $now);
            }
        }
        if ($this->filterEarlyAccess === 'yes') {
            $query->where('early_access_enabled', true);
        } elseif ($this->filterEarlyAccess === 'no') {
            $query->where('early_access_enabled', false);
        }

        return view('livewire.admin.archive.products.index', [
            'products' => $query->latest()->paginate(10)
        ]);
    }

    // --- Wizard Navigation ---

    public function updatedRestrictionMode($value)
    {
        if ($this->isHydrating) return;
        if ($value === 'public') {
            $this->selectedVisibilityTiers = [];
        }
        $this->computeEligibleBlurTiers();
    }

    public function updatedSelectedVisibilityTiers()
    {
        if ($this->isHydrating) return;
        $this->computeEligibleBlurTiers();
    }

    public function updatedRestrictionType($value)
    {
        if ($this->isHydrating) return;
        if ($value !== 'hierarchical') {
            $this->restrictedMinTierId = null;
        }
        if ($value !== 'private') {
            $this->restrictedPrivateTierId = null;
        }
        if ($value !== 'random') {
            $this->selectedRandomTiers = [];
        }
        // No need to recompute eligibility here as restriction type affects BLUR logic, not visibility list
        // EXCEPT if we want to reset some blur selection?
    }

    public function validateStep($step)
    {
        if ($step === 1) {
            $this->validate([
                'title' => 'required|string|max:255',
                'categoryId' => 'required|exists:archive_categories,id',
                'priceMin' => 'nullable|integer|min:0',
                'priceMax' => 'nullable|integer|min:0',
                'quantity' => 'required|integer|min:1',
                'descriptionUnlocked' => 'nullable|string',
                'goLiveAt' => 'nullable|required_if:goLiveNow,false|date',
            ]);
        } elseif ($step === 2) {
             $rules = [
                'newImages.*' => 'image|max:10240', // 10MB
                'new360Images.*' => 'image|max:10240',
             ];
             // If creating, require at least one image if no existing images? 
             // Current rules likely require images.
             if (!$this->isEditMode && empty($this->existingImages) && empty($this->newImages)) {
                 $rules['newImages'] = 'required';
             }
             $this->validate($rules);
        } elseif ($step === 3) {
            $this->validate([
                'restrictionMode' => 'required|in:public,restricted',
                // Visibility Tiers
                'selectedVisibilityTiers' => ['exclude_unless:restrictionMode,restricted', 'required', 'array', 'min:1'],
                'selectedVisibilityTiers.*' => ['exists:membership_tiers,id'],

                // Blur Settings (Restriction Type moved here)
                'blurEnabled' => 'boolean',
                'restrictionType' => ['exclude_if:blurEnabled,false', 'required', 'in:hierarchical,random,private'],
                'restrictedMinTierId' => ['exclude_unless:restrictionType,hierarchical', 'required', 'exists:membership_tiers,id', function($attribute, $value, $fail) {
                     // Ensure min tier is in visibility set (or public)
                     if (!in_array((string)$value, $this->computedVisibilityTierIds)) {
                         $fail('Selected minimum tier is not in the visible access list.');
                     }
                }],
                'restrictedPrivateTierId' => ['exclude_unless:restrictionType,private', 'required', 'exists:membership_tiers,id', function($attribute, $value, $fail) {
                     if (!in_array((string)$value, $this->computedVisibilityTierIds)) {
                         $fail('Selected private tier is not in the visible access list.');
                     }
                }],
                'selectedRandomTiers' => ['exclude_unless:restrictionType,random', 'array', 'min:1'],
                'selectedRandomTiers.*' => ['exclude_unless:restrictionType,random', 'exists:membership_tiers,id', function($attribute, $value, $fail) {
                     if (!in_array((string)$value, $this->computedVisibilityTierIds)) {
                         $fail('Selected random tier is not in the visible access list.');
                     }
                }],

                'clearViewTierIds' => ['exclude_if:blurEnabled,false', 'array'], // Can be empty if driven by MinTier? No, let's keep logic simple
                // Actually, if Hierarchy/Random/Private is used to determine Clear View, do we explicitly populate clearViewTierIds?
                // The current requirement says: 
                // "Hierarchical: choose min tier -> clear-view tiers = allowed tiers >= chosen min tier"
                // "Random: choose clear-view tiers -> clear-view tiers = chosen ones"
                // "Private: choose one -> clear-view tiers = chosen one"
                // So validation depends on type.
                
                // Let's rely on the specific fields above (Min/Private/Random) to drive logic
             ]);
        }
    }

    public function nextStep()
    {
        $this->validateStep($this->createStep);
        if ($this->createStep < 4) {
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
         // Allow going back freely, but going forward requires validation of previous steps
         if ($step < $this->createStep) {
             $this->createStep = $step;
             return;
         }
         
         // To go forward, validate all steps before target
         for ($i = 1; $i < $step; $i++) {
             $this->validateStep($i);
         }
         $this->createStep = $step;
    }

    // --- Create / Edit Product ---

    public function create()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->createStep = 1;
        $this->computeEligibleBlurTiers();
        $this->showCreateModal = true;
        $this->dispatch('show-create-modal');
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->dispatch('hide-create-modal');
        $this->resetForm();
        $this->createStep = 1;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $this->isHydrating = true; // [FIX] Start hydration guard
        $this->productId = $id;

        // [FIX] Eager load visibility and clear view tiers
        $product = ArchiveProduct::with(['images', 'images360', 'tiers', 'visibilityTiers', 'clearViewTiers'])->findOrFail($id);
        
        $this->title = $product->title;
        $this->categoryId = $product->archive_category_id;
        
        $this->computeCategoryAllowedTiers(); // [NEW] Populate allowed tiers for validation/UI
        
        $this->priceMin = $product->price_min_amount;
        $this->priceMax = $product->price_max_amount;
        $this->quantity = $product->quantity ?? 1;
        $this->descriptionUnlocked = $product->description_unlocked; 
        // Note: We ignore description_locked in UI as consolidated
        
        $this->goLiveNow = $product->go_live_now;
        $this->goLiveAt = $product->go_live_at ? $product->go_live_at->format('Y-m-d\TH:i') : null;
        $this->allowsEarlyAccess = $product->early_access_enabled;
        
        $this->restrictionMode = $product->restriction_mode;
        $this->restrictionType = $product->restriction_type;
        $this->restrictedMinTierId = $product->restricted_min_tier_id ? (string)$product->restricted_min_tier_id : null;
        $this->restrictedPrivateTierId = $product->restricted_private_tier_id ? (string)$product->restricted_private_tier_id : null;
        
        $this->existingImages = $product->images;
        $this->existing360Images = $product->images360;
        
        // [FIX] Hydrate Visibility Tiers (The missing piece)
        $this->selectedVisibilityTiers = $product->visibilityTiers->pluck('id')->map(fn($id) => (string)$id)->toArray();

        if ($product->restriction_type === 'random') {
             $this->selectedRandomTiers = $product->tiers->pluck('id')->map(fn($id) => (string)$id)->toArray();
        }
        
        $this->blurEnabled = (bool)$product->blur_enabled;
        $this->clearViewTierIds = $product->clearViewTiers->pluck('id')->map(fn($id) => (string)$id)->toArray();
        
        $this->computeEligibleBlurTiers();
        
        $this->createStep = 1;

        $this->isHydrating = false; // [FIX] End hydration guard
        $this->dispatch('show-create-modal');
    }

    public function storeProduct()
    {
        logger()->info('storeProduct called', ['step' => $this->createStep, 'data' => $this->all()]);
        
        $this->validateStep(1);
        $this->validateStep(2);
        
        // Normalize restriction data before final validation to prevent stale errors
        if ($this->restrictionMode === 'public') {
             $this->selectedVisibilityTiers = [];
        }
        
        // Clean up Blur vars if Blur disabled
        if (!$this->blurEnabled) {
            $this->restrictionType = null;
            $this->restrictedMinTierId = null;
            $this->restrictedPrivateTierId = null;
            $this->selectedRandomTiers = [];
            $this->clearViewTierIds = [];
        } else {
             if ($this->restrictionType !== 'hierarchical') $this->restrictedMinTierId = null;
             if ($this->restrictionType !== 'private') $this->restrictedPrivateTierId = null;
             if ($this->restrictionType !== 'random') $this->selectedRandomTiers = [];
        }

        // Additional image validation for final submit
        $this->validate([
             'newImages.*' => 'image|max:10240', // 10MB
             'new360Images.*' => 'image|max:10240',
        ]);
        $this->validateStep(3); // Access
        // Step 4 is Review, no validation needed needed beyond previous steps

        // Safe Defaults for restriction vars if public
        if ($this->restrictionMode === 'public') {
            $this->restrictionType = null;
            $this->restrictedMinTierId = null;
            $this->restrictedPrivateTierId = null;
            $this->selectedRandomTiers = [];
        }

        $slug = Str::slug($this->title);
        $count = 1;
        while(ArchiveProduct::where('slug', $slug)->exists()){
            $slug = Str::slug($this->title) . '-' . $count++;
        }

        DB::beginTransaction();
        try {
            $product = ArchiveProduct::create([
                'title' => $this->title,
                'slug' => $slug,
                'archive_category_id' => $this->categoryId,
                'description_unlocked' => $this->descriptionUnlocked,
                // 'description_locked' => $this->descriptionLocked, // Removed/Consolidated
                'price_min_amount' => $this->priceMin,
                'price_max_amount' => $this->priceMax,
                'quantity' => $this->quantity ?? 1,
                'currency' => $this->currency,
                'go_live_now' => $this->goLiveNow,
                'go_live_at' => $this->goLiveNow ? null : $this->goLiveAt,
                'early_access_enabled' => (!$this->goLiveNow && $this->allowsEarlyAccess),
                'restriction_mode' => $this->restrictionMode,
                'restriction_type' => $this->restrictionType,
                'restricted_min_tier_id' => $this->restrictedMinTierId,
                'restricted_private_tier_id' => $this->restrictedPrivateTierId,
                'blur_enabled' => $this->blurEnabled,
            ]);


            // Save Visibility Tiers
            if ($this->restrictionMode === 'restricted') {
                $product->visibilityTiers()->sync($this->selectedVisibilityTiers);
            } else {
                // Public = No explicit map (empty pivot)
                $product->visibilityTiers()->detach();
            }

            // Save Clear View Tiers
            $finalClearTiers = [];
            if ($this->blurEnabled) {
                // Calculate clear tiers based on Type
                if ($this->restrictionType === 'hierarchical' && $this->restrictedMinTierId) {
                     $min = MembershipTier::find($this->restrictedMinTierId);
                     if ($min) {
                         // Must be in Visibility Set (intersect)
                         $potential = MembershipTier::where('level', '>=', $min->level)->pluck('id')->toArray();
                         $finalClearTiers = array_intersect($potential, $this->computedVisibilityTierIds); // computed is now vis set
                     }
                } elseif ($this->restrictionType === 'random') {
                     $finalClearTiers = $this->selectedRandomTiers;
                } elseif ($this->restrictionType === 'private' && $this->restrictedPrivateTierId) {
                     $finalClearTiers = [$this->restrictedPrivateTierId];
                }
                $product->clearViewTiers()->sync($finalClearTiers);
            } else {
                $product->clearViewTiers()->detach();
            }
            
            // We no longer use product->tiers() (old random pivot)
            $product->tiers()->detach();

            // Save Images
            if ($this->newImages) {
                foreach ($this->newImages as $index => $image) {
                    $path = $image->store('archive/products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'sort_order' => $index
                    ]);
                }
            }
            
            // Save 360 Images
            if ($this->new360Images) {
                foreach ($this->new360Images as $index => $image) {
                    $path = $image->store('archive/products/360', 'public');
                    $product->images360()->create([
                        'image_path' => $path,
                        'sort_order' => $index
                    ]);
                }
            }
            
            DB::commit();

            $this->closeModal();
            $this->dispatch('refresh-products'); 
            $this->dispatch('archive-product-created'); // Trigger JS close
            session()->flash('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('general', 'Error saving product: ' . $e->getMessage());
        }
    }

    public function updateProduct()
    {
        $this->validateStep(1);
        $this->validateStep(2);
        $this->validateStep(3);

        $product = ArchiveProduct::findOrFail($this->productId);

        if ($this->restrictionMode === 'public') {
            $this->restrictionType = null;
            $this->restrictedMinTierId = null;
            $this->restrictedPrivateTierId = null;
            $this->selectedRandomTiers = [];
        }

        $product->update([
            'title' => $this->title,
            'archive_category_id' => $this->categoryId,
            'description_unlocked' => $this->descriptionUnlocked,
            'description_locked' => $this->descriptionLocked,
            'price_min_amount' => $this->priceMin,
            'price_max_amount' => $this->priceMax,
            'quantity' => $this->quantity ?? 1,
            'go_live_now' => $this->goLiveNow,
            'go_live_at' => $this->goLiveNow ? null : $this->goLiveAt,
            'early_access_enabled' => (!$this->goLiveNow && $this->allowsEarlyAccess),
            'restriction_mode' => $this->restrictionMode,
            'restriction_type' => $this->restrictionType,
            'restricted_min_tier_id' => $this->restrictedMinTierId,
            'restricted_private_tier_id' => $this->restrictedPrivateTierId,
            'blur_enabled' => $this->blurEnabled,
        ]);

        // Sync Clear View Tiers
        // Save Visibility
        if ($this->restrictionMode === 'restricted') {
            $product->visibilityTiers()->sync($this->selectedVisibilityTiers);
        } else {
            $product->visibilityTiers()->detach();
        }

        // Save Clear View Tiers
        $finalClearTiers = [];
        if ($this->blurEnabled) {
            if ($this->restrictionType === 'hierarchical' && $this->restrictedMinTierId) {
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
            $product->clearViewTiers()->sync($finalClearTiers);
        } else {
             $product->clearViewTiers()->detach();
        }
        
        $product->tiers()->detach(); // Obsolescent

        // Add New Images
        if (count($this->newImages) > 0) {
            $currentMaxSort = $product->images()->max('sort_order') ?? 0;
            foreach ($this->newImages as $index => $img) {
                $path = $img->store('archive/products', 'public');
                $product->images()->create([
                    'image_path' => str_replace('\\', '/', $path),
                    'sort_order' => $currentMaxSort + $index + 1
                ]);
            }
        }

        $this->showCreateModal = false;
        $this->dispatch('hide-create-modal');
        $this->dispatch('archive-product-updated'); // Trigger JS close
        session()->flash('success', 'Product updated successfully.');
    }
    
    public function deleteImage($imageId, $type = 'main')
    {
        // Preserve current step to avoid jumping back to Step 1
        $currentStep = $this->createStep;

        if ($type === 'main') {
            $img = ArchiveProductImage::findOrFail($imageId);
            // Verify ownership
            if ($this->productId && $img->archive_product_id == $this->productId) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
                // Refresh ONLY media, not full edit form
                $this->refreshMedia(); 
            }
        } elseif ($type === '360') {
             // Assuming ArchiveProduct360Image model exists
             $img = \App\Models\Archive\ArchiveProduct360Image::findOrFail($imageId);
             if ($this->productId && $img->archive_product_id == $this->productId) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
                $this->refreshMedia(); 
             }
        }
        
        // Restore step (though refreshMedia shouldn't touch it, SAFETY first)
        $this->createStep = $currentStep;
    }

    public function refreshMedia()
    {
        if ($this->productId) {
            $product = ArchiveProduct::with(['images', 'images360'])->find($this->productId);
            if ($product) {
                $this->existingImages = $product->images;
                $this->existing360Images = $product->images360;
            }
        }
    }

    public function removeNewImage($index)
    {
        if (isset($this->newImages[$index])) {
            unset($this->newImages[$index]);
            $this->newImages = array_values($this->newImages);
        }
    }

    public function removeNew360Image($index)
    {
        if (isset($this->new360Images[$index])) {
            unset($this->new360Images[$index]);
            $this->new360Images = array_values($this->new360Images);
        }
    }



    // --- Early Access Config ---

    public $earlyAccessAllowedTiers = [];
    public $earlyAccessGoLiveDate;

    public function configureEarlyAccess($id)
    {
        $this->productId = $id;
        $product = ArchiveProduct::with('earlyAccessWindows')->findOrFail($id);
        
        if ($product->go_live_now || !$product->go_live_at) {
            session()->flash('error', 'Early access can only be configured for scheduled products.');
            return;
        }

        $this->earlyAccessGoLiveDate = $product->go_live_at->format('d M Y, h:i A');

        // Compute allowed tiers based on restriction
        $this->earlyAccessAllowedTiers = $this->getAllowedTiersForProduct($product);
        
        $this->earlyAccessRows = $product->earlyAccessWindows->map(function($ea) {
            return [
                'tier_id' => $ea->membership_tier_id,
                'access_at' => $ea->access_at->format('Y-m-d\TH:i'),
            ];
        })->toArray();

        if (count($this->earlyAccessRows) === 0) {
             $this->earlyAccessRows[] = ['tier_id' => '', 'access_at' => ''];
        }

        $this->showEarlyAccessModal = true;
        $this->dispatch('show-ea-modal');
    }

    protected function getAllowedTiersForProduct(ArchiveProduct $product)
    {
        if ($product->restriction_mode === 'public') {
            return $this->membershipTiers; // All active tiers from mount()
        }

        if ($product->restriction_type === 'hierarchical' && $product->restricted_min_tier_id) {
            $minTier = MembershipTier::find($product->restricted_min_tier_id);
            if (!$minTier) return collect([]);
            
            return MembershipTier::where('is_active', true)
                ->where('level', '>=', $minTier->level)
                ->orderBy('level')
                ->get();
        }

        if ($product->restriction_type === 'random') {
             // Assuming relation is defined on ArchiveProduct model for 'tiers'
            return $product->tiers()->where('is_active', true)->orderBy('level')->get();
        }

        if ($product->restriction_type === 'private' && $product->restricted_private_tier_id) {
            return MembershipTier::where('id', $product->restricted_private_tier_id)
                ->where('is_active', true)
                ->get();
        }

        return collect([]);
    }
    
    // Compute IDs for the Access Form (creation/edit state)
    // Compute IDs for BLUR consideration (The 'Available' set)
    // Computed Property: The single source of truth for "Visibility Allowed Tiers"
    public function getVisibilityAllowedTierIdsProperty()
    {
        if ($this->restrictionMode === 'public') {
            return array_map(fn($id) => (string)$id, $this->categoryAllowedTierIds);
        }
        
        // Restricted: Intersection of Category Allowed AND User Selected
        // Normalize both to strings for reliable intersection
        $catIds = array_map(fn($id) => (string)$id, $this->categoryAllowedTierIds);
        $selIds = array_map(fn($id) => (string)$id, $this->selectedVisibilityTiers);
        
        return array_values(array_intersect($catIds, $selIds));
    }
    
    // Computed Property: Collection of models
    public function getVisibilityAllowedTiersProperty()
    {
        $ids = $this->visibilityAllowedTierIds;
        // Filter from all loaded tiers to preserve order/attributes
        return $this->membershipTiers->whereIn('id', $ids); 
    }

    // Compute IDs for BLUR consideration
    // This methods updates the simple array property used by UI/Validation
    public function computeEligibleBlurTiers()
    {
        // ALWAYS update this so validation/UI stays fresh
        $this->computedVisibilityTierIds = array_map(fn($id) => (string)$id, $this->visibilityAllowedTierIds);
        
        if ($this->isHydrating) return; // Guard only the destructive parts
        
        // Auto-sanitize Blur Selections
        if ($this->blurEnabled) {
             if ($this->restrictedMinTierId && !in_array($this->restrictedMinTierId, $this->computedVisibilityTierIds)) {
                 $this->restrictedMinTierId = null;
             }
             if ($this->restrictedPrivateTierId && !in_array($this->restrictedPrivateTierId, $this->computedVisibilityTierIds)) {
                 $this->restrictedPrivateTierId = null;
             }
             if (!empty($this->selectedRandomTiers)) {
                 $this->selectedRandomTiers = array_intersect($this->selectedRandomTiers, $this->computedVisibilityTierIds);
             }
        }
    }
    
    
    public function computeCategoryAllowedTiers()
    {
        if ($this->categoryId) {
            $cat = ArchiveCategory::with('tiers')->find($this->categoryId);
            if ($cat) {
                 $this->categoryAllowedTierIds = array_map(fn($id) => (string)$id, $cat->getAllowedTierIds());
                  // Auto-clean selected if not allowed
                  if (!$this->isHydrating && !empty($this->selectedVisibilityTiers)) {
                      $valid = array_intersect($this->selectedVisibilityTiers, $this->categoryAllowedTierIds);
                      if (count($valid) !== count($this->selectedVisibilityTiers)) {
                          $this->selectedVisibilityTiers = array_values($valid);
                      }
                  }
            } else {
                $this->categoryAllowedTierIds = [];
            }
        } else {
            $this->categoryAllowedTierIds = [];
        }
        
        // [NEW] Always recompute blur eligibility when category changes
        $this->computeEligibleBlurTiers();
    }

    public function updatedCategoryId($value)
    {
        $this->computeCategoryAllowedTiers();
    }
    
    // ... (computeEligibleBlurTiers) has been replaced/moved above

    public function addEarlyAccessRow()
    {
        $this->earlyAccessRows[] = ['tier_id' => '', 'access_at' => ''];
    }

    public function removeEarlyAccessRow($index)
    {
        unset($this->earlyAccessRows[$index]);
        $this->earlyAccessRows = array_values($this->earlyAccessRows);
        $this->resetErrorBag();
    }

    public function updatedEarlyAccessRows($value, $name)
    {
        // Clear validation for the exact nested key that changed
        $this->resetValidation($name);

        // Also clear the row’s derived errors so corrected values can pass on next save
        if (preg_match('/^earlyAccessRows\.(\d+)\.(tier_id|access_at)$/', $name, $m)) {
            $i = (int) $m[1];
            $this->resetValidation("earlyAccessRows.$i.tier_id");
            $this->resetValidation("earlyAccessRows.$i.access_at");
        }
    }

    public function saveEarlyAccess()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $product = ArchiveProduct::findOrFail($this->productId);
        
        // Validation Logic
        if ($product->go_live_now || !$product->go_live_at) {
             $this->dispatch('hide-ea-modal');
             return; 
        }

        // Re-compute allowed tiers to be safe
        $allowedTiers = $this->getAllowedTiersForProduct($product);
        $allowedTierIds = $allowedTiers->pluck('id')->toArray();

        // Check rows
        foreach ($this->earlyAccessRows as $index => $row) {
            if (!empty($row['tier_id'])) {
                if (!in_array($row['tier_id'], $allowedTierIds)) {
                    $this->addError("earlyAccessRows.{$index}.tier_id", "Tier not allowed.");
                    continue; // Skip further checks for this row if invalid tier
                }

                // [NEW] Check Eligibility
                $tierObj = \App\Models\MembershipTier::find($row['tier_id']);
                if ($tierObj && !$tierObj->has_early_access) {
                     $this->addError("earlyAccessRows.{$index}.tier_id", "Tier not eligible.");
                     continue;
                }
                
                if (!empty($row['access_at'])) {
                    $accessDate = Carbon::parse($row['access_at']);
                    if ($accessDate->gt($product->go_live_at)) {
                        $this->addError("earlyAccessRows.{$index}.access_at", "Date must be before Go Live.");
                    }
                } else {
                     $this->addError("earlyAccessRows.{$index}.access_at", "Date required.");
                }
            }
        }
        
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('toast', type: 'error', message: 'Please fix the highlighted fields.');
            return;
        }

        try {
            // Save
            $product->earlyAccessWindows()->delete();
            
            foreach ($this->earlyAccessRows as $row) {
                if (!empty($row['tier_id']) && !empty($row['access_at'])) {
                    $product->earlyAccessWindows()->create([
                        'membership_tier_id' => $row['tier_id'],
                        'access_at' => $row['access_at']
                    ]);
                }
            }
            
            $this->showEarlyAccessModal = false;
            $this->dispatch('hide-ea-modal');
            $this->dispatch('toast', type: 'success', message: 'Early access config saved.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Unable to save. Please try again.');
        }
    }
        
    // --- Attachments Config ---
    
    public $attachmentAllowedTiers = [];

    public function manageAttachments($id)
    {
        $this->productId = $id;
        $product = ArchiveProduct::with(['attachments', 'attachments.tiers', 'restrictedMinTier'])->findOrFail($id);
        
        // Compute allowed tiers for the product to enforce subset restrictions
        $this->attachmentAllowedTiers = $this->getAllowedTiersForProduct($product);
        
        $this->attachmentRows = $product->attachments->map(function($att) {
            return [
                'id' => $att->id,
                'type' => $att->type,
                'line_text' => $att->line_text,
                'kv_key' => $att->kv_key,
                'kv_value' => $att->kv_value,
                'heading' => $att->heading,
                'body' => $att->body,
                'restriction_mode' => $att->restriction_mode,
                'restriction_type' => $att->restriction_type,
                'restricted_min_tier_id' => $att->restricted_min_tier_id,
                'restricted_private_tier_id' => $att->restricted_private_tier_id,
                'selected_tiers' => $att->tiers->pluck('id')->toArray(),
            ];
        })->toArray();

        $this->showAttachmentsModal = true;
        $this->dispatch('show-att-modal');
    }
    
    public function addAttachmentRow($type)
    {
        $this->attachmentRows[] = [
            'id' => null, // new
            'type' => $type,
            'line_text' => '',
            'kv_key' => '',
            'kv_value' => '',
            'heading' => '',
            'body' => '',
            'restriction_mode' => 'inherit',
            'restriction_type' => null,
            'restricted_min_tier_id' => null,
            'restricted_private_tier_id' => null,
            'selected_tiers' => [],
        ];
    }
    
    public function removeAttachmentRow($index)
    {
         unset($this->attachmentRows[$index]);
         $this->attachmentRows = array_values($this->attachmentRows);
    }

    public function saveAttachments()
    {
        $product = ArchiveProduct::findOrFail($this->productId);
        
        // Re-compute allowed tiers for server-side validation
        $productAllowedTiers = $this->getAllowedTiersForProduct($product);
        $allowedTierIds = $productAllowedTiers->pluck('id')->toArray();

        // 1. Validation
        foreach ($this->attachmentRows as $index => $row) {
            // Basic Fields
            if ($row['type'] === 'line' && empty($row['line_text'])) {
                $this->addError("attachmentRows.{$index}.line_text", "Text is required.");
                return;
            }
            if ($row['type'] === 'kv' && (empty($row['kv_key']) || empty($row['kv_value']))) {
                 $this->addError("attachmentRows.{$index}.kv_key", "Key and Value are required.");
                 return;
            }
            if ($row['type'] === 'rich' && empty($row['body'])) {
                 $this->addError("attachmentRows.{$index}.body", "Content body is required.");
                 return;
            }

            // Restriction Logic
            if ($row['restriction_mode'] === 'restricted') {
                 if (empty($row['restriction_type'])) {
                     $this->addError("attachmentRows.{$index}.restriction_type", "Restriction type is required.");
                     return;
                 }
                 
                 // Enforce Subset Logic
                 if ($row['restriction_type'] === 'hierarchical') {
                     if (empty($row['restricted_min_tier_id'])) {
                         $this->addError("attachmentRows.{$index}.restricted_min_tier_id", "Minimum tier is required.");
                         return;
                     }
                     if (!in_array($row['restricted_min_tier_id'], $allowedTierIds)) {
                         $this->addError("attachmentRows.{$index}.restriction_mode", "Selected tier is not allowed by product restriction.");
                         return;
                     }
                 } elseif ($row['restriction_type'] === 'random') {
                     if (empty($row['selected_tiers'])) {
                         $this->addError("attachmentRows.{$index}.selected_tiers", "Select at least one tier.");
                         return;
                     }
                     foreach($row['selected_tiers'] as $tid) {
                         if(!in_array($tid, $allowedTierIds)) {
                             $this->addError("attachmentRows.{$index}.selected_tiers", "One or more selected tiers are not allowed.");
                             return;
                         }
                     }
                 } elseif ($row['restriction_type'] === 'private') {
                     if (empty($row['restricted_private_tier_id'])) {
                         $this->addError("attachmentRows.{$index}.restricted_private_tier_id", "Private tier is required.");
                         return;
                     }
                     if (!in_array($row['restricted_private_tier_id'], $allowedTierIds)) {
                         $this->addError("attachmentRows.{$index}.restriction_mode", "Selected tier is not allowed.");
                         return;
                     }
                 }
            }
        }

        // 2. Persistence
        // Sync: delete missing IDs, update existing, create new.
        $submittedIds = array_filter(array_column($this->attachmentRows, 'id'));
        $product->attachments()->whereNotIn('id', $submittedIds)->delete();
        
        foreach ($this->attachmentRows as $index => $row) {
            $data = [
                'type' => $row['type'],
                'line_text' => $row['line_text'],
                'kv_key' => $row['kv_key'],
                'kv_value' => $row['kv_value'],
                'heading' => $row['heading'],
                'body' => $row['body'],
                'restriction_mode' => $row['restriction_mode'],
                'restriction_type' => $row['restriction_mode'] == 'restricted' ? $row['restriction_type'] : null,
                'restricted_min_tier_id' => $row['restriction_mode'] == 'restricted' ? $row['restricted_min_tier_id'] : null,
                'restricted_private_tier_id' => $row['restriction_mode'] == 'restricted' ? $row['restricted_private_tier_id'] : null,
                'sort_order' => $index,
            ];
            
            $att = $product->attachments()->updateOrCreate(['id' => $row['id']], $data);
            
            if ($row['restriction_mode'] == 'restricted' && $row['restriction_type'] == 'random') {
                $att->tiers()->sync($row['selected_tiers']);
            } else {
                $att->tiers()->detach();
            }
        }
        
        $this->showAttachmentsModal = false;
        $this->dispatch('hide-att-modal');
        session()->flash('success', 'Attachments saved.');
    }

    // --- Deletion ---
    // --- Deletion ---
    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->confirmingDelete = true;
        $this->dispatch('show-product-delete-modal');
    }

    public function deleteConfirmed()
    {
        if (!$this->deleteId) return;

        $p = ArchiveProduct::findOrFail($this->deleteId);
        // Clean images
        foreach($p->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }
        // Clean 360 images logic if needed later
        
        $p->delete();
        
        $this->confirmingDelete = false;
        $this->deleteId = null;
        $this->dispatch('hide-product-delete-modal');
        session()->flash('success', 'Product deleted.');
    }

    public function resetForm()
    {
        $this->reset(['title', 'categoryId', 'priceMin', 'priceMax', 'quantity', 'descriptionUnlocked']);
        $this->reset(['goLiveNow', 'goLiveAt', 'allowsEarlyAccess', 'restrictionMode', 'restrictionType', 'restrictedMinTierId', 'restrictedPrivateTierId']);
        $this->reset(['blurEnabled', 'clearViewTierIds', 'computedVisibilityTierIds']);
        $this->selectedRandomTiers = [];
        $this->reset(['newImages', 'existingImages', 'new360Images', 'existing360Images', 'earlyAccessRows', 'attachmentRows']);
        
        $this->goLiveNow = true;
        $this->quantity = 1;
        // Defaults
        $this->restrictionMode = 'public';
        $this->currency = 'INR';
    }

    // --- Computed Properties for Preview (Step 4) ---

    // 1. VISIBLE TIERS
    public function getPreviewVisibleTiersProperty()
    {
        // If public, everything is visible
        if ($this->restrictionMode === 'public') {
            return $this->membershipTiers; // Assuming this is loaded in mount() with active tiers
        }
        
        // If restricted, only selected IDs
        // Filter active tiers to ensure we don't show stale/deleted ones, and strict match selection
        return $this->membershipTiers->whereIn('id', $this->selectedVisibilityTiers);
    }

    // 2. CLEAR TIERS (Subset of Visible)
    public function getPreviewClearTiersProperty()
    {
        $visible = $this->previewVisibleTiers;

        // If blur NOT enabled, then CLEAR = VISIBLE (everything is clear)
        if (!$this->blurEnabled) {
            return $visible;
        }

        // If blur IS enabled, we calculate who gets CLEAR view based on restriction type
        // The result MUST be a subset of $visible
        
        if ($this->restrictionType === 'hierarchical' && $this->restrictedMinTierId) {
            $minTier = $this->membershipTiers->firstWhere('id', $this->restrictedMinTierId);
            if (!$minTier) return collect([]); // Misconfiguration or not found
            
            // All visible tiers with level >= minTier level
            return $visible->filter(function($tier) use ($minTier) {
                return $tier->level >= $minTier->level;
            });
        }

        if ($this->restrictionType === 'random' && !empty($this->selectedRandomTiers)) {
            // All visible tiers that are ALSO in the random selection
            return $visible->whereIn('id', $this->selectedRandomTiers);
        }

        if ($this->restrictionType === 'private' && $this->restrictedPrivateTierId) {
            // Only the specific private tier, if it is visible
            return $visible->where('id', $this->restrictedPrivateTierId);
        }

        return collect([]); // Fallback: no clear tiers selected
    }

    // 3. BLUR TIERS (Visible - Clear)
    public function getPreviewBlurTiersProperty()
    {
        $visible = $this->previewVisibleTiers;
        $clear = $this->previewClearTiers;
        
        // Blur = Visible diff Clear
        return $visible->diff($clear);
    }

    public function getPreviewCountsProperty()
    {
        return [
            'visible' => $this->previewVisibleTiers->count(),
            'clear' => $this->previewClearTiers->count(),
            'blur' => $this->previewBlurTiers->count(),
        ];
    }
}
