<?php

namespace App\Models\Shipping;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingShipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'shippable_type',
        'shippable_id',
        'user_id',
        'shipping_provider',
        'provider_order_id',
        'provider_shipment_id',
        'awb_code',
        'courier_company_id',
        'courier_name',
        'courier_rating',
        'courier_etd',
        'courier_estimated_delivery_days',
        'courier_freight_charge',
        'courier_cod_charge',
        'courier_total_charge',
        'courier_raw',
        'pickup_location',
        'pickup_pincode',
        'delivery_pincode',
        'payment_mode',
        'weight_kg',
        'length_cm',
        'breadth_cm',
        'height_cm',
        'volumetric_weight_kg',
        'chargeable_weight_kg',
        'shipping_charge',
        'currency',
        'delivery_address_snapshot',
        'pickup_address_snapshot',
        'package_snapshot',
        'provider_payload',
        'provider_response',
        'metadata',
        'label_url',
        'invoice_url',
        'manifest_url',
        'tracking_url',
        'status',
        'last_tracked_at',
        'initiated_at',
    ];

    protected $casts = [
        'delivery_address_snapshot' => 'array',
        'pickup_address_snapshot' => 'array',
        'package_snapshot' => 'array',
        'provider_payload' => 'array',
        'provider_response' => 'array',
        'courier_raw' => 'array',
        'metadata' => 'array',
        'weight_kg' => 'decimal:3',
        'length_cm' => 'decimal:2',
        'breadth_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'volumetric_weight_kg' => 'decimal:3',
        'chargeable_weight_kg' => 'decimal:3',
        'courier_rating' => 'decimal:2',
        'courier_freight_charge' => 'decimal:2',
        'courier_cod_charge' => 'decimal:2',
        'courier_total_charge' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'last_tracked_at' => 'datetime',
        'initiated_at' => 'datetime',
    ];

    // --- Relationships ---

    public function shippable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShippingEvent::class);
    }

    // --- Helpers ---

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function hasSelectedCourier(): bool
    {
        return !empty($this->courier_company_id);
    }

    public function canInitiateShipment(): bool
    {
        return in_array($this->status, ['courier_selected', 'ready_to_ship']) && empty($this->provider_shipment_id);
    }

    public function markCourierSelected(array $selectedCourier, ?ShippingRateQuote $quote = null): void
    {
        $this->update([
            'courier_company_id' => $selectedCourier['courier_company_id'] ?? null,
            'courier_name' => $selectedCourier['courier_name'] ?? null,
            'courier_rating' => $selectedCourier['rating'] ?? null,
            'courier_etd' => $selectedCourier['etd'] ?? null,
            'courier_estimated_delivery_days' => $selectedCourier['estimated_delivery_days'] ?? null,
            'courier_freight_charge' => $selectedCourier['freight_charge'] ?? null,
            'courier_cod_charge' => $selectedCourier['cod_charge'] ?? null,
            'courier_total_charge' => $selectedCourier['total_charge'] ?? null,
            'courier_raw' => $selectedCourier['raw'] ?? $selectedCourier,
            'shipping_charge' => $selectedCourier['total_charge'] ?? null,
            'status' => 'courier_selected',
        ]);
    }

    public function markFailed(string $message, array $context = []): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $message;
        $metadata['failure_context'] = $context;
        $metadata['failed_at'] = now()->toDateTimeString();

        $this->update([
            'status' => 'failed',
            'metadata' => $metadata,
        ]);
    }
}
