<?php

namespace App\Livewire\Admin\Membership\Tiers;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\MembershipTier;
use App\Models\Privilege;
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
        // Guard rail: check relationships
        
        $hasApps = \App\Domain\Membership\MembershipApplication::where('selected_tier_id', $id)->orWhere('recommended_tier_id', $id)->exists();
        $hasMemberships = \App\Models\Membership::where('membership_tier_id', $id)->exists();

        if ($hasApps || $hasMemberships) {
            session()->flash('error', 'Cannot delete tier: It is actively used by applications or members. Deactivate it instead.');
            return;
        }

        $this->tierToDeleteId = $id;
        $this->confirmingDeletion = true;
        $this->dispatch('open-delete-modal');
    }

    public function delete()
    {
        $this->checkSuperAdmin();
        if ($this->tierToDeleteId) {
            MembershipTier::find($this->tierToDeleteId)?->delete();
            session()->flash('success', 'Tier deleted successfully.');
        }
        $this->confirmingDeletion = false;
        $this->tierToDeleteId = null;
        $this->dispatch('close-modals');
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
