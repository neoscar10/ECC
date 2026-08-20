<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCountry extends Model
{
    protected $fillable = [
        'name',
        'code',
        'shipping_address_group_id',
        'delivery_type',
        'courier_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function addressGroup()
    {
        return $this->belongsTo(ShippingAddressGroup::class, 'shipping_address_group_id');
    }
}
