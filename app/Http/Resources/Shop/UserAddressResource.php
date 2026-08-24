<?php

namespace App\Http\Resources\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'delivery_country_id' => $this->delivery_country_id,
            'delivery_type' => $this->deliveryCountry->delivery_type ?? ($this->country && strtolower(trim($this->country)) !== 'india' ? 'negotiated' : 'courier'),
            'is_default' => $this->is_default,
            'type' => $this->type,
        ];
    }
}
