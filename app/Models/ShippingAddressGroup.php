<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddressGroup extends Model
{
    protected $fillable = [
        'name',
        'fields',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
    ];
}
