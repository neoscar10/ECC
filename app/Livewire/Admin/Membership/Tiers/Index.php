<?php

namespace App\Livewire\Admin\Membership\Tiers;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\MembershipTier;
use App\Models\Privilege;
use Illuminate\Support\Facades\Auth;
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
    public $is_active = true;
    public $requires_approval = true;
    public $currency = 'INR';
    public $sort_order = 0;
    public $upgrade_from_id = null;
    
    // Privileges
    public $selectedPrivileges = [];

    protected $paginationTheme = 'bootstrap';

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:255', Rule::unique('membership_tiers')->ignore($this->tierId)],
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'upgrade_from_id' => ['nullable', 'exists:membership_tiers,id', function($attribute, $value, $fail) {
                if ($this->tierId && $value == $this->tierId) {
                    $fail('A tier cannot be an upgrade from itself.');
                }
            }],
            'selectedPrivileges' => 'array'
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
        $this->reset(['tierId', 'name', 'code', 'price', 'duration_days', 'is_active', 'requires_approval', 'currency', 'sort_order', 'upgrade_from_id', 'selectedPrivileges']);
        $this->requires_approval = true; // Default to true
        
        // Auto-calculate sort order: max + 1
        $maxSortOrder = MembershipTier::max('sort_order');
        $this->sort_order = ($maxSortOrder !== null) ? $maxSortOrder + 1 : 1;

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
        $this->is_active = $tier->is_active;
        $this->requires_approval = $tier->requires_approval;
        $this->currency = $tier->currency;
        $this->sort_order = $tier->sort_order;
        $this->upgrade_from_id = $tier->upgrade_from_id;
        
        // Load privileges
        $this->selectedPrivileges = $tier->privileges->pluck('id')->map(fn($id) => (string)$id)->toArray();

        $this->dispatch('open-init-modal');
    }

    public function store()
    {
        $this->checkSuperAdmin();
        $validated = $this->validate();

        $tier = MembershipTier::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'is_active' => $validated['is_active'],
            'currency' => $this->currency,
            'sort_order' => $validated['sort_order'],
            'level' => $validated['sort_order'], // Sync level with sort_order
            'requires_approval' => $validated['requires_approval'],
            'upgrade_from_id' => $validated['upgrade_from_id'] ?: null,
        ]);
        
        $tier->privileges()->sync($this->selectedPrivileges);

        session()->flash('success', 'Membership Tier created successfully.');
        $this->dispatch('close-modals');
    }

    public function update()
    {
        $this->checkSuperAdmin();
        $validated = $this->validate();

        $tier = MembershipTier::findOrFail($this->tierId);
        
        $tier->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'is_active' => $validated['is_active'],
            'sort_order' => $validated['sort_order'],
            'level' => $validated['sort_order'], // Sync level with sort_order
            'requires_approval' => $validated['requires_approval'],
            'upgrade_from_id' => $validated['upgrade_from_id'] ?: null,
        ]);

        $tier->privileges()->sync($this->selectedPrivileges);

        session()->flash('success', 'Membership Tier updated successfully.');
        $this->dispatch('close-modals');
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
