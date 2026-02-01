<?php

namespace App\Models\Auctions;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionNotificationSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'auction_lot_id',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auctionLot(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class);
    }
}
