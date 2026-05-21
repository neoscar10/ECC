<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipTier extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->isDirty('price_amount') && !$model->isDirty('price')) {
                $model->price = (float) $model->price_amount / 100;
            } elseif ($model->isDirty('price') && !$model->isDirty('price_amount')) {
                $model->price_amount = (int) ($model->price * 100);
            } elseif (!$model->price_amount && $model->price) {
                $model->price_amount = (int) ($model->price * 100);
            } elseif ($model->price_amount && !$model->price) {
                $model->price = (float) $model->price_amount / 100;
            }
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'has_early_access' => 'boolean', // [NEW] Capability flag
        'has_vault_access' => 'boolean',
        'is_auto_bidding_enabled' => 'boolean',
        'benefits_json' => 'array',
    ];

    protected $hidden = [
        // 'price_amount',
    ];

    public function privileges()
    {
        return $this->belongsToMany(Privilege::class, 'membership_tier_privilege')->withTimestamps();
    }

    public function features()
    {
        return $this->hasMany(MembershipTierFeature::class)->orderBy('sort_order');
    }

    public function upgradeFrom()
    {
        return $this->belongsTo(MembershipTier::class, 'upgrade_from_id');
    }

    public function upgradesTo()
    {
        return $this->hasMany(MembershipTier::class, 'upgrade_from_id');
    }

    public static function getBrokenRestrictionsCount()
    {
        $activeTierIds = self::pluck('id')->toArray();
        
        $orphanedAuctions = \App\Models\Auctions\AuctionLot::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)->count();
            
        $orphanedArchive = \App\Models\Archive\ArchiveProduct::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)->count();
            
        $orphanedCms = \App\Models\Cms\CmsBlock::whereNotNull('restricted_min_tier_id')
            ->whereNotIn('restricted_min_tier_id', $activeTierIds)->count();

        return $orphanedAuctions + $orphanedArchive + $orphanedCms;
    }
}
