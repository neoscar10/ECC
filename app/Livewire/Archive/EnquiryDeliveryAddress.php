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
        $countries = $this->getAllWorldCountries();

        return view('livewire.archive.enquiry-delivery-address', [
            'countries' => $countries,
        ])->layout('layouts.guest');
    }

    private function getAllWorldCountries(): array
    {
        $dbCountries = DeliveryCountry::query()->pluck('name')->toArray();

        $allCountries = [
            "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan",
            "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi",
            "Cabo Verde", "Cambodia", "Cameroon", "Canada", "Central African Republic", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo", "Costa Rica", "Croatia", "Cuba", "Cyprus", "Czech Republic (Czechia)",
            "Denmark", "Djibouti", "Dominica", "Dominican Republic",
            "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini", "Ethiopia",
            "Fiji", "Finland", "France",
            "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana",
            "Haiti", "Honduras", "Hungary",
            "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Ivory Coast (Côte d'Ivoire)",
            "Jamaica", "Japan", "Jordan",
            "Kazakhstan", "Kenya", "Kiribati", "Kuwait", "Kyrgyzstan",
            "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg",
            "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar (Burma)",
            "Namibia", "Nauru", "Nepal", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "North Korea", "North Macedonia", "Norway",
            "Oman",
            "Pakistan", "Palau", "Palestine", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal", "Qatar",
            "Romania", "Russia", "Rwanda",
            "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Korea", "South Sudan", "Spain", "Sri Lanka", "Sudan", "Suriname", "Sweden", "Switzerland", "Syria",
            "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Timor-Leste", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu",
            "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States of America", "Uruguay", "Uzbekistan",
            "Vanuatu", "Vatican City", "Venezuela", "Vietnam",
            "Yemen",
            "Zambia", "Zimbabwe"
        ];

        $merged = array_unique(array_merge($dbCountries, $allCountries));
        sort($merged);

        return $merged;
    }
}
