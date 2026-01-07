<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipTierFeature extends Model
{
    protected $guarded = [];

    public function tier()
    {
        return $this->belongsTo(MembershipTier::class, 'membership_tier_id');
    }
}
