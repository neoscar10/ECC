<?php

namespace App\Domain\Membership;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipApplication extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'personal_details_json' => 'array',
        'cricket_profile_json' => 'array',
        'collector_intent_json' => 'array',
        'payment_meta_json' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function membershipTier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\MembershipTier::class);
    }

    public function membership()
    {
        return $this->hasOne(\App\Models\Membership::class, 'source_application_id');
    }
}
