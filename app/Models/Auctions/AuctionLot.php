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
        'blur_strategy' => 'string',
        'decision_made_at' => 'datetime',
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

    // Decision Maker
    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_made_by');
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
    
    public function minClearViewTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'min_clear_view_tier_id');
    }

    public function clearPrivateTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'clear_private_tier_id');
    }

    public function restrictedMinTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'restricted_min_tier_id');
    }

    public function restrictedPrivateTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'restricted_private_tier_id');
    }
    
    // Lineage
    public function reauctionedFrom(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'reauctioned_from_lot_id');
    }

    public function reauctionedTo(): BelongsTo
    {
        return $this->belongsTo(AuctionLot::class, 'reauctioned_to_lot_id');
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\Order::class);
    }

    // Scopes
    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeVisibleTo($query, ?\App\Models\User $user, ?MembershipTier $userTier = null)
    {
        return $query->where(function ($q) use ($user, $userTier) {
            // 1. Public is always visible
            $q->where('restriction_mode', 'public');

            // 2. If user exists, check visibility restrictions
            if ($userTier) {
                $q->orWhere(function ($restricted) use ($userTier) {
                    $restricted->where('restriction_mode', 'restricted')
                        // MATCH ARCHIVE LOGIC: Permissive checks (OR)
                        ->where(function ($checks) use ($userTier) {
                             // A) Allowlist / Visibility Pivot (Primary Archive Mechanism)
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

    public function getImageUrlAttribute()
    {
        $image = $this->images->first();
        return $image ? \Illuminate\Support\Facades\Storage::url($image->image_path) : null;
    }
}
