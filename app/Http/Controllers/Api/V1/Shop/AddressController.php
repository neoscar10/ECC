<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\UserAddressResource;
use App\Models\Shop\UserAddress;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'line1' => 'required|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean',
            'type' => 'nullable|string|in:shipping,billing',
        ]);

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

        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'full_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'line1' => 'sometimes|string|max:255',
            'line2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'is_default' => 'boolean',
            'type' => 'nullable|string|in:shipping,billing',
        ]);

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
