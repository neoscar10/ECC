<?php

namespace App\Services\Profile;

use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    /**
     * Get user profile details including membership summary
     */
    public function getProfile(User $user): array
    {
        $currentMembership = $user->currentMembership()->with('membershipTier.privileges')->first();
        $tier = $currentMembership ? $currentMembership->membershipTier : null;

        $avatarUrl = $user->avatar_path ? Storage::url($user->avatar_path) : null;

        // Simplify membership summary
        $membershipData = null;
        if ($tier) {
            $membershipData = [
                'tier' => [
                    'id' => $tier->id,
                    'code' => $tier->code,
                    'name' => $tier->name,
                    'is_active' => $tier->is_active,
                ],
                'status' => $currentMembership->status,
                'expires_at' => $currentMembership->expires_at,
            ];
        }

        // Return structured data suitable for resource/response
        return [
            'user' => $user, // Send model for Resource processing
            'avatar_url' => $avatarUrl,
            'avatar_required' => is_null($user->avatar_path),
            'membership' => $membershipData,
        ];
    }

    /**
     * Update user personal details
     */
    public function updateProfile(User $user, array $data): User
    {
        // Filter only allowed fields to prevent mass assignment of sensitive data if not handled by request
        // (Assuming request validation handles most, but good to be explicit)
        $allowed = [
            'full_name', 'name', 'phone', 'date_of_birth', 'country', 'city', 'gender'
        ];

        // Address update is separate or part of this? Request mentions "address fields".
        // If address fields are on user table, update them.
        
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return $user->refresh();
    }

    /**
     * Upload and update avatar
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        // 1. Delete old avatar if exists
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // 2. Store new file
        // Path: users/{id}/avatar/filename
        $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("users/{$user->id}/avatar", $filename, 'public');

        // 3. Update DB
        $user->update(['avatar_path' => $path]);

        return Storage::url($path);
    }

    /**
     * Get full membership details with privileges
     */
    public function getMembershipDetails(User $user): array
    {
        $currentMembership = $user->currentMembership()->with(['membershipTier.privileges' => function($q) {
            $q->where('is_active', true)->orderBy('sort_order')->orderBy('name');
        }])->first();

        if (!$currentMembership || !$currentMembership->membershipTier) {
            // Return empty or guest tier structure if needed
            return [
                'tier' => null,
                'status' => 'inactive',
                'expires_at' => null,
                'privileges' => []
            ];
        }

        $tier = $currentMembership->membershipTier;
        
        return [
            'tier' => $tier, // Return model for Resource
            'status' => $currentMembership->status,
            'started_at' => $currentMembership->started_at,
            'expires_at' => $currentMembership->expires_at,
            'privileges' => $tier->privileges, // Already filtered and sorted
        ];
    }
}
