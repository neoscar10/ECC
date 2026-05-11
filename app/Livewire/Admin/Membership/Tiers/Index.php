<?php

namespace App\Livewire\Admin\Membership\Tiers;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\MembershipTier;
use App\Models\Privilege;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\Auctions\AuctionLot;
use App\Models\Archive\ArchiveProduct;
use App\Models\Archive\ArchiveCategory;
use App\Models\Cms\CmsBlock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    
    // Modal states
    public $showInitModal = false;
    public $isEditMode = false;
    public $confirmingDeletion = false;
    public $tierToDeleteId = null;

    // Form fields
    public $tierId;
    public $name;
    public $code;
    public $price;
    public $duration_days = 365;
    public $durationValue = 1;
    public $durationUnit = 'years';
    public $is_active = true;
    public $requires_approval = true;
    public $currency = 'INR';
    public $has_early_access = false;
    public $has_vault_access = false;
    public $is_auto_bidding_enabled = false;
    public $sort_order = 0;
    public $upgrade_from_id = null;
    public $description = null;
    public $features = []; // Dynamic list: [['id'=>?, 'title'=>string, 'sort_order'=>int]]
    
    // Migration Properties
    public $showMigrationModal = false;
    public $migrationTargetTierId = null;
    public $membersOnTierCount = 0;
    public $migrationMembers = []; // List of member details
    public $selectedMembershipIds = [];
    public $selectAll = false;
    
    // Migration Wizard
    public $migrationStep = 1;
    public $restrictionTargetTierId = null;
    public $brokenRestrictions = [
        'auctions' => 0,
        'archive_products' => 0,
        'archive_categories' => 0,
        'cms_blocks' => 0,
        'total' => 0
    ];
    
    // Privileges
    public $selectedPrivileges = [];

    protected $paginationTheme = 'bootstrap';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255', Rule::unique('membership_tiers')->ignore($this->tierId)],
            'price' => 'required|numeric|min:0',
            'durationValue' => 'required|integer|min:1|max:36500',
            'durationUnit' => 'required|in:days,weeks,months,years',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'has_early_access' => 'boolean',
            'has_vault_access' => 'boolean',
            'is_auto_bidding_enabled' => 'boolean',
            'requires_approval' => 'boolean',
            'upgrade_from_id' => ['nullable', 'exists:membership_tiers,id', function($attribute, $value, $fail) {
                if ($this->tierId && $value == $this->tierId) {
                    $fail('A tier cannot be an upgrade from itself.');
                }
            }],
            'selectedPrivileges' => 'array',
            'description' => 'nullable|string|max:5000',
            'features' => 'array|max:50',
            'features.*.title' => 'required_with:features.*.id|string|max:255', // Require title if row exists and is being saved (filtered later)
            'features.*.sort_order' => 'integer|min:0',
        ];
    }

    public function mount()
    {
        // Initial setup if needed
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->checkSuperAdmin();
        $this->reset(['tierId', 'name', 'code', 'price', 'duration_days', 'durationValue', 'durationUnit', 'is_active', 'has_early_access', 'has_vault_access', 'is_auto_bidding_enabled', 'requires_approval', 'currency', 'sort_order', 'upgrade_from_id', 'selectedPrivileges', 'description', 'features']);
        $this->durationValue = 1;
        $this->durationUnit = 'years';
        $this->requires_approval = true; // Default to true
        
        // Auto-calculate sort order: max + 1
        $maxSortOrder = MembershipTier::max('sort_order');
        $this->sort_order = ($maxSortOrder !== null) ? $maxSortOrder + 1 : 1;
        $this->features = [$this->blankFeatureRow(1)];

        $this->isEditMode = false;
        $this->dispatch('open-init-modal');
    }

    public function edit($id)
    {
        $this->checkSuperAdmin();
        $this->isEditMode = true;
        $tier = MembershipTier::with('privileges')->findOrFail($id);
        
        $this->tierId = $tier->id;
        $this->name = $tier->name;
        $this->code = $tier->code;
        $this->price = $tier->price;
        $this->duration_days = $tier->duration_days;
        
        // Convert to Unit/Value for Edit Mode
        if ($this->duration_days % 365 === 0) {
            $this->durationValue = $this->duration_days / 365;
            $this->durationUnit = 'years';
        } elseif ($this->duration_days % 30 === 0) {
            $this->durationValue = $this->duration_days / 30;
            $this->durationUnit = 'months';
        } elseif ($this->duration_days % 7 === 0) {
            $this->durationValue = $this->duration_days / 7;
            $this->durationUnit = 'weeks';
        } else {
            $this->durationValue = $this->duration_days;
            $this->durationUnit = 'days';
        }
        $this->is_active = $tier->is_active;
        $this->has_early_access = $tier->has_early_access;
        $this->has_vault_access = $tier->has_vault_access;
        $this->is_auto_bidding_enabled = $tier->is_auto_bidding_enabled;
        $this->requires_approval = $tier->requires_approval;
        $this->currency = $tier->currency;
        $this->sort_order = $tier->sort_order;
        $this->upgrade_from_id = $tier->upgrade_from_id;
        
        $this->description = $tier->description;
        
        // Load privileges
        $this->selectedPrivileges = $tier->privileges->pluck('id')->map(fn($id) => (string)$id)->toArray();

        // Load features
        $this->features = DB::table('membership_tier_features')
            ->where('membership_tier_id', $id)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'title' => $f->feature,
                    'sort_order' => $f->sort_order,
                ];
            })->toArray();
        
        if (empty($this->features)) {
            $this->features = [$this->blankFeatureRow(1)];
        }

        $this->dispatch('open-init-modal');
    }

    public function store()
    {
        $this->checkSuperAdmin();
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $tier = MembershipTier::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'duration_days' => $this->durationToDays(),
                'is_active' => $validated['is_active'],
                'has_early_access' => $validated['has_early_access'],
                'has_vault_access' => $validated['has_vault_access'],
                'is_auto_bidding_enabled' => $validated['is_auto_bidding_enabled'],
                'currency' => $this->currency,
                'sort_order' => $validated['sort_order'],
                'level' => $validated['sort_order'], // Sync level with sort_order
                'requires_approval' => $validated['requires_approval'],
                'upgrade_from_id' => $validated['upgrade_from_id'] ?: null,
            ]);
            
            $tier->privileges()->sync($this->selectedPrivileges);

            // Save features
            foreach ($this->features as $feature) {
                if (!empty(trim($feature['title']))) {
                    DB::table('membership_tier_features')->insert([
                        'membership_tier_id' => $tier->id,
                        'feature' => trim($feature['title']),
                        'sort_order' => $feature['sort_order'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        session()->flash('success', 'Membership Tier created successfully.');
        $this->dispatch('close-modals');
    }

    public function update()
    {
        $this->checkSuperAdmin();
        $validated = $this->validate();

        DB::transaction(function () use ($validated) {
            $tier = MembershipTier::findOrFail($this->tierId);
            
            $tier->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'duration_days' => $this->durationToDays(),
                'is_active' => $validated['is_active'],
                'has_early_access' => $validated['has_early_access'],
                'has_vault_access' => $validated['has_vault_access'],
                'is_auto_bidding_enabled' => $validated['is_auto_bidding_enabled'],
                'sort_order' => $validated['sort_order'],
                'level' => $validated['sort_order'], // Sync level with sort_order
                'requires_approval' => $validated['requires_approval'],
                'upgrade_from_id' => $validated['upgrade_from_id'] ?: null,
            ]);

            $tier->privileges()->sync($this->selectedPrivileges);

            // Sync features manually
            $existingIds = collect($this->features)->pluck('id')->filter()->toArray();
            
            // Delete removed
            DB::table('membership_tier_features')
                ->where('membership_tier_id', $this->tierId)
                ->whereNotIn('id', $existingIds)
                ->delete();

            // Upsert
            foreach ($this->features as $feature) {
                if (!empty(trim($feature['title']))) {
                    if (!empty($feature['id'])) {
                        DB::table('membership_tier_features')
                            ->where('id', $feature['id'])
                            ->update([
                                'feature' => trim($feature['title']),
                                'sort_order' => $feature['sort_order'] ?? 0,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('membership_tier_features')->insert([
                            'membership_tier_id' => $this->tierId,
                            'feature' => trim($feature['title']),
                            'sort_order' => $feature['sort_order'] ?? 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        session()->flash('success', 'Membership Tier updated successfully.');
        $this->dispatch('close-modals');
    }

    public function addFeatureRow()
    {
        $this->features[] = $this->blankFeatureRow(count($this->features) + 1);
    }

    public function removeFeatureRow($index)
    {
        unset($this->features[$index]);
        $this->features = array_values($this->features); // Re-index
    }

    private function blankFeatureRow($sort)
    {
        return [
            'id' => null,
            'title' => '',
            'sort_order' => $sort,
        ];
    }

    public function confirmDelete($id)
    {
        $this->checkSuperAdmin();
        
        $this->tierToDeleteId = $id;
        $this->migrationStep = 1;
        $this->membersOnTierCount = Membership::where('membership_tier_id', $id)->where('status', 'active')->distinct('user_id')->count();
        $hasApps = MembershipApplication::where('selected_tier_id', $id)->orWhere('recommended_tier_id', $id)->exists();

        // Check for product restrictions
        $this->checkRestrictionDependencies($id);

        if ($this->membersOnTierCount > 0 || $hasApps || $this->brokenRestrictions['total'] > 0) {
            $this->migrationTargetTierId = null;
            $this->restrictionTargetTierId = null;
            // Load unique members based on user_id to avoid visual duplicates
            $this->migrationMembers = Membership::with('user')
                ->where('membership_tier_id', $id)
                ->where('status', 'active')
                ->get()
                ->unique('user_id')
                ->map(function($m) {
                    return [
                        'id' => $m->id,
                        'user_id' => $m->user_id,
                        'name' => $m->user->name,
                        'email' => $m->user->email,
                    ];
                })
                ->toArray();
            
            $this->selectedMembershipIds = collect($this->migrationMembers)->pluck('id')->map(fn($id) => (string)$id)->toArray();
            $this->selectAll = true;
                
            $this->showMigrationModal = true;
            $this->dispatch('open-migration-modal');
            return;
        }

        $this->confirmingDeletion = true;
        $this->dispatch('open-delete-modal');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedMembershipIds = collect($this->migrationMembers)->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedMembershipIds = [];
        }
    }

    public function executeMigration()
    {
        $this->checkSuperAdmin();


        $rules = [
            'restrictionTargetTierId' => 'nullable|exists:membership_tiers,id|different:tierToDeleteId'
        ];

        if (!empty($this->selectedMembershipIds)) {
            $rules['migrationTargetTierId'] = 'required|exists:membership_tiers,id|different:tierToDeleteId';
        }

        $this->validate($rules, [
            'migrationTargetTierId.required' => 'Please select a destination tier for members.',
            'migrationTargetTierId.different' => 'Target tier must be different from the deleted tier.',
            'restrictionTargetTierId.different' => 'Target tier must be different from the deleted tier.'
        ]);

        DB::transaction(function() {
            // 1. Migrate selected memberships (Existing logic)
            if (!empty($this->selectedMembershipIds)) {
                $userIds = Membership::whereIn('id', $this->selectedMembershipIds)->pluck('user_id')->unique();
                Membership::whereIn('user_id', $userIds)
                    ->where('membership_tier_id', $this->tierToDeleteId)
                    ->update(['membership_tier_id' => $this->migrationTargetTierId]);
                    
                MembershipApplication::whereIn('user_id', $userIds)
                    ->where('selected_tier_id', $this->tierToDeleteId)
                    ->update(['selected_tier_id' => $this->migrationTargetTierId]);
                    
                MembershipApplication::whereIn('user_id', $userIds)
                    ->where('recommended_tier_id', $this->tierToDeleteId)
                    ->update(['recommended_tier_id' => $this->migrationTargetTierId]);
            }

            // 2. Migrate Product Restrictions (If target provided)
            if ($this->restrictionTargetTierId) {
                $this->migrateItemRestrictions($this->tierToDeleteId, $this->restrictionTargetTierId);
            }
                
            // Check if any members are left on this tier
            $remainingCount = Membership::where('membership_tier_id', $this->tierToDeleteId)->count();
            
            if ($remainingCount === 0) {
                // 3. Clear upgrade recommendations pointing to this tier ONLY if no one is left
                MembershipTier::where('upgrade_from_id', $this->tierToDeleteId)
                    ->update(['upgrade_from_id' => null]);
            }
        });

        // Refresh count
        $this->membersOnTierCount = Membership::where('membership_tier_id', $this->tierToDeleteId)->where('status', 'active')->distinct('user_id')->count();
        $this->checkRestrictionDependencies($this->tierToDeleteId);
        
        if ($this->membersOnTierCount === 0 && $this->brokenRestrictions['total'] === 0) {
            $this->showMigrationModal = false;
            // Now trigger delete confirmation since it's clean
            $this->confirmingDeletion = true;
            $this->dispatch('close-modals');
            $this->dispatch('open-delete-modal');
            session()->flash('success', 'All members migrated successfully. You can now delete the tier.');
        } else {
            // Update the list for remaining members
            $this->migrationMembers = Membership::with('user')
                ->where('membership_tier_id', $this->tierToDeleteId)
                ->where('status', 'active')
                ->get()
                ->unique('user_id')
                ->map(function($m) {
                    return [
                        'id' => $m->id,
                        'user_id' => $m->user_id,
                        'name' => $m->user->name,
                        'email' => $m->user->email,
                    ];
                })
                ->toArray();
            $this->selectedMembershipIds = [];
            $this->selectAll = false;
            $this->showMigrationModal = false;
            $this->dispatch('close-modals');
            session()->flash('success', 'Migration executed successfully. Note: There are still remaining dependencies on this tier.');
        }
    }

    // Resolution Properties
    public $showResolutionModal = false;
    public $orphanedItems = [];
    public $resolutionTargetTierId = null;

    public function getOrphanedRestrictionsCount()
    {
        $activeTierIds = MembershipTier::pluck('id')->toArray();
        
        $orphanedAuctions = AuctionLot::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)->count();
            
        $orphanedArchive = ArchiveProduct::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)->count();
            
        $orphanedCms = CmsBlock::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)->count();

        // Plus pivot table checks if we want to be very thorough
        // But hierarchical min tier is the primary "breaker"
        
        return $orphanedAuctions + $orphanedArchive + $orphanedCms;
    }

    public function openResolutionModal()
    {
        $activeTierIds = MembershipTier::pluck('id')->toArray();
        
        $this->orphanedItems = [];

        // Fetch Auctions
        AuctionLot::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)
            ->get()->each(function($item) {
                $this->orphanedItems[] = ['type' => 'Auction Lot', 'name' => $item->title, 'id' => $item->id, 'module' => 'auctions'];
            });

        // Fetch Archive
        ArchiveProduct::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)
            ->get()->each(function($item) {
                $this->orphanedItems[] = ['type' => 'Archive Product', 'name' => $item->name, 'id' => $item->id, 'module' => 'archive'];
            });

        // Fetch CMS
        CmsBlock::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)
            ->get()->each(function($item) {
                $this->orphanedItems[] = ['type' => 'CMS Block', 'name' => $item->title, 'id' => $item->id, 'module' => 'cms'];
            });

        $this->showResolutionModal = true;
        $this->dispatch('open-resolution-modal');
    }

    public function resolveAllRestrictions()
    {
        $this->checkSuperAdmin();
        
        $this->validate([
            'resolutionTargetTierId' => 'required|exists:membership_tiers,id'
        ]);

        $activeTierIds = MembershipTier::pluck('id')->toArray();

        // 1. Resolve Auctions
        AuctionLot::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)
            ->update(['restricted_min_tier_id' => $this->resolutionTargetTierId]);

        // 2. Resolve Archive
        ArchiveProduct::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)
            ->update(['restricted_min_tier_id' => $this->resolutionTargetTierId]);

        // 3. Resolve CMS
        CmsBlock::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)
            ->update(['restricted_min_tier_id' => $this->resolutionTargetTierId]);

        session()->flash('success', 'All orphaned restrictions have been re-assigned.');
        $this->showResolutionModal = false;
        $this->dispatch('close-modals');
    }

    public function nextStep()
    {
        if ($this->migrationStep === 1) {
            if (!empty($this->selectedMembershipIds)) {
                $this->validate([
                    'migrationTargetTierId' => 'required|exists:membership_tiers,id|different:tierToDeleteId'
                ], [
                    'migrationTargetTierId.required' => 'Please select a destination tier for the selected members.',
                    'migrationTargetTierId.different' => 'Target tier must be different from the deleted tier.'
                ]);
            }
            $this->migrationStep = 2;
        } elseif ($this->migrationStep === 2) {
            $this->validate([
                'restrictionTargetTierId' => 'nullable|exists:membership_tiers,id|different:tierToDeleteId'
            ], [
                'restrictionTargetTierId.different' => 'Target tier must be different from the deleted tier.'
            ]);
            $this->migrationStep = 3;
        }
    }

    public function previousStep()
    {
        if ($this->migrationStep > 1) {
            $this->migrationStep--;
        }
    }

    public $affectedItemsFilter = 'all';

    public function getAffectedItemsList()
    {
        $id = $this->tierToDeleteId;
        $items = [];
        
        // 1. Hierarchical/Private Dependencies (Always count as they are anchors)
        $auctions = AuctionLot::where(function($q) use ($id) {
            $q->where('restricted_min_tier_id', $id)
              ->orWhere('restricted_private_tier_id', $id)
              ->orWhere('min_clear_view_tier_id', $id)
              ->orWhere('clear_private_tier_id', $id);
        })->limit(20)->get();
        foreach ($auctions as $i) {
            $items[] = ['id' => $i->id, 'title' => $i->title, 'source' => 'auctions', 'source_label' => 'Auction Lot', 'type' => 'Hierarchical/Private'];
        }

        $archives = ArchiveProduct::where(function($q) use ($id) {
            $q->where('restricted_min_tier_id', $id)
              ->orWhere('restricted_private_tier_id', $id);
        })->limit(20)->get();
        foreach ($archives as $i) {
            $items[] = ['id' => $i->id, 'title' => $i->title, 'source' => 'archive_products', 'source_label' => 'Archive Product', 'type' => 'Hierarchical/Private'];
        }

        $cms = CmsBlock::where(function($q) use ($id) {
            $q->where('restricted_min_tier_id', $id)
              ->orWhere('restricted_private_tier_id', $id)
              ->orWhere('min_clear_view_tier_id', $id);
        })->limit(20)->get();
        foreach ($cms as $i) {
            $items[] = ['id' => $i->id, 'title' => $i->title, 'source' => 'cms_blocks', 'source_label' => 'CMS Block', 'type' => 'Hierarchical/Private'];
        }

        // 2. Exclusive Pivot Dependencies
        $aucIds = DB::table('auction_lot_visibility_tier')
            ->select('auction_lot_id')
            ->groupBy('auction_lot_id')
            ->havingRaw('COUNT(*) = 1 AND MAX(membership_tier_id) = ?', [$id])
            ->pluck('auction_lot_id');
        $aucExclusive = AuctionLot::whereIn('id', $aucIds)->limit(20)->get();
        foreach ($aucExclusive as $i) {
            $items[] = ['id' => $i->id, 'title' => $i->title, 'source' => 'auctions', 'source_label' => 'Auction Lot', 'type' => 'Exclusive Access'];
        }

        $prodIds = DB::table('archive_product_visibility_tier')
            ->select('archive_product_id')
            ->groupBy('archive_product_id')
            ->havingRaw('COUNT(*) = 1 AND MAX(membership_tier_id) = ?', [$id])
            ->pluck('archive_product_id');
        $prodExclusive = ArchiveProduct::whereIn('id', $prodIds)->limit(20)->get();
        foreach ($prodExclusive as $i) {
            $items[] = ['id' => $i->id, 'title' => $i->title, 'source' => 'archive_products', 'source_label' => 'Archive Product', 'type' => 'Exclusive Access'];
        }

        $catIds = DB::table('archive_category_tier')
            ->select('archive_category_id')
            ->groupBy('archive_category_id')
            ->havingRaw('COUNT(*) = 1 AND MAX(membership_tier_id) = ?', [$id])
            ->pluck('archive_category_id');
        $catExclusive = ArchiveCategory::whereIn('id', $catIds)->limit(20)->get();
        foreach ($catExclusive as $i) {
            $items[] = ['id' => $i->id, 'title' => $i->name, 'source' => 'archive_categories', 'source_label' => 'Archive Category', 'type' => 'Exclusive Access'];
        }

        // Remove duplicates if an item is both hierarchical and exclusive (rare but possible)
        $unique = collect($items)->unique(function ($item) {
            return $item['source'] . $item['id'];
        });

        if ($this->affectedItemsFilter !== 'all') {
            $unique = $unique->where('source', $this->affectedItemsFilter);
        }

        return $unique->values()->all();
    }

    private function checkRestrictionDependencies($tierId)
    {
        $count = 0;

        // Hierarchical
        $count += AuctionLot::where(function($q) use ($tierId) {
            $q->where('restricted_min_tier_id', $tierId)
              ->orWhere('restricted_private_tier_id', $tierId)
              ->orWhere('min_clear_view_tier_id', $tierId)
              ->orWhere('clear_private_tier_id', $tierId);
        })->count();

        $count += ArchiveProduct::where(function($q) use ($tierId) {
            $q->where('restricted_min_tier_id', $tierId)
              ->orWhere('restricted_private_tier_id', $tierId);
        })->count();

        $count += CmsBlock::where(function($q) use ($tierId) {
            $q->where('restricted_min_tier_id', $tierId)
              ->orWhere('restricted_private_tier_id', $tierId)
              ->orWhere('min_clear_view_tier_id', $tierId);
        })->count();

        // Pivot Exclusive Access
        $count += DB::table('auction_lot_visibility_tier')
            ->select('auction_lot_id')
            ->whereIn('auction_lot_id', function($q) { $q->select('id')->from('auction_lots')->whereNull('deleted_at'); })
            ->groupBy('auction_lot_id')
            ->havingRaw('COUNT(*) = 1 AND MAX(membership_tier_id) = ?', [$tierId])
            ->get()->count();

        $count += DB::table('archive_product_visibility_tier')
            ->select('archive_product_id')
            ->whereIn('archive_product_id', function($q) { $q->select('id')->from('archive_products')->whereNull('deleted_at'); })
            ->groupBy('archive_product_id')
            ->havingRaw('COUNT(*) = 1 AND MAX(membership_tier_id) = ?', [$tierId])
            ->get()->count();

        $count += DB::table('archive_category_tier')
            ->select('archive_category_id')
            ->whereIn('archive_category_id', function($q) { $q->select('id')->from('archive_categories')->whereNull('deleted_at'); })
            ->groupBy('archive_category_id')
            ->havingRaw('COUNT(*) = 1 AND MAX(membership_tier_id) = ?', [$tierId])
            ->get()->count();

        $this->brokenRestrictions['total'] = $count;
    }

    private function safeMigratePivot($table, $itemColumn, $oldTierId, $newTierId)
    {
        // 1. Find items that ALREADY have the new tier assigned
        $conflictingIds = DB::table($table)
            ->where('membership_tier_id', $newTierId)
            ->pluck($itemColumn);

        // 2. Delete the old tier row from those items to avoid duplicate unique keys
        if ($conflictingIds->isNotEmpty()) {
            DB::table($table)
                ->where('membership_tier_id', $oldTierId)
                ->whereIn($itemColumn, $conflictingIds)
                ->delete();
        }

        // 3. Safely update the remaining old tier rows to the new tier
        DB::table($table)
            ->where('membership_tier_id', $oldTierId)
            ->update(['membership_tier_id' => $newTierId]);
    }

    private function migrateItemRestrictions($oldTierId, $newTierId)
    {
        // 1. Hierarchical & Private & Clear View
        AuctionLot::where('restricted_min_tier_id', $oldTierId)->update(['restricted_min_tier_id' => $newTierId]);
        AuctionLot::where('restricted_private_tier_id', $oldTierId)->update(['restricted_private_tier_id' => $newTierId]);
        AuctionLot::where('min_clear_view_tier_id', $oldTierId)->update(['min_clear_view_tier_id' => $newTierId]);
        AuctionLot::where('clear_private_tier_id', $oldTierId)->update(['clear_private_tier_id' => $newTierId]);
        
        ArchiveProduct::where('restricted_min_tier_id', $oldTierId)->update(['restricted_min_tier_id' => $newTierId]);
        ArchiveProduct::where('restricted_private_tier_id', $oldTierId)->update(['restricted_private_tier_id' => $newTierId]);
        
        CmsBlock::where('restricted_min_tier_id', $oldTierId)->update(['restricted_min_tier_id' => $newTierId]);
        CmsBlock::where('restricted_private_tier_id', $oldTierId)->update(['restricted_private_tier_id' => $newTierId]);
        CmsBlock::where('min_clear_view_tier_id', $oldTierId)->update(['min_clear_view_tier_id' => $newTierId]);

        // 2. Pivot tables (Safe Migration)
        $this->safeMigratePivot('auction_lot_visibility_tier', 'auction_lot_id', $oldTierId, $newTierId);
        $this->safeMigratePivot('archive_product_visibility_tier', 'archive_product_id', $oldTierId, $newTierId);
        $this->safeMigratePivot('archive_product_clear_tier', 'archive_product_id', $oldTierId, $newTierId);
        $this->safeMigratePivot('archive_category_tier', 'archive_category_id', $oldTierId, $newTierId);
        $this->safeMigratePivot('cms_block_visibility_tier', 'cms_block_id', $oldTierId, $newTierId);
        $this->safeMigratePivot('cms_block_clear_tier', 'cms_block_id', $oldTierId, $newTierId);
    }

    protected function durationToDays(): int
    {
        $value = (int) $this->durationValue;
        return match ($this->durationUnit) {
            'weeks' => $value * 7,
            'months' => $value * 30, // 30-day month as per requirements
            'years' => $value * 365, // 365-day year as per requirements
            default => $value, // 'days' or fallback
        };
    }
    
    private function checkSuperAdmin()
    {
        if (!Auth::user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $query = MembershipTier::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
        }

        $tiers = $query->orderBy('sort_order')->orderBy('price')->paginate(10);
        $allPrivileges = Privilege::where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.admin.membership.tiers.index', [
            'tiers' => $tiers,
            'allPrivileges' => $allPrivileges
        ]);
    }
}
