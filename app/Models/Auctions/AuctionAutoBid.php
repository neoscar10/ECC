<?php

namespace App\Models\Auctions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionAutoBid extends Model
{
    protected $guarded = [];

    protected $casts = [
        'max_bid' => 'decimal:2',
        'increment_amount' => 'decimal:2',
        'is_enabled' => 'boolean',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'auction_lot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
