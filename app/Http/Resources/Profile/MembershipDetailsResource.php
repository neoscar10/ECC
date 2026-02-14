<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Expecting array from Service -> getMembershipDetails
        $tier = $this['tier'];
        $status = $this['status'];
        $startedAt = $this['started_at'];
        $expiresAt = $this['expires_at'];
        $privileges = $this['privileges']; // Collection

        if (!$tier) {
            return [
                'status' => 'inactive',
                'tier' => null,
                'privileges' => [],
            ];
        }

        return [
            'status' => $status,
            'joined_at' => $startedAt,
            'expires_at' => $expiresAt,
            'tier' => [
                'id' => $tier->id,
                'code' => $tier->code,
                'name' => $tier->name,
                'level' => $tier->id, // Assuming ID proxies for level or add level column if exists
                'has_vault_access' => $tier->has_vault_access,
                'benefits' => $tier->benefits_json,
            ],
            'privileges' => $privileges->map(function ($p) {
                return [
                    'id' => $p->id,
                    'key' => $p->key,
                    'name' => $p->name,
                    'description' => $p->description,
                    'icon' => $p->icon,
                ];
            }),
            'vault' => $this['vault'] ?? null,
        ];
    }
}
