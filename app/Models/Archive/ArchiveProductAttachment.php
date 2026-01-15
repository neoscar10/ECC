<?php

namespace App\Models\Archive;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveProductAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ArchiveProduct::class, 'archive_product_id');
    }

    // Tiers for "Random" restriction type (attachment specific)
    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'archive_attachment_tier', 'archive_product_attachment_id', 'membership_tier_id')
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
}
