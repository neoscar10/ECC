<?php

namespace App\Models\Archive;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(ArchiveProduct::class);
    }

    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'archive_category_tier', 'archive_category_id', 'membership_tier_id')
            ->withTimestamps();
    }

    /**
     * Get IDs of tiers allowed to access this category.
     * If public (no tiers related or visibility flag?), assumes all.
     * BUT: ArchiveCategory schema usually has a 'visibility' or 'is_public' flag.
     * Based on strict requirements: "If category is public => allowed = all current tiers".
     * "If category is restricted => allowed = category's configured tiers".
     */
    public function getAllowedTierIds()
    {
        if ($this->visibility === 'public') {
             return MembershipTier::where('is_active', true)->pluck('id')->toArray();
        }
        return $this->tiers()->pluck('membership_tiers.id')->toArray();
    }
}
