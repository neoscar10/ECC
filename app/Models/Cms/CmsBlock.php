<?php

namespace App\Models\Cms;

use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\CmsBlockFactory::new();
    }

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'content' => 'array',
        'type_config' => 'array',
        'blur_enabled' => 'boolean',
    ];

    // Relation: Tiers that have VISIBILITY access (can see the block exists)
    public function visibilityTiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'cms_block_visibility_tier', 'cms_block_id', 'membership_tier_id')
            ->withTimestamps();
    }

    // Relation: Tiers that get CLEAR view when blur is enabled
    public function clearViewTiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'cms_block_clear_tier', 'cms_block_id', 'membership_tier_id')
            ->withTimestamps();
    }

    public function restrictedMinTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'restricted_min_tier_id');
    }

    public function restrictedPrivateTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'restricted_private_tier_id');
    }
    
    public function minClearViewTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'min_clear_view_tier_id');
    }

    /**
     * Scope to filter blocks visible to a user.
     * STRICTLY MIRRORS ArchiveProduct::scopeVisibleTo
     */
    public function scopeVisibleTo($query, ?\App\Models\User $user, ?MembershipTier $userTier = null)
    {
        return $query->where(function($p) use ($userTier) {
            // 1. Public is always visible
            $p->where('restriction_mode', 'public');

            // 2. If user tier exists, check visibility restrictions
            if ($userTier) {
                $p->orWhere(function ($restricted) use ($userTier) {
                     $restricted->where('restriction_mode', 'restricted')
                        // MATCH ARCHIVE LOGIC: Permissive checks (OR)
                        ->where(function ($checks) use ($userTier) {
                             // A) Allowlist / Visibility Pivot
                             $checks->whereHas('visibilityTiers', function ($t) use ($userTier) {
                                  $t->where('membership_tiers.id', $userTier->id);
                             })
                             // B) Hierarchical
                             ->orWhereHas('restrictedMinTier', function ($t) use ($userTier) {
                                  $t->where('level', '<=', $userTier->level);
                             })
                             // C) Private
                             ->orWhere('restricted_private_tier_id', $userTier->id);
                        });
                });
            }
        });
    }
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
