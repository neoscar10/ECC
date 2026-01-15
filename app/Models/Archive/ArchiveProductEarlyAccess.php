<?php

namespace App\Models\Archive;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveProductEarlyAccess extends Model
{
    protected $table = 'archive_product_early_access';
    protected $guarded = [];

    protected $casts = [
        'access_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ArchiveProduct::class, 'archive_product_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }
}
