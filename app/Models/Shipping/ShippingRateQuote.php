<?php

namespace App\Models\Shipping;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ShippingRateQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'shippable_type',
        'shippable_id',
        'user_id',
        'shipping_provider',
        'pickup_pincode',
        'delivery_pincode',
        'payment_mode',
        'weight_kg',
        'length_cm',
        'breadth_cm',
        'height_cm',
        'volumetric_weight_kg',
        'chargeable_weight_kg',
        'selected_courier_company_id',
        'selected_courier_name',
        'selected_courier_rating',
        'selected_freight_charge',
        'selected_cod_charge',
        'selected_total_charge',
        'selected_etd',
        'selected_estimated_delivery_days',
        'selected_courier_raw',
        'quotes',
        'raw_response',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'length_cm' => 'decimal:2',
        'breadth_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'volumetric_weight_kg' => 'decimal:3',
        'chargeable_weight_kg' => 'decimal:3',
        'selected_courier_rating' => 'decimal:2',
        'selected_freight_charge' => 'decimal:2',
        'selected_cod_charge' => 'decimal:2',
        'selected_total_charge' => 'decimal:2',
        'selected_courier_raw' => 'array',
        'quotes' => 'array',
        'raw_response' => 'array',
        'expires_at' => 'datetime',
    ];

    public function shippable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasSelectedCourier(): bool
    {
        return !empty($this->selected_courier_company_id);
    }
}
