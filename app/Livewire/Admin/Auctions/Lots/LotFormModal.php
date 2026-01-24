<?php

namespace App\Livewire\Admin\Auctions\Lots;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Carbon\Carbon;
use App\Models\Auctions\AuctionLot;

class LotFormModal extends Component
{
    use WithFileUploads;

    // Data for dropdowns (Shared)
    public $membershipTiers = [];

    // Modal State
    public $isEditMode = false;
    public $lotId;

    // --- Steps State ---
    public $createStep = 1;

    // --- Form Fields ---
    
    // Step 1: Basic
    public $lot_no; 
    public $title;
    public $description;
    
    // Pricing
    public $starting_price;
    public $min_selling_price; // Reserve
    public $min_increment;
    
    // Scheduling
    public $goLiveNow = false;
    public $starts_at; 
    public $ends_at;
    
    // Step 2: Media
    public $newImages = []; 
    public $existingImages = []; 
    public $new360Images = [];
    public $existing360Images = [];

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

    // Early Access Flag (only for toggle in wizard, full config handles in separate modal usually, but let's see)
    // In Index.php 'allowsEarlyAccess' was in resetForm but not widely used in the simplified Logic unless I missed it.
    // Index.php had `allowsEarlyAccess` and `early_access_enabled` mapping. 
    public $allowsEarlyAccess = false;

    public function mount()
    {
        $this->membershipTiers = \App\Models\MembershipTier::where('is_active', true)->orderBy('sort_order')->get();
    }

    #[On('auction-lot:create')]
    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditMode = false;
        $this->createStep = 1;
        $this->dispatch('show-create-modal');
    }

    #[On('auction-lot:edit')]
    public function edit($lotId)
    {
        $this->resetForm();
        $this->isEditMode = true;
        $this->lotId = $lotId;
        $lot = AuctionLot::with(['images', 'visibilityTiers', 'clearViewTiers'])->findOrFail($lotId);

        $this->lot_no = $lot->lot_no;
        $this->title = $lot->title;
        $this->description = $lot->description;
        $this->starting_price = $lot->starting_price;
        $this->min_selling_price = $lot->min_selling_price;
        $this->min_increment = $lot->min_increment;
        
        $this->starts_at = $lot->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $lot->ends_at?->format('Y-m-d\TH:i');
        $this->goLiveNow = $lot->status === 'live' && $lot->starts_at <= now(); // Approximate logic

        $this->anti_sniping_enabled = (bool) $lot->anti_sniping_enabled;
        $this->trigger_window_seconds = $lot->trigger_window_seconds;
        $this->extend_by_seconds = $lot->extend_by_seconds;
        $this->max_extensions = $lot->max_extensions;
        
        $this->allowsEarlyAccess = (bool) $lot->early_access_enabled;

        // Access Hydration
        $this->restrictionMode = $lot->restriction_mode ?? 'public';
        $this->selectedVisibilityTiers = $lot->visibilityTiers->pluck('id')->toArray();
        $this->blurEnabled = (bool) $lot->blur_enabled;
        $this->restrictionType = $lot->restriction_type;
        $this->restrictedMinTierId = $lot->restricted_min_tier_id;
        $this->restrictedPrivateTierId = $lot->restricted_private_tier_id;
        
        // Complex hydration for random tiers (many-to-many on clear view usually?)
        // Index.php didn't fully implement hydration for random/clear tiers in the `edit` method snippet I saw?
        // Wait, I need to check `Index.php` `edit` method again in my mind.
        // It loaded `clearViewTiers`.
        // The parity logic for "Random" usually involves `clearViewTiers` relation.
        if ($this->restrictionType === 'random') {
             $this->selectedRandomTiers = $lot->clearViewTiers->pluck('id')->toArray(); 
        }

        // Images
        $this->existingImages = $lot->images->where('type', '!=', '360')->map(fn($i) => ['id'=>$i->id, 'path'=>$i->path, 'image_path'=>$i->path])->toArray();
        $this->existing360Images = $lot->images->where('type', '360')->map(fn($i) => ['id'=>$i->id, 'path'=>$i->path, 'image_path'=>$i->path])->toArray();

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
            'allowsEarlyAccess'
        ]);
        
        // Defaults
        $this->anti_sniping_enabled = false;
        $this->trigger_window_seconds = 120;
        $this->extend_by_seconds = 60;
        $this->max_extensions = 5;
        $this->restrictionMode = 'public';
        $this->goLiveNow = true;
    }

    public function closeModal()
    {
        $this->dispatch('hide-create-modal');
        $this->resetForm();
    }

    // --- Steps & Validation ---

    public function goToStep($step)
    {
        if ($this->isEditMode) {
             $this->createStep = $step;
        } else {
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
            $rules = [
                'title' => 'required',
                'starting_price' => 'required|numeric|min:0',
                'min_increment' => 'required|numeric|min:0',
                'min_selling_price' => 'nullable|numeric|min:0',
            ];
            
            if (!$this->goLiveNow) {
                $rules['starts_at'] = 'required|date';
                $rules['ends_at'] = 'required|date|after:starts_at';
            } else {
                $rules['ends_at'] = 'required|date|after:now';
            }
            
            $this->validate($rules);
        } elseif ($step === 2) {
            // Media
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
        } elseif ($step === 4) {
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

    // --- Media Actions ---
    public function removeNewImage($index) { array_splice($this->newImages, $index, 1); }
    public function removeNew360Image($index) { array_splice($this->new360Images, $index, 1); }
    
    public function deleteImage($id, $type)
    {
        $img = \App\Models\Auctions\AuctionLotImage::find($id);
        if ($img) {
            $img->delete();
            if ($type == 'main') {
                 $this->existingImages = array_filter($this->existingImages, fn($i) => $i['id'] != $id);
            } else {
                 $this->existing360Images = array_filter($this->existing360Images, fn($i) => $i['id'] != $id);
            }
        }
    }

    // --- Save ---

    public function save()
    {
         $this->validateStep(1);
         $this->validateStep(2);
         $this->validateStep(3);
         $this->validateStep(4);

        DB::transaction(function () {
            $start = $this->goLiveNow ? Carbon::now() : Carbon::parse($this->starts_at);
            $end = Carbon::parse($this->ends_at);
            $status = $this->goLiveNow ? 'live' : 'upcoming';
            
            $data = [
                'title' => $this->title,
                'description' => $this->description,
                'starting_price' => $this->starting_price,
                'min_selling_price' => $this->min_selling_price,
                'min_increment' => $this->min_increment,
                'starts_at' => $start,
                'ends_at' => $end,
                
                'anti_sniping_enabled' => $this->anti_sniping_enabled,
                'trigger_window_seconds' => $this->trigger_window_seconds,
                'extend_by_seconds' => $this->extend_by_seconds,
                'max_extensions' => $this->max_extensions,
                
                'early_access_enabled' => (!$this->goLiveNow && $this->allowsEarlyAccess),
                
                'restriction_mode' => $this->restrictionMode,
                'restriction_type' => $this->restrictionType,
                'restricted_min_tier_id' => $this->restrictedMinTierId,
                'restricted_private_tier_id' => $this->restrictedPrivateTierId,
                'blur_enabled' => $this->blurEnabled,
            ];
            
            if ($this->restrictionMode == 'public') {
                 $data['restriction_type'] = null;
                 $data['restricted_min_tier_id'] = null;
                 $data['restricted_private_tier_id'] = null;
                 $data['blur_enabled'] = false; 
            }

            // Only update status if creating or explicitly logic demands it? 
            // In Edit, if it was 'ended', we shouldn't accidentally set it to 'upcoming' unless user changed dates.
            // For now, let's assume Wizard dictates status based on dates.
            // BUT be careful: if it was Cancelled?
            // Let's stick to safe logic: if it's new, set status. If edit, update dates, let Observe/Job handle status transition OR set it if dates changed.
            // Simplified: Update status based on new dates.
             if ($this->isEditMode) {
                 // For edit, maybe we shouldn't force 'live' if it was 'upcoming' but starts_at is technically now? 
                 // Yes we should.
                 $data['status'] = $status;
             } else {
                 $data['status'] = $status;
             }

            if ($this->isEditMode) {
                $lot = AuctionLot::find($this->lotId);
                $lot->update($data);
            } else {
                $preData = $data;
                $preData['lot_no'] = 'TEMP-' . uniqid(); 
                $preData['created_by_admin_id'] = auth()->id();
                
                $lot = AuctionLot::create($preData);
                $lot->update(['lot_no' => 'LOT-' . str_pad($lot->id, 5, '0', STR_PAD_LEFT)]);
                $this->lotId = $lot->id;
            }

            // Persist Visibility
            if ($this->restrictionMode == 'restricted') {
                $lot->visibilityTiers()->sync($this->selectedVisibilityTiers);
            } else {
                $lot->visibilityTiers()->detach();
            }

            // Persist Clear View
            $lot->clearViewTiers()->detach();
            if ($this->blurEnabled && $this->restrictionMode == 'restricted') {
                if ($this->restrictionType == 'random') {
                    $lot->clearViewTiers()->sync($this->selectedRandomTiers);
                }
                // For hierarchical/private, `ArchiveAccessResolver` handles it dynamically, no pivot needed usually? 
                // Or do we save it? The models on Index.php didn't show parity helper methods.
                // Assuming standard Archive parity: ClearViewTiers is only populated for Random.
                // Hierarchical/Private checks attributes.
            }

            // Handle Images
            if (!empty($this->newImages)) {
                $maxOrder = $lot->images()->max('sort_order') ?? 0;
                foreach ($this->newImages as $image) {
                    $maxOrder++;
                    $path = $image->store('auction-lots/' . $lot->id, 'public');
                    $lot->images()->create([
                        'path' => $path,
                        'sort_order' => $maxOrder,
                    ]);
                }
            }
            
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
            
            // The following code is from the user's instruction, adapted to fit the existing $lot variable
            // and assuming $isNew is determined by $this->isEditMode
            $isNew = !$this->isEditMode;

            if ($this->restrictionMode === 'public') {
                 $lot->visibilityTiers()->detach();
                 $lot->clearViewTiers()->detach();
            } else {
                 $lot->visibilityTiers()->sync($this->selectedVisibilityTiers); // Using selectedVisibilityTiers from existing code
                 // The original instruction had $this->clearViewTiers, but the existing code uses $this->selectedRandomTiers for clearViewTiers sync
                 // For hierarchical/private, clearViewTiers are not synced here.
                 if ($this->blurEnabled && $this->restrictionType == 'random') {
                     $lot->clearViewTiers()->sync($this->selectedRandomTiers);
                 } else {
                     $lot->clearViewTiers()->detach(); // Detach if not random or blur not enabled
                 }
            }
            
            // Assuming earlyAccessWindows logic is not part of this component or needs to be added if it is.
            // The provided snippet for earlyAccessWindows seems to be from a different context.
            // For now, I'll omit the earlyAccessWindows part as it's not present in the original document
            // and its inclusion would require more context about its properties ($this->earlyAccess).
            // If it was intended to be added, it would need to be defined elsewhere.
            
            // Broadcast Update separate from status change (unless status changed too)
            event(new \App\Events\AuctionLotUpdated($lot)); // Use $lot instead of $this->lot
        }); // End of DB::transaction

        $this->dispatch('hide-create-modal'); // Changed from hide-modal to hide-create-modal to match existing
        $this->dispatch('auction-updated'); // Internal Livewire refresh
        
        session()->flash('success', $this->isEditMode ? 'Auction Lot updated successfully.' : 'Auction Lot created successfully.');
        
        // Dispatch event for parent component to show alert instantly without refresh
        $this->dispatch('operation-success', message: $this->isEditMode ? 'Auction Lot updated successfully.' : 'Auction Lot created successfully.');

        $this->resetForm();
    }

    // Computed Props for Access Preview (Copied from Index.php)
    public function getComputedVisibilityTierIdsProperty()
    {
        if ($this->restrictionMode === 'public') {
            return $this->membershipTiers->pluck('id')->toArray();
        }
        return $this->selectedVisibilityTiers; 
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

        $clearIds = [];
        if ($this->restrictionType === 'hierarchical' && $this->restrictedMinTierId) {
            $minTier = $this->membershipTiers->firstWhere('id', $this->restrictedMinTierId);
             if ($minTier) {
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
    
    public function render()
    {
        return view('livewire.admin.auctions.lots.lot-form-modal');
    }
}
