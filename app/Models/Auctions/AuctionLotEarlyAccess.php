<?php

namespace App\Models\Auctions;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionLotEarlyAccess extends Model
{
    use HasFactory;

    protected $table = 'auction_lot_early_access';
    protected $guarded = [];

    protected $casts = [
        'access_at' => 'datetime',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'auction_lot_id');
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }
}
