<?php

namespace App\Models\Shop;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'shipping_fee',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'shipping_address_snapshot',
        'billing_address_snapshot',
        'notes',
        'placed_at',
        'paid_at',
        'cancelled_at',
        'meta_json',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipping_address_snapshot' => 'array',
        'billing_address_snapshot' => 'array',
        'meta_json' => 'array',
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }

    public function shippingShipment()
    {
        return $this->morphOne(\App\Models\Shipping\ShippingShipment::class, 'shippable')->latestOfMany();
    }
}
