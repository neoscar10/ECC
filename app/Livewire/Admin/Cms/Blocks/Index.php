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
    public $type = 'card'; // card, banner
    public $isActive = true;
    public $sortOrder = 0;
    
    // Content Fields (Mapped to JSON)
    public $contentTitle;
    public $contentSubtitle;
    public $contentBody;
    public $contentCtaText;
    public $contentCtaUrl;
    public $contentImage; // file upload
    public $existingContentImage; // url string
    
    // Restriction Fields
    public $restrictionMode = 'public';
    public $restrictionType; // hierarchical, random, private
    public $restrictedMinTierId;
    public $restrictedPrivateTierId;
    public $selectedVisibilityTiers = [];
    public $selectedRandomTiers = []; // For Random type specifically? Archive uses visibility pivot for random
    
    // Blur Fields
    public $blurEnabled = false;
    // public $blurStrategy; // We can default to hierarchical or mirror restriction type?
    // Let's assume strategy mirrors restriction type for simplicity unless overridden
    // Archive UI consolidates logic.
    
    // Helpers
    public $computedVisibilityTierIds = [];

    // Wizard State
    public $createStep = 1; // 1: Content, 2: Access & Blur

    public function mount()
    {
        $this->membershipTiers = MembershipTier::where('is_active', true)->orderBy('sort_order')->get();
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
            'blocks' => $query->orderBy('sort_order')->latest()->paginate(10)
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
        // For CMS blocks, if restriction is public, ALL tiers are theoretically visible?
        // But if public, usually no need for restriction logic.
        // If restricted, visibility set defines the universe.
        if ($this->restrictionMode === 'public') {
             $this->computedVisibilityTierIds = $this->membershipTiers->pluck('id')->map(fn($id)=>(string)$id)->toArray();
        } else {
             $this->computedVisibilityTierIds = array_map(fn($id)=>(string)$id, $this->selectedVisibilityTiers);
        }
    }

    public function validateStep($step)
    {
        if ($step === 1) {
            $this->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:card,banner',
                'isActive' => 'boolean',
                'sortOrder' => 'integer',
                // Content validations
                'contentTitle' => 'nullable|string',
                'contentImage' => 'nullable|image|max:10240',
            ]);
        } elseif ($step === 2) {
            $this->validate([
                'restrictionMode' => 'required|in:public,restricted',
                // Visibility
                'selectedVisibilityTiers' => ['exclude_unless:restrictionMode,restricted', 'required', 'array', 'min:1'],
                
                // Restriction Type (Logic driver)
                'restrictionType' => ['exclude_unless:restrictionMode,restricted', 'required', 'in:hierarchical,random,private'],
                
                // Hierarchical
                'restrictedMinTierId' => ['exclude_unless:restrictionType,hierarchical', 'required', 'exists:membership_tiers,id'],
                
                // Private
                'restrictedPrivateTierId' => ['exclude_unless:restrictionType,private', 'required', 'exists:membership_tiers,id'],

                // Blur
                'blurEnabled' => 'boolean',
                // If blur enabled, we need clear view settings?
                // For simplicity, we can say: 
                // - Hierarchical: Min Tier => Clear View from that tier up.
                // - Private: That tier gets clear view. 
                // - Random: Selected tiers get clear view? Or require separate selection?
                // Archive defaults to:
                // Hierarchical -> Min Tier sets clear view base?
                // Let's stick to simple logic: If blur, logic determines clear view based on restriction type.
            ]);
        }
    }

    public function nextStep()
    {
        $this->validateStep($this->createStep);
        if ($this->createStep < 2) {
            $this->createStep++;
        }
    }

    public function prevStep()
    {
        if ($this->createStep > 1) {
            $this->createStep--;
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
        $this->type = $block->type;
        $this->isActive = $block->is_active;
        $this->sortOrder = $block->sort_order;
        
        // Content
        $content = $block->content ?? [];
        $this->contentTitle = $content['title'] ?? '';
        $this->contentSubtitle = $content['subtitle'] ?? '';
        $this->contentBody = $content['body'] ?? '';
        $this->contentCtaText = $content['cta_text'] ?? '';
        $this->contentCtaUrl = $content['cta_url'] ?? '';
        $this->existingContentImage = $content['image_url'] ?? null;
        
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
            'cta_text' => $this->contentCtaText,
            'cta_url' => $this->contentCtaUrl,
            'image_url' => $imageUrl
        ];

        DB::beginTransaction();
        try {
            $data = [
                'title' => $this->title,
                'type' => $this->type,
                'content' => $contentPayload,
                'is_active' => $this->isActive,
                'sort_order' => $this->sortOrder,
                'restriction_mode' => $this->restrictionMode,
                'restriction_type' => $this->restrictionMode === 'public' ? 'hierarchical' : $this->restrictionType, // default
                'restricted_min_tier_id' => $this->restrictionMode === 'public' ? null : $this->restrictedMinTierId,
                'restricted_private_tier_id' => $this->restrictionMode === 'public' ? null : $this->restrictedPrivateTierId,
                'blur_enabled' => $this->blurEnabled,
                'blur_strategy' => $this->restrictionMode === 'public' ? 'hierarchical' : ($this->restrictionType ?? 'hierarchical'),
            ];

            // Derive Clear View Min Tier ID from Restricted Min Tier if hierarchical
            // (Simplification: Access Tier also defines Clear View Tier if Blur is ON)
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
            
            // Sync Clear View Tiers (For Random/Private/Allowlist logic)
            // If Blur & Hierarchical, we handled min_clear_view_tier_id above. Column logic handles it.
            // If Blur & Private, we might want to sync pivot? Or just rely on private ID.
            // Archive Logic: "Clear View" pivot is distinct.
            // Simplified: If Private, sync that one tier to clear view pivot too.
            $clearTiers = [];
            if ($this->blurEnabled) {
                 if ($data['restriction_type'] === 'private' && $this->restrictedPrivateTierId) {
                     $clearTiers = [$this->restrictedPrivateTierId];
                 } elseif ($data['restriction_type'] === 'random') { // "random" here mapping to Allowlist logic in UI
                     // If random, assume all visible are clear? Or need distinct selection?
                     // Let's assume for Blocks: Visible = Clear unless we add 3rd step.
                     // Wait, if Visible = Clear, then Blur is useless?
                     // Blur implies: Visible (list) but Blurred (detail).
                     // If Random: Select Tiers that can SEE (Visibility).
                     // Which of those can See CLEARLY?
                     // Archive UI has separate selection.
                     // I'll simplifiy: If Random & Blur, NO ONE sees clearly (teased for everyone in that list)? 
                     // Or ALL see clearly?
                     // Let's fallback to: Random + Blur = Visual Teaser for everyone in that visibility set?
                     // AccessResolver expects: if blur_enabled AND NOT in clearViewTiers -> Blurred.
                     // So if I want them to see clearly, I MUST put them in clearViewTiers.
                     // If I put NO ONE in clearViewTiers, everyone sees blur.
                     // I will sync ALL selected visibility tiers to Clear View for now, effectively disabling blur for Random 
                     // UNLESS I add a UI for it.
                     // Let's just disable blur for Random in UI validation or force it off.
                     // OR, assume Random = "Allowed to View", and if Blur is ON, they are ALL blurred? 
                     // That seems a valid use case: "Here is a card, you can see it exists, but content is blurred."
                     // So clearTiers = [].
                 } else if ($data['restriction_type'] === 'hierarchical') {
                      // Handled by min_clear_view_tier_id
                 }
            }
            $block->clearViewTiers()->sync($clearTiers);

            DB::commit();
            $this->closeModal();
            $this->dispatch('refresh-blocks'); 
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('general', $e->getMessage());
        }
    }

    public function delete($id)
    {
        $this->blockId = $id;
        $this->confirmingDelete = true;
        // dispatch open modal?
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
        $this->type = 'card';
        $this->contentTitle = '';
        $this->contentSubtitle = '';
        $this->contentBody = '';
        $this->contentCtaText = '';
        $this->contentCtaUrl = '';
        $this->contentImage = null;
        $this->existingContentImage = null;
        $this->restrictionMode = 'public';
        $this->restrictionType = null;
        $this->restrictedMinTierId = null;
        $this->restrictedPrivateTierId = null;
        $this->selectedVisibilityTiers = [];
        $this->blurEnabled = false;
        $this->createStep = 1;
    }
}
