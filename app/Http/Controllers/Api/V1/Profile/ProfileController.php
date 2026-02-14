<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Profile\MembershipDetailsResource;
use App\Http\Resources\Profile\UserProfileResource;
use App\Services\Profile\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use \App\Support\ApiResponse;

    protected $service;

    public function __construct(ProfileService $service)
    {
        $this->service = $service;
    }

    /**
     * Get authenticated user profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $data = $this->service->getProfile($user);

        return $this->success(
            new UserProfileResource($data),
            'Profile retrieved successfully.'
        );
    }

    /**
     * Update user personal details
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'phone' => [
                'nullable', 
                'string', 
                'max:20', 
                Rule::unique('users', 'phone')->ignore($user->id)
            ],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $this->service->updateProfile($user, $request->all());
        
        // Return fresh profile data
        $data = $this->service->getProfile($user);

        return $this->success(
            new UserProfileResource($data),
            'Profile updated successfully.'
        );
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png|max:20480',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', 422, $validator->errors());
        }

        $user = $request->user();
        if ($request->hasFile('avatar')) {
             $this->service->updateAvatar($user, $request->file('avatar'));
        }

        // Return fresh profile data
        $data = $this->service->getProfile($user);

        return $this->success(
            new UserProfileResource($data),
            'Avatar updated successfully.'
        );
    }

    /**
     * Get membership details with privileges
     */
    public function membership(Request $request)
    {
        $user = $request->user();
        $data = $this->service->getMembershipDetails($user);

        // Inject Vault Details
        $data['vault'] = [
            'can_access' => (bool) $user->has_vault_access,
            'counts' => [
                 'locked' => $user->vaultItems()->locked()->count(),
                 'removed' => $user->vaultItems()->removed()->count(),
                 'total' => $user->vaultItems()->count()
            ]
        ];

        return $this->success(
            new MembershipDetailsResource($data),
            'Membership details retrieved.'
        );
    }
}
