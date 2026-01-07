<?php

namespace App\Domain\Membership;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta_json' => 'array',
        'amount' => 'decimal:2',
    ];

    public function membershipApplication(): BelongsTo
    {
        return $this->belongsTo(MembershipApplication::class);
    }
}
