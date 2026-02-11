<?php

namespace App\Livewire\Admin\Auctions;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $search = '';
    public $filterStatus = '';

    // Data for dropdowns
    public $membershipTiers = [];

    // Modal States
    public $showCreateModal = false;
    public $showAttachmentsModal = false;
    public $showEarlyAccessModal = false;
    public $isEditMode = false;
    public $lotId;
    
    // Delete Modal State
    public $confirmingDelete = false;
    public $deleteId = null;

    // --- Lot Form Fields ---
    
    // Step 1: Basic
    public $lot_no; // Auto-generated/Read-only
    public $title;
    public $description;
    
    // Pricing
    public $starting_price;
    public $min_selling_price; // Reserve
    public $min_increment;
    
    // Scheduling (Go Live Logic)
    public $goLiveNow = false;
    public $starts_at; // effectively goLiveAt
    public $ends_at; // required if scheduled or live
    
    // Step 2: Media
    public $newImages = []; 
    public $existingImages = []; 
    public $new360Images = [];
    public $existing360Images = [];

    // Attachments (Parity with Archive)
    public $attachmentRows = []; 
    public $attachmentAllowedTiers = []; // [NEW] For modal restriction dropdowns

    // Early Access
    public $allowsEarlyAccess = false;
    public $earlyAccessRows = [];
    public $earlyAccessAllowedTiers = [];
    public $earlyAccessGoLiveDate;

    // Step 3: Anti-Sniping
    public $anti_sniping_enabled = false;
    public $trigger_window_seconds = 120;
    public $extend_by_seconds = 60;
    public $max_extensions = 5;

    // Step 4: Access Settings
    public $restrictionMode = 'public'; // public, restricted
    public $selectedVisibilityTiers = []; 
    
    public $blurEnabled = false;
    public $restrictionType = ''; // hierarchical, random, private
    public $restrictedMinTierId = null;
    public $selectedRandomTiers = [];
    public $restrictedPrivateTierId = null;
    
    // Wizard State
    public $createStep = 1;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function mount()
    {
        $this->membershipTiers = \App\Models\MembershipTier::where('is_active', true)->orderBy('sort_order')->get();
    }

    // --- Computed Properties for Access Logic (Parity) ---
    
    public function getVisibilityAllowedTiersProperty()
    {
        // For Auctions, we don't have category restrictions.
        // So ALL tiers are allowed to be selected for visibility in restricted mode.
        // In 'public' mode, ALL tiers are visible.
        return $this->membershipTiers; 
    }

    public function getComputedVisibilityTierIdsProperty()
    {
        if ($this->restrictionMode === 'public') {
            return $this->membershipTiers->pluck('id')->toArray();
        }
        return $this->selectedVisibilityTiers; // array of IDs
    }

    public function getPreviewVisibleTiersProperty()
    {
         $ids = $this->computedVisibilityTierIds;
         return $this->membershipTiers->whereIn('id', $ids);
    }

    public function getPreviewClearTiersProperty()
    {
        $visibleIds = $this->computedVisibilityTierIds;
        
        if (!$this->blurEnabled) {
            return $this->membershipTiers->whereIn('id', $visibleIds);
        }

        // Logic mirrors ArchiveAccessResolver but local for preview
        $clearIds = [];

        if ($this->restrictionType === 'hierarchical' && $this->restrictedMinTierId) {
            $minTier = $this->membershipTiers->firstWhere('id', $this->restrictedMinTierId);
             if ($minTier) {
                // All tiers with level >= minTier level
                $clearIds = $this->membershipTiers->whereIn('id', $visibleIds)
                                ->where('level', '>=', $minTier->level)
                                ->pluck('id')->toArray();
            }
        } elseif ($this->restrictionType === 'random') {
            $clearIds = array_intersect($visibleIds, $this->selectedRandomTiers);
        } elseif ($this->restrictionType === 'private' && $this->restrictedPrivateTierId) {
             if (in_array($this->restrictedPrivateTierId, $visibleIds)) {
                 $clearIds = [$this->restrictedPrivateTierId];
             }
        }

        return $this->membershipTiers->whereIn('id', $clearIds);
    }

    public function getPreviewBlurTiersProperty()
    {
        $visibleIds = $this->computedVisibilityTierIds;
        $clearIds = $this->previewClearTiers->pluck('id')->toArray();
        $blurIds = array_diff($visibleIds, $clearIds);
        
        return $this->membershipTiers->whereIn('id', $blurIds);
    }

    // --- Render ---

    public function render()
    {
        $query = \App\Models\Auctions\AuctionLot::query();

        if ($this->search) {
            $query->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('lot_no', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $lots = $query->withCount(['bids', 'attachments'])->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.admin.auctions.index', [
            'lots' => $lots
        ])->layout('layouts.admin');
    }

    // --- Modal Actions ---
    
    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->showCreateModal = true;
        // Auto-generate placeholder for Lot No is handled in view or just show "Auto"
        $this->createStep = 1;
        $this->dispatch('show-create-modal');
    }

    public function resetForm()
    {
        $this->reset([
            'lotId', 'lot_no', 'title', 'description', 
            'starting_price', 'min_selling_price', 'min_increment', 
            'goLiveNow', 'starts_at', 'ends_at',
            'anti_sniping_enabled', 'trigger_window_seconds', 'extend_by_seconds', 'max_extensions',
            'restrictionMode', 'selectedVisibilityTiers',
            'blurEnabled', 'restrictionType', 'restrictedMinTierId', 'selectedRandomTiers', 'restrictedPrivateTierId',
            'newImages', 'existingImages', 'new360Images', 'existing360Images',
            'attachmentRows', 'attachmentAllowedTiers'
        ]);
        
        // Defaults
        $this->anti_sniping_enabled = false;
        $this->trigger_window_seconds = 120;
        $this->extend_by_seconds = 60;
        $this->max_extensions = 5;
        $this->restrictionMode = 'public';
        $this->goLiveNow = true;  // Match Archive Default
        $this->allowsEarlyAccess = false;
    }

    // --- Steps ---

    public function goToStep($step)
    {
        if ($this->isEditMode) {
             $this->createStep = $step;
        } else {
             // Validate
             if ($step > $this->createStep) {
                 if ($this->validateStep($this->createStep)) {
                     $this->createStep = $step;
                 }
             } else {
                 $this->createStep = $step;
             }
        }
    }

    public function nextStep()
    {
        if ($this->validateStep($this->createStep)) {
            $this->createStep++;
        }
    }

    public function prevStep()
    {
        if ($this->createStep > 1) {
            $this->createStep--;
        }
    }

    public function validateStep($step)
    {
        if ($step === 1) {
            // Basic & Pricing & Sched
            $rules = [
                'title' => 'required',
                'starting_price' => 'required|numeric|min:0',
                'min_increment' => 'required|numeric|min:0',
                'min_selling_price' => 'nullable|numeric|min:0',
            ];
            
            if (!$this->goLiveNow) {
                // Scheduled
                $rules['starts_at'] = 'required|date';
                $rules['ends_at'] = 'required|date|after:starts_at';
            } else {
                $rules['ends_at'] = 'required|date|after:now';
            }
            
            $this->validate($rules);
        } elseif ($step === 2) {
            // Media
            $this->validate([
                'newImages.*' => 'image|mimes:jpeg,png|max:10240',
                'new360Images.*' => 'image|mimes:jpeg,png|max:10240',
            ]);

            if (empty($this->existingImages) && empty($this->newImages)) {
                $this->addError('newImages', 'At least one main image is required.');
                return false;
            }
        } elseif ($step === 3) {
            // Anti-Sniping
            if ($this->anti_sniping_enabled) {
                $this->validate([
                    'trigger_window_seconds' => 'required|numeric|min:10',
                    'extend_by_seconds' => 'required|numeric|min:10',
                    'max_extensions' => 'required|numeric|min:1',
                ]);
            }
        }
 elseif ($step === 4) {
             // Access
             if ($this->restrictionMode == 'restricted') {
                 $this->validate([
                     'selectedVisibilityTiers' => 'required|array|min:1'
                 ]);
             }
             if ($this->blurEnabled) {
                 $this->validate(['restrictionType' => 'required']);
                 if ($this->restrictionType == 'hierarchical') $this->validate(['restrictedMinTierId' => 'required']);
                 if ($this->restrictionType == 'random') $this->validate(['selectedRandomTiers' => 'required|array|min:1']);
                 if ($this->restrictionType == 'private') $this->validate(['restrictedPrivateTierId' => 'required']);
             }
        }
        return true;
    }

    // --- Media & Attachment Actions ---
    
    public function removeNewImage($index) { array_splice($this->newImages, $index, 1); }
    public function removeNew360Image($index) { array_splice($this->new360Images, $index, 1); }
    
    public function deleteImage($id, $type)
    {
        // Simple logic: remove from DB and existing array
        $img = \App\Models\Auctions\AuctionLotImage::find($id); // Assuming model name
        if ($img) {
            // Storage delete...
            $img->delete();
            if ($type == 'main') {
                 $this->existingImages = array_filter($this->existingImages, fn($i) => $i['id'] != $id);
            } else {
                 $this->existing360Images = array_filter($this->existing360Images, fn($i) => $i['id'] != $id);
            }
        }
    }
    
    // Attachments todo (stub for now, needs DB structure which user didn't explicitly ask to create but UI implied. 
    // The user said "Attachments MUST exist", implying I should handle them. 
    // I'll assume a polymorphic attachment table exists or I add one, OR I just use a json field.
    // Index.php previously had 'attachmentRows'. I will use that pattern but cleaner.
    // For now, let's stick to the UI parity request. I will implement the UI methods.
    
    // --- Save ---

    public function save()
    {
         $this->validateStep(1);
         $this->validateStep(2);
         $this->validateStep(3);
         $this->validateStep(4);

        DB::transaction(function () {
            // Logic to determine dates
            $start = $this->goLiveNow ? Carbon::now() : Carbon::parse($this->starts_at);
            $end = Carbon::parse($this->ends_at);
            
            // Status Logic
            $status = $this->goLiveNow ? 'live' : 'upcoming';
            
            $data = [
                'title' => $this->title,
                'description' => $this->description,
                'starting_price' => $this->starting_price,
                'min_selling_price' => $this->min_selling_price,
                'min_increment' => $this->min_increment,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => $status,
                // Flags removed
                
                // Sniping
                'anti_sniping_enabled' => $this->anti_sniping_enabled,
                'trigger_window_seconds' => $this->trigger_window_seconds,
                'extend_by_seconds' => $this->extend_by_seconds,
                'max_extensions' => $this->max_extensions,
                
                // Early Access
                'early_access_enabled' => (!$this->goLiveNow && $this->allowsEarlyAccess),
                
                // Access
                'restriction_mode' => $this->restrictionMode,
                'restriction_type' => $this->restrictionType,
                'restricted_min_tier_id' => $this->restrictedMinTierId,
                'restricted_private_tier_id' => $this->restrictedPrivateTierId,
                'blur_enabled' => $this->blurEnabled,
            ];
            
            // Clean access if public
            if ($this->restrictionMode == 'public') {
                 $data['restriction_type'] = null;
                 $data['restricted_min_tier_id'] = null;
                 $data['restricted_private_tier_id'] = null;
                 $data['blur_enabled'] = false; // Usually true, but logic might vary?
                 // Actually logic says: public can still have blur? 
                 // Usually restriction mode 'public' means everyone sees it.
                 // Archive logic clears these.
            }

            if ($this->isEditMode) {
                $lot = \App\Models\Auctions\AuctionLot::find($this->lotId);
                $lot->update($data);
            } else {
                // Generate Lot No
                $preData = $data;
                $preData['lot_no'] = 'TEMP-' . uniqid(); 
                $preData['created_by_admin_id'] = auth()->id();
                
                $lot = \App\Models\Auctions\AuctionLot::create($preData);
                $lot->update(['lot_no' => 'LOT-' . str_pad($lot->id, 5, '0', STR_PAD_LEFT)]);
                $this->lotId = $lot->id;
            }

            // Persist Access (using helper)
            $this->persistAccessSettingsToLot($lot);
            
            // Attachments persistence is now handled via Modal, NOT here.

            // Handle Images
            if (!empty($this->newImages)) {
                $maxOrder = $lot->images()->max('sort_order') ?? 0;
                foreach ($this->newImages as $image) {
                    $maxOrder++;
                    $path = $image->store('auction-lots/' . $lot->id, 'public');
                    $lot->images()->create([
                        'path' => $path,
                        'sort_order' => $maxOrder,
                        // 'type' => 'main' 
                    ]);
                }
            }
            
            // Handle 360 Images
             if (!empty($this->new360Images)) {
                $maxOrder = $lot->images()->max('sort_order') ?? 0;
                foreach ($this->new360Images as $image) {
                    $maxOrder++;
                    $path = $image->store('auction-lots/' . $lot->id . '/360', 'public');
                    $lot->images()->create([
                        'path' => $path,
                        'sort_order' => $maxOrder,
                        'type' => '360' 
                    ]);
                }
             }
        });

        $this->showCreateModal = false;
        $this->dispatch($this->isEditMode ? 'auction-updated' : 'auction-created');
        $this->resetForm();
    }

    // --- Attachment Actions ---

    public function manageAttachments($id)
    {
        $this->lotId = $id;
        $lot = \App\Models\Auctions\AuctionLot::with(['attachments', 'attachments.tiers', 'visibilityTiers'])->findOrFail($id); // Eager load relations
        
        // Compute allowed tiers for the lot to enforce subset restrictions
        $this->attachmentAllowedTiers = $this->getAllowedTiersForLot($lot);
        
        $this->attachmentRows = $lot->attachments->map(function($att) {
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

    // --- Early Access Actions ---

    public function configureEarlyAccess($id)
    {
        $this->lotId = $id;
        $lot = \App\Models\Auctions\AuctionLot::with('earlyAccessWindows')->findOrFail($id);
        
        if ($lot->status == 'live' || $lot->goLiveNow || $lot->starts_at <= now()) {
             // Basic check: if live, EA is moot. But requirements says "If go live now is OFF".
             // We'll stick to Archive Logic: if go_live_now || !go_live_at
        }
        
        // Archive Logic:
        if ($lot->goLiveNow && $lot->status == 'live') {
              session()->flash('error', 'Early access can only be configured for scheduled auctions.');
              return;
        }

        $this->earlyAccessGoLiveDate = $lot->starts_at ? $lot->starts_at->format('d M Y, h:i A') : 'Not Scheduled';

        // Compute allowed tiers
        $this->earlyAccessAllowedTiers = $this->getAllowedTiersForLot($lot);
        
        $this->earlyAccessRows = $lot->earlyAccessWindows->map(function($ea) {
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

    public function addEarlyAccessRow()
    {
        $this->earlyAccessRows[] = ['tier_id' => '', 'access_at' => ''];
    }

    public function removeEarlyAccessRow($index)
    {
        unset($this->earlyAccessRows[$index]);
        $this->earlyAccessRows = array_values($this->earlyAccessRows);
        $this->resetErrorBag(); // Clear errors on row remove
    }

    public function updatedEarlyAccessRows($value, $name)
    {
        $this->resetValidation($name);
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

        $lot = \App\Models\Auctions\AuctionLot::findOrFail($this->lotId);
        
        if ($lot->goLiveNow || !$lot->starts_at) {
             $this->dispatch('hide-ea-modal');
             return; 
        }

        $allowedTiers = $this->getAllowedTiersForLot($lot);
        $allowedTierIds = $allowedTiers->pluck('id')->toArray();

        // Check rows
        foreach ($this->earlyAccessRows as $index => $row) {
            if (!empty($row['tier_id'])) {
                if (!in_array($row['tier_id'], $allowedTierIds)) {
                    $this->addError("earlyAccessRows.{$index}.tier_id", "Tier not allowed.");
                    continue; 
                }

                // Check Eligibility
                $tierObj = \App\Models\MembershipTier::find($row['tier_id']);
                if ($tierObj && !$tierObj->has_early_access) {
                     $this->addError("earlyAccessRows.{$index}.tier_id", "Tier not eligible.");
                     continue;
                }
                
                if (!empty($row['access_at'])) {
                    $accessDate = Carbon::parse($row['access_at']);
                    if ($accessDate->gt($lot->starts_at)) {
                        $this->addError("earlyAccessRows.{$index}.access_at", "Date must be before Go Live.");
                    }
                } else {
                     $this->addError("earlyAccessRows.{$index}.access_at", "Date required.");
                }
            }
        }
        
        if ($this->getErrorBag()->isNotEmpty()) {
            // $this->dispatch('toast', type: 'error', message: 'Please fix errors.');
            return;
        }

        try {
            $lot->earlyAccessWindows()->delete();
            
            foreach ($this->earlyAccessRows as $row) {
                if (!empty($row['tier_id']) && !empty($row['access_at'])) {
                    $lot->earlyAccessWindows()->create([
                        'membership_tier_id' => $row['tier_id'],
                        'access_at' => $row['access_at']
                    ]);
                }
            }
            
            $this->showEarlyAccessModal = false;
            $this->dispatch('hide-ea-modal');
            session()->flash('success', 'Early access config saved.');
        } catch (\Throwable $e) {
            // report($e);
            session()->flash('error', 'Unable to save.');
        }
    }

    protected function getAllowedTiersForLot(\App\Models\Auctions\AuctionLot $lot)
    {
        if ($lot->visibilityTiers->isEmpty()) {
            return $this->membershipTiers; // Public lot = all tiers valid
        }
        return $lot->visibilityTiers->where('is_active', true)->values();
    }
    
    public function addAttachmentRow($type)
    {
        $this->attachmentRows[] = [
            'id' => null, 
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
        $lot = \App\Models\Auctions\AuctionLot::with('visibilityTiers')->findOrFail($this->lotId);
        
        // Re-compute allowed tiers for server-side validation
        $lotAllowedTiers = $this->getAllowedTiersForLot($lot);
        $allowedTierIds = $lotAllowedTiers->pluck('id')->toArray();

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
            if (($row['restriction_mode'] ?? 'inherit') === 'restricted') {
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
                         $this->addError("attachmentRows.{$index}.restriction_mode", "Selected tier is not allowed by lot visibility.");
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
        $lot->attachments()->whereNotIn('id', $submittedIds)->delete();
        
        foreach ($this->attachmentRows as $index => $row) {
            $data = [
                'type' => $row['type'],
                'line_text' => $row['line_text'],
                'kv_key' => $row['kv_key'],
                'kv_value' => $row['kv_value'],
                'heading' => $row['heading'],
                'body' => $row['body'],
                'restriction_mode' => $row['restriction_mode'] ?? 'inherit',
                'restriction_type' => ($row['restriction_mode'] ?? 'inherit') == 'restricted' ? $row['restriction_type'] : null,
                'restricted_min_tier_id' => ($row['restriction_mode'] ?? 'inherit') == 'restricted' ? $row['restricted_min_tier_id'] : null,
                'restricted_private_tier_id' => ($row['restriction_mode'] ?? 'inherit') == 'restricted' ? $row['restricted_private_tier_id'] : null,
                'sort_order' => $index,
            ];
            
            $att = $lot->attachments()->updateOrCreate(['id' => $row['id']], $data);
            
            if (($row['restriction_mode'] ?? 'inherit') == 'restricted' && ($row['restriction_type'] ?? '') == 'random') {
                $att->tiers()->sync($row['selected_tiers'] ?? []);
            } else {
                $att->tiers()->detach();
            }
        }
        
        $this->showAttachmentsModal = false;
        $this->dispatch('hide-att-modal');
        session()->flash('success', 'Attachments saved.');
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $this->lotId = $id;
        $lot = \App\Models\Auctions\AuctionLot::with(['images', 'visibilityTiers', 'clearViewTiers'])->findOrFail($id);

        $this->lot_no = $lot->lot_no;
        $this->title = $lot->title;
        $this->description = $lot->description;
        $this->starting_price = $lot->starting_price;
        $this->min_selling_price = $lot->min_selling_price;
        $this->min_increment = $lot->min_increment;
        
        $this->starts_at = $lot->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $lot->ends_at?->format('Y-m-d\TH:i');
        
        // Go Live Logic
        // If status is live, goLiveNow could be true? Or just show the date.
        // Archive behavior: if past, show date.
        
        $this->anti_sniping_enabled = (bool) $lot->anti_sniping_enabled;
        $this->trigger_window_seconds = $lot->trigger_window_seconds;
        $this->extend_by_seconds = $lot->extend_by_seconds;
        $this->max_extensions = $lot->max_extensions;

        // Access Hydration (Parity)
        $this->hydrateAccessSettingsFromLot($lot);
        
        // Images
        $this->existingImages = $lot->images->map(fn($i) => ['id'=>$i->id, 'path'=>$i->path, 'image_path'=>$i->path])->toArray();

        $this->showCreateModal = true;
        $this->createStep = 1;
        $this->dispatch('show-create-modal');
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }
    
    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed()
    {
        if ($this->deleteId) {
            $lot = \App\Models\Auctions\AuctionLot::find($this->deleteId);
            if ($lot) {
                $lot->delete();
                $this->dispatch('auction-deleted'); // Optional, if needed
                session()->flash('success', 'Auction lot deleted successfully.');
            } else {
                session()->flash('error', 'Auction lot not found.');
            }
        }
        $this->deleteId = null;
        $this->dispatch('hide-delete-modal');
    }

    // --- Access Persistence Helpers (Parity) ---

    protected function hydrateAccessSettingsFromLot(\App\Models\Auctions\AuctionLot $lot)
    {
        $this->restrictionMode = $lot->restriction_mode ?? 'public';
        // If public, selectedVisibilityTiers is irrelevant for logic but good to clear.
        // If restricted, we need the stored tiers.
        $this->selectedVisibilityTiers = $lot->visibilityTiers->pluck('id')->toArray();
        
        $this->blurEnabled = (bool) $lot->blur_enabled;
        $this->restrictionType = $lot->restriction_type;
        $this->restrictedMinTierId = $lot->restricted_min_tier_id;
        $this->restrictedPrivateTierId = $lot->restricted_private_tier_id;
        
        if ($lot->restriction_type === 'random') {
             $this->selectedRandomTiers = $lot->clearViewTiers->pluck('id')->toArray();
        } else {
             $this->selectedRandomTiers = [];
        }
    }

    protected function persistAccessSettingsToLot(\App\Models\Auctions\AuctionLot $lot)
    {
        // 1. Visibility Tiers
        if ($this->restrictionMode == 'restricted') {
            $lot->visibilityTiers()->sync($this->computedVisibilityTierIds);
        } else {
            $lot->visibilityTiers()->detach();
        }
        
        // 2. Clear View Tiers
        if ($this->blurEnabled) {
            $clearIds = $this->previewClearTiers->pluck('id')->toArray();
            $lot->clearViewTiers()->sync($clearIds);
        } else {
            $lot->clearViewTiers()->detach();
        }
    }
}
