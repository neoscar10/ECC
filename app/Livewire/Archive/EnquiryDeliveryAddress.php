<?php

namespace App\Livewire\Archive;

use App\Models\Archive\ArchiveProductEnquiry;
use App\Models\DeliveryCountry;
use Livewire\Component;

class EnquiryDeliveryAddress extends Component
{
    public ArchiveProductEnquiry $enquiry;

    public string $delivery_name = '';
    public string $delivery_phone = '';
    public string $delivery_line1 = '';
    public ?string $delivery_line2 = '';
    public string $delivery_city = '';
    public string $delivery_state = '';
    public string $delivery_postal_code = '';
    public string $delivery_country = 'India';

    public bool $submittedSuccessfully = false;

    protected function rules(): array
    {
        return [
            'delivery_name' => 'required|string|max:255',
            'delivery_phone' => 'required|string|max:20',
            'delivery_line1' => 'required|string|max:255',
            'delivery_line2' => 'nullable|string|max:255',
            'delivery_city' => 'required|string|max:100',
            'delivery_state' => 'required|string|max:100',
            'delivery_postal_code' => 'required|string|max:20',
            'delivery_country' => 'required|string|max:100',
        ];
    }

    public function mount(ArchiveProductEnquiry $enquiry)
    {
        $this->enquiry = $enquiry->load(['product.images', 'user']);

        if ($this->enquiry->delivery_address_submitted_at) {
            $this->submittedSuccessfully = true;
        }

        $this->delivery_name = $this->enquiry->delivery_name ?? $this->enquiry->contact_name ?? $this->enquiry->user?->name ?? '';
        $this->delivery_phone = $this->enquiry->delivery_phone ?? $this->enquiry->contact_phone ?? $this->enquiry->user?->phone ?? '';
        $this->delivery_line1 = $this->enquiry->delivery_line1 ?? '';
        $this->delivery_line2 = $this->enquiry->delivery_line2 ?? '';
        $this->delivery_city = $this->enquiry->delivery_city ?? '';
        $this->delivery_state = $this->enquiry->delivery_state ?? '';
        $this->delivery_postal_code = $this->enquiry->delivery_postal_code ?? '';
        $this->delivery_country = $this->enquiry->delivery_country ?? 'India';
    }

    public function saveAddress()
    {
        $validated = $this->validate();

        $this->enquiry->update(array_merge($validated, [
            'delivery_address_submitted_at' => now(),
        ]));

        $this->submittedSuccessfully = true;
        session()->flash('success', 'Delivery address submitted successfully!');
    }

    public function editAddress()
    {
        $this->submittedSuccessfully = false;
    }

    public function render()
    {
        $countries = DeliveryCountry::query()->where('is_active', true)->orderBy('name')->get();

        return view('livewire.archive.enquiry-delivery-address', [
            'countries' => $countries,
        ])->layout('layouts.guest');
    }
}
