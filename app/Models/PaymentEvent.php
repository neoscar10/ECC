<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'gateway',
        'event_type',
        'gateway_event_id',
        'payload',
        'signature_valid',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'processed_at' => 'datetime'
    ];

    // ── Relationships ──

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
