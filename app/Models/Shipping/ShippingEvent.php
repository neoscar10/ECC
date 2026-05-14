<?php

namespace App\Models\Shipping;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_shipment_id',
        'shipping_provider',
        'event_code',
        'event_status',
        'event_description',
        'location',
        'event_time',
        'raw_payload',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShippingShipment::class, 'shipping_shipment_id');
    }
}
