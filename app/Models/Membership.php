<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MembershipApplication;

class Membership extends Model
{
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function membershipTier()
    {
        return $this->belongsTo(MembershipTier::class);
    }

    public function sourceApplication()
    {
        return $this->belongsTo(MembershipApplication::class, 'source_application_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
