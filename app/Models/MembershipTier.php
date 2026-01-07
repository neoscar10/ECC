<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipTier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'benefits_json' => 'array',
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
}
