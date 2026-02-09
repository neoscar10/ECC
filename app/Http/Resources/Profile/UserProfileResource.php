<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->resource is expected to be the User model
        // However, the Service returns an array with 'user' model and extra keys like 'avatar_url'
        // Let's adjust based on how we call this. 
        // If we pass the array from service:
        // We might need a custom approach or pass individual items.
        
        // Let's assume the Controller passes the User model, and we handle avatar derivation here or helper.
        // BUT the service already does logic. 
        // Let's make this resource handle the ARRAY returned by Service -> getProfile.

        $user = $this['user'] ?? null;
        $membership = $this['membership'] ?? null;
        $avatarUrl = $this['avatar_url'] ?? null;
        $avatarRequired = $this['avatar_required'] ?? true;

        if (!$user) {
            // Fallback if passed directly as User model (e.g. update response)
             if ($this->resource instanceof \App\Models\User) {
                 $user = $this->resource;
                 $avatarUrl = $user->avatar_path ? Storage::url($user->avatar_path) : null;
                 $avatarRequired = is_null($user->avatar_path);
                 // Membership summary implies calling service or relationship
                 // For now, let's keep it simple.
             }
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone, // already verified?
                'date_of_birth' => $user->date_of_birth,
                'country' => $user->country,
                'city' => $user->city,
                'member_code' => $user->member_code ?? ('EXEC-' . str_pad($user->id, 3, '0', STR_PAD_LEFT)), // Mock code if not exists
                'avatar_url' => $avatarUrl,
                'avatar_required' => $avatarRequired,
            ],
            'membership' => $membership,
            // 'tier_privileges' => ... (handled by separate endpoint or added here if requested)
            // The requirement says "create endpoint C" but also "can embed summary".
            // Let's keep it minimal here as per "membership summary".
        ];
    }
}
