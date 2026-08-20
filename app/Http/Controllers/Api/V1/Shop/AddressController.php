<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\UserAddressResource;
use App\Models\Shop\UserAddress;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\DeliveryCountry;

class AddressController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->orderByDesc('is_default')->latest()->get();
        return $this->success(UserAddressResource::collection($addresses), 'Addresses fetched successfully.');
    }

    public function store(Request $request)
    {
        $baseRules = [
            'label' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'type' => 'nullable|string|in:shipping,billing',
            'delivery_country_id' => 'required|exists:delivery_countries,id',
            'country' => 'nullable|string|max:100', // Kept for backward compat or custom name
        ];

        // Dynamic validation based on country
        $countryId = $request->input('delivery_country_id');
        $dynamicRules = [];

        if ($countryId) {
            $country = DeliveryCountry::with('addressGroup')->find($countryId);
            if ($country && $country->addressGroup) {
                $fields = $country->addressGroup->fields ?? [];
                
                // Set default rules for all possible fields to nullable first
                $possibleFields = ['full_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code'];
                foreach ($possibleFields as $field) {
                    $dynamicRules[$field] = 'nullable|string|max:255';
                }

                foreach ($fields as $fieldConfig) {
                    $fieldName = $fieldConfig['name'];
                    $isRequired = $fieldConfig['is_required'] ?? false;
                    $rule = $isRequired ? 'required|string|max:255' : 'nullable|string|max:255';
                    $dynamicRules[$fieldName] = $rule;
                }
            }
        }

        $validated = $request->validate(array_merge($baseRules, $dynamicRules));

        if (!empty($validated['is_default']) && $validated['is_default']) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($validated);

        return $this->success(new UserAddressResource($address), 'Address created successfully.');
    }

    public function show(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        return $this->success(new UserAddressResource($address), 'Address details fetched successfully.');
    }

    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $baseRules = [
            'label' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'type' => 'nullable|string|in:shipping,billing',
            'delivery_country_id' => 'sometimes|exists:delivery_countries,id',
            'country' => 'nullable|string|max:100',
        ];

        $countryId = $request->input('delivery_country_id', $address->delivery_country_id);
        $dynamicRules = [];

        if ($countryId) {
            $country = DeliveryCountry::with('addressGroup')->find($countryId);
            if ($country && $country->addressGroup) {
                $fields = $country->addressGroup->fields ?? [];
                
                $possibleFields = ['full_name', 'phone', 'line1', 'line2', 'city', 'state', 'postal_code'];
                foreach ($possibleFields as $field) {
                    $dynamicRules[$field] = 'nullable|string|max:255';
                }

                foreach ($fields as $fieldConfig) {
                    $fieldName = $fieldConfig['name'];
                    $isRequired = $fieldConfig['is_required'] ?? false;
                    $rule = $isRequired ? 'sometimes|required|string|max:255' : 'nullable|string|max:255';
                    $dynamicRules[$fieldName] = $rule;
                }
            }
        }

        $validated = $request->validate(array_merge($baseRules, $dynamicRules));

        if (isset($validated['is_default']) && $validated['is_default']) {
            $request->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return $this->success(new UserAddressResource($address), 'Address updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return $this->success(null, 'Address deleted successfully.');
    }
}
