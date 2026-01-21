<?php

namespace App\Models\Archive;

use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'go_live_now' => 'boolean',
        'go_live_at' => 'datetime',
        'early_access_enabled' => 'boolean',
        'price_min_amount' => 'integer',
        'price_max_amount' => 'integer',
        'quantity' => 'integer',
        'blur_enabled' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArchiveCategory::class, 'archive_category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ArchiveProductImage::class)->orderBy('sort_order');
    }

    public function images360(): HasMany
    {
        return $this->hasMany(ArchiveProduct360Image::class)->orderBy('sort_order');
    }

    // Tiers for "Random" restriction type
    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'archive_product_tier', 'archive_product_id', 'membership_tier_id')
            ->withTimestamps();
    }
    
    // Tiers that have VISIBILITY access (can see the product card)
    public function visibilityTiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'archive_product_visibility_tier', 'archive_product_id', 'membership_tier_id')
            ->withTimestamps();
    }

    // Tiers that get CLEAR view when blur is enabled
    public function clearViewTiers(): BelongsToMany
    {
        return $this->belongsToMany(MembershipTier::class, 'archive_product_clear_tier', 'archive_product_id', 'membership_tier_id')
            ->withTimestamps();
    }

    // Tiers for Early Access windows
    public function earlyAccessWindows(): HasMany
    {
        return $this->hasMany(ArchiveProductEarlyAccess::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ArchiveProductAttachment::class)->orderBy('sort_order');
    }

    public function restrictedMinTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'restricted_min_tier_id');
    }

    public function restrictedPrivateTier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'restricted_private_tier_id');
    }

    public function scopeVisibleTo($query, ?\App\Models\User $user, ?MembershipTier $userTier = null)
    {
        return $query->where(function ($q) use ($user, $userTier) {
            // CATEGORY GATE: Product must belong to a category visible to the user
            $q->whereHas('category', function ($catQuery) use ($userTier) {
                 $catQuery->where(function($c) use ($userTier) {
                      // Cat Public
                      $c->where('visibility', 'public');
                      // OR Cat Restricted but user has tier
                      if ($userTier) {
                          $c->orWhere(function($cr) use ($userTier) {
                              $cr->where('visibility', 'restricted')
                                 ->whereHas('tiers', function($t) use ($userTier) {
                                      $t->where('membership_tiers.id', $userTier->id);
                                 });
                          });
                      }
                 });
            });

            // PRODUCT GATE: Only if category gate passed, apply product rules
            $q->where(function($p) use ($userTier) {
                // 1. Public is always visible (within allowed category)
                $p->where('restriction_mode', 'public');

                // 2. If user exists, check visibility restrictions
                if ($userTier) {
                    $p->orWhere(function ($restricted) use ($userTier) {
                        $restricted->where('restriction_mode', 'restricted')
                            ->whereHas('visibilityTiers', function ($t) use ($userTier) {
                                $t->where('membership_tiers.id', $userTier->id);
                            });
                    });
                }
            });
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ArchiveOrder::class);
    }
}
