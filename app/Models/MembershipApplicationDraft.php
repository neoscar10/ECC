<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipApplicationDraft extends Model
{
    protected $fillable = [
        'session_id',
        'payload_json',
        'current_step',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];
}
