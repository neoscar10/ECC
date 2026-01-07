<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Privilege extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tiers()
    {
        return $this->belongsToMany(MembershipTier::class, 'membership_tier_privilege')->withTimestamps();
    }
}
