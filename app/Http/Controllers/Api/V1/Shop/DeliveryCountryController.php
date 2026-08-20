<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCountry;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DeliveryCountryController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $countries = DeliveryCountry::with('addressGroup')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'code' => $country->code,
                    'delivery_type' => $country->delivery_type,
                    'fields' => $country->addressGroup ? $country->addressGroup->fields : [],
                ];
            });

        return $this->success($countries, 'Delivery countries fetched successfully.');
    }
}
