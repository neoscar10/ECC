<?php

namespace App\Models\Auctions;

use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuctionLot extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'ended_at' => 'datetime',
        'starting_price' => 'decimal:2',
        'min_selling_price' => 'decimal:2',
        'current_highest_bid' => 'decimal:2',
        'min_increment' => 'decimal:2',
        'anti_sniping_enabled' => 'boolean',
        'blur_enabled' => 'boolean',
        'early_access_enabled' => 'boolean',
    ];

    // --- Relationships ---

    // History
    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)->orderBy('amount', 'desc');
    }

    public function latestBid(): BelongsTo
    {
         // Usually redundant if we track current_highest_bid denormalized, but good for validation
         return $this->belongsTo(AuctionBid::class, 'winner_user_id', 'user_id')
             ->where('auction_lot_id', $this->id) // simplified logic, actually latest bid is by time/amount
             ->latest('amount');
    }
    
    // Auto Bids
    public function autoBids(): HasMany
    {
        return $this->hasMany(AuctionAutoBid::class);
    }
    
    // Audit
    public function events(): HasMany
    {
        return $this->hasMany(AuctionEvent::class)->latest();
    }
    
    // Winner
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }
    
    // Media - Mirrors Archive Pattern
    public function images(): HasMany
    {
        return $this->hasMany(AuctionLotImage::class)->orderBy('sort_order');
    }
    
    public function attachments(): HasMany
    {
        return $this->hasMany(AuctionLotAttachment::class)->orderBy('sort_order');
    }
    
    // Access Control
    public function earlyAccessWindows(): HasMany
    {
        return $this->hasMany(AuctionLotEarlyAccess::class);
    }

    public function earlyAccessWindowsDesc(): HasMany
    {
         return $this->hasMany(AuctionLotEarlyAccess::class)->orderBy('access_at', 'desc');
    }
    
    public function visibilityTiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'auction_lot_visibility_tier', 'auction_lot_id', 'membership_tier_id')
            ->withTimestamps();
    }
    
    public function clearViewTiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'auction_lot_clear_tier', 'auction_lot_id', 'membership_tier_id')
            ->withTimestamps();
    }
    
    // Lineage
    public function reauctionedFrom(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'reauctioned_from_lot_id');
    }

    // Scopes
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }
}
