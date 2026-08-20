<?php

namespace App\Livewire\Admin\AddressSettings\Countries;

use App\Models\DeliveryCountry;
use App\Models\ShippingAddressGroup;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    
    // Modal state
    public $countryId;
    public $name;
    public $code;
    public $shipping_address_group_id;
    public $delivery_type = 'courier';
    public $courier_name = 'shiprocket';
    public $is_active = true;
    public $isEditMode = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'code' => 'nullable|string|max:5',
        'shipping_address_group_id' => 'required|exists:shipping_address_groups,id',
        'delivery_type' => 'required|in:courier,negotiated',
        'courier_name' => 'nullable|string|max:100',
        'is_active' => 'boolean',
    ];

    public function openCreateModal()
    {
        $this->reset(['countryId', 'name', 'code', 'shipping_address_group_id']);
        $this->delivery_type = 'courier';
        $this->courier_name = 'shiprocket';
        $this->is_active = true;
        $this->isEditMode = false;
        $this->dispatch('show-modal', 'countryModal');
        $this->resetErrorBag();
    }

    public function editCountry($id)
    {
        $country = DeliveryCountry::findOrFail($id);
        $this->countryId = $country->id;
        $this->name = $country->name;
        $this->code = $country->code;
        $this->shipping_address_group_id = $country->shipping_address_group_id;
        $this->delivery_type = $country->delivery_type;
        $this->courier_name = $country->courier_name;
        $this->is_active = $country->is_active;

        $this->isEditMode = true;
        $this->dispatch('show-modal', 'countryModal');
        $this->resetErrorBag();
    }

    public function save()
    {
        $this->validate();

        if ($this->delivery_type === 'negotiated') {
            $this->courier_name = null;
        }

        if ($this->isEditMode) {
            $country = DeliveryCountry::findOrFail($this->countryId);
            $country->update([
                'name' => $this->name,
                'code' => $this->code,
                'shipping_address_group_id' => $this->shipping_address_group_id,
                'delivery_type' => $this->delivery_type,
                'courier_name' => $this->courier_name,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('notify', ['message' => 'Country updated successfully!', 'type' => 'success']);
        } else {
            DeliveryCountry::create([
                'name' => $this->name,
                'code' => $this->code,
                'shipping_address_group_id' => $this->shipping_address_group_id,
                'delivery_type' => $this->delivery_type,
                'courier_name' => $this->courier_name,
                'is_active' => $this->is_active,
            ]);
            $this->dispatch('notify', ['message' => 'Country added successfully!', 'type' => 'success']);
        }

        $this->dispatch('hide-modal', 'countryModal');
    }

    public function deleteCountry($id)
    {
        $country = DeliveryCountry::findOrFail($id);
        
        if (\App\Models\Shop\UserAddress::where('delivery_country_id', $id)->exists()) {
            $this->dispatch('notify', ['message' => 'Cannot delete country as it is used in user addresses.', 'type' => 'error']);
            return;
        }

        $country->delete();
        $this->dispatch('notify', ['message' => 'Country deleted.', 'type' => 'success']);
    }

    public function render()
    {
        $countries = DeliveryCountry::with('addressGroup')
            ->when($this->search, function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(15);

        $groups = ShippingAddressGroup::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.address-settings.countries.index', [
            'countries' => $countries,
            'groups' => $groups,
        ])->layout('layouts.admin');
    }
}
