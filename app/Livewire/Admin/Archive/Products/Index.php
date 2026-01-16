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
    public $showAttachmentsModal = false;
    public $isEditMode = false;
    public $productId;
    
    // Delete Modal State
    public $confirmingDelete = false;
    public $deleteId = null;

    // --- Product Form Fields ---
    public $title;
    public $categoryId; // selected category
    public $priceMin;
    public $priceMax;
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
        if ($value === 'public') {
            $this->restrictionType = null;
            $this->restrictedMinTierId = null;
            $this->restrictedPrivateTierId = null;
            $this->selectedRandomTiers = [];
        }
    }

    public function updatedRestrictionType($value)
    {
        if ($value !== 'hierarchical') {
            $this->restrictedMinTierId = null;
        }
        if ($value !== 'private') {
            $this->restrictedPrivateTierId = null;
        }
        if ($value !== 'random') {
            $this->selectedRandomTiers = [];
        }
    }

    public function validateStep($step)
    {
        if ($step === 1) {
            $this->validate([
                'title' => 'required|string|max:255',
                'categoryId' => 'required|exists:archive_categories,id',
                'priceMin' => 'nullable|integer|min:0',
                'priceMax' => 'nullable|integer|min:0',
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
                'restrictionType' => ['exclude_unless:restrictionMode,restricted', 'required', 'in:hierarchical,random,private'],
                'restrictedMinTierId' => ['exclude_unless:restrictionType,hierarchical', 'required', 'exists:membership_tiers,id'],
                'restrictedPrivateTierId' => ['exclude_unless:restrictionType,private', 'required', 'exists:membership_tiers,id'],
                'selectedRandomTiers' => ['exclude_unless:restrictionType,random', 'array', 'min:1'],
                'selectedRandomTiers.*' => ['exclude_unless:restrictionType,random', 'exists:membership_tiers,id'],
            ]);
        }
    }

    public function nextStep()
    {
        $this->validateStep($this->createStep);
        if ($this->createStep < 3) {
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
        $this->productId = $id;

        $product = ArchiveProduct::with(['images', 'images360', 'tiers'])->findOrFail($id);
        
        $this->title = $product->title;
        $this->categoryId = $product->archive_category_id;
        $this->priceMin = $product->price_min_amount;
        $this->priceMax = $product->price_max_amount;
        $this->descriptionUnlocked = $product->description_unlocked; 
        // Note: We ignore description_locked in UI as consolidated
        
        $this->goLiveNow = $product->go_live_now;
        $this->goLiveAt = $product->go_live_at ? $product->go_live_at->format('Y-m-d\TH:i') : null;
        $this->allowsEarlyAccess = $product->early_access_enabled;
        
        $this->restrictionMode = $product->restriction_mode;
        $this->restrictionType = $product->restriction_type;
        $this->restrictedMinTierId = $product->restricted_min_tier_id;
        $this->restrictedPrivateTierId = $product->restricted_private_tier_id;
        
        $this->existingImages = $product->images;
        $this->existing360Images = $product->images360;
        
        if ($product->restriction_type === 'random') {
             $this->selectedRandomTiers = $product->tiers->pluck('id')->toArray();
        }
        
        $this->createStep = 1;

        $this->dispatch('show-create-modal');
    }

    public function storeProduct()
    {
        logger()->info('storeProduct called', ['step' => $this->createStep, 'data' => $this->all()]);
        
        $this->validateStep(1);
        $this->validateStep(2);
        
        // Normalize restriction data before final validation to prevent stale errors
        if ($this->restrictionMode === 'public') {
            $this->restrictionType = null;
            $this->restrictedMinTierId = null;
            $this->restrictedPrivateTierId = null;
            $this->selectedRandomTiers = [];
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
        $this->validateStep(3);

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
                'currency' => $this->currency,
                'go_live_now' => $this->goLiveNow,
                'go_live_at' => $this->goLiveNow ? null : $this->goLiveAt,
                'early_access_enabled' => (!$this->goLiveNow && $this->allowsEarlyAccess),
                'restriction_mode' => $this->restrictionMode,
                'restriction_type' => $this->restrictionType,
                'restricted_min_tier_id' => $this->restrictedMinTierId,
                'restricted_private_tier_id' => $this->restrictedPrivateTierId,
            ]);

            // Save Tiers (Random)
            if ($this->restrictionMode === 'restricted' && $this->restrictionType === 'random') {
                $product->tiers()->sync($this->selectedRandomTiers);
            }
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
            'go_live_now' => $this->goLiveNow,
            'go_live_at' => $this->goLiveNow ? null : $this->goLiveAt,
            'early_access_enabled' => (!$this->goLiveNow && $this->allowsEarlyAccess),
            'restriction_mode' => $this->restrictionMode,
            'restriction_type' => $this->restrictionType,
            'restricted_min_tier_id' => $this->restrictedMinTierId,
            'restricted_private_tier_id' => $this->restrictedPrivateTierId,
        ]);

         if ($this->restrictionMode === 'restricted' && $this->restrictionType === 'random') {
            $product->tiers()->sync($this->selectedRandomTiers);
        } else {
            $product->tiers()->detach();
        }

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
        if ($type === 'main') {
            $img = ArchiveProductImage::findOrFail($imageId);
            // Verify ownership
            if ($this->productId && $img->archive_product_id == $this->productId) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
                // Refresh
                $this->edit($this->productId); 
            }
        } elseif ($type === '360') {
             // Assuming ArchiveProduct360Image model exists
             $img = \App\Models\Archive\ArchiveProduct360Image::findOrFail($imageId);
             if ($this->productId && $img->archive_product_id == $this->productId) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
                $this->edit($this->productId); 
             }
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

    public function addEarlyAccessRow()
    {
        $this->earlyAccessRows[] = ['tier_id' => '', 'access_at' => ''];
    }

    public function removeEarlyAccessRow($index)
    {
        unset($this->earlyAccessRows[$index]);
        $this->earlyAccessRows = array_values($this->earlyAccessRows);
    }

    public function saveEarlyAccess()
    {
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
        foreach ($this->earlyAccessRows as $row) {
            if (!empty($row['tier_id'])) {
                if (!in_array($row['tier_id'], $allowedTierIds)) {
                    $this->addError("earlyAccessRows", "One or more selected tiers are not allowed for this product restriction.");
                    return;
                }
                
                if (!empty($row['access_at'])) {
                    $accessDate = Carbon::parse($row['access_at']);
                    if ($accessDate->gt($product->go_live_at)) {
                        $this->addError("earlyAccessRows", "Early access date must be before Go Live date ({$product->go_live_at->format('Y-m-d H:i')}).");
                        return;
                    }
                } else {
                     $this->addError("earlyAccessRows", "Access date is required.");
                     return;
                }
            }
        }

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
        session()->flash('success', 'Early access config saved.');
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
        $this->reset(['title', 'categoryId', 'priceMin', 'priceMax', 'descriptionUnlocked']);
        $this->reset(['goLiveNow', 'goLiveAt', 'allowsEarlyAccess', 'restrictionMode', 'restrictionType', 'restrictedMinTierId', 'restrictedPrivateTierId']);
        $this->selectedRandomTiers = [];
        $this->reset(['newImages', 'existingImages', 'new360Images', 'existing360Images', 'earlyAccessRows', 'attachmentRows']);
        
        $this->goLiveNow = true;
        // Defaults
        $this->restrictionMode = 'public';
        $this->currency = 'INR';
    }
}
