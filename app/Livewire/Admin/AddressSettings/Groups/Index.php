<?php

namespace App\Livewire\Admin\AddressSettings\Groups;

use App\Models\ShippingAddressGroup;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    
    // Modal state
    public $groupId;
    public $name;
    public $is_active = true;
    public $fields = [];
    public $isEditMode = false;

    public $availableFields = [
        'full_name' => 'Full Name',
        'phone' => 'Phone Number',
        'line1' => 'Address Line 1',
        'line2' => 'Address Line 2',
        'city' => 'City',
        'state' => 'State/Province',
        'postal_code' => 'Postal Code / Pincode',
    ];

    protected $rules = [
        'name' => 'required|string|max:255',
        'is_active' => 'boolean',
        'fields' => 'array',
    ];

    public function mount()
    {
        $this->initializeFields();
    }

    public function initializeFields()
    {
        $this->fields = [];
        foreach ($this->availableFields as $key => $label) {
            $this->fields[$key] = [
                'is_collected' => true,
                'is_required' => true,
            ];
        }
    }

    public function openCreateModal()
    {
        $this->reset(['groupId', 'name']);
        $this->is_active = true;
        $this->isEditMode = false;
        $this->initializeFields();
        $this->dispatch('show-modal', 'addressGroupModal');
        $this->resetErrorBag();
    }

    public function editGroup($id)
    {
        $group = ShippingAddressGroup::findOrFail($id);
        $this->groupId = $group->id;
        $this->name = $group->name;
        $this->is_active = $group->is_active;
        
        $this->initializeFields();
        if (is_array($group->fields)) {
            foreach ($group->fields as $fieldConfig) {
                if (isset($fieldConfig['name']) && isset($this->fields[$fieldConfig['name']])) {
                    $this->fields[$fieldConfig['name']] = [
                        'is_collected' => true,
                        'is_required' => $fieldConfig['is_required'] ?? false,
                    ];
                }
            }
            // Mark fields not in DB as not collected
            foreach ($this->fields as $key => $config) {
                $found = false;
                foreach ($group->fields as $fieldConfig) {
                    if (($fieldConfig['name'] ?? '') === $key) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $this->fields[$key]['is_collected'] = false;
                    $this->fields[$key]['is_required'] = false;
                }
            }
        }

        $this->isEditMode = true;
        $this->dispatch('show-modal', 'addressGroupModal');
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        $formattedFields = [];
        foreach ($this->fields as $key => $config) {
            if ($config['is_collected']) {
                $formattedFields[] = [
                    'name' => $key,
                    'is_required' => $config['is_required'],
                ];
            }
        }

        if ($this->isEditMode) {
            $group = ShippingAddressGroup::findOrFail($this->groupId);
            $group->update([
                'name' => $this->name,
                'fields' => $formattedFields,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('notify', ['message' => 'Address group updated successfully!', 'type' => 'success']);
        } else {
            ShippingAddressGroup::create([
                'name' => $this->name,
                'fields' => $formattedFields,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('notify', ['message' => 'Address group created successfully!', 'type' => 'success']);
        }

        $this->dispatch('hide-modal', 'addressGroupModal');
    }

    public function deleteGroup($id)
    {
        $group = ShippingAddressGroup::findOrFail($id);
        
        // Prevent deletion if linked to countries
        if (\App\Models\DeliveryCountry::where('shipping_address_group_id', $id)->exists()) {
            $this->dispatch('notify', ['message' => 'Cannot delete group as it is assigned to one or more delivery countries.', 'type' => 'error']);
            return;
        }

        $group->delete();
        $this->dispatch('notify', ['message' => 'Address group deleted.', 'type' => 'success']);
    }

    public function render()
    {
        $groups = ShippingAddressGroup::query()
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.address-settings.groups.index', [
            'groups' => $groups
        ])->layout('layouts.admin');
    }
}
