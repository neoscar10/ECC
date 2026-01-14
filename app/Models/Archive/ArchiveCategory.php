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
}
