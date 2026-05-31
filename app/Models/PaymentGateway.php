<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_enabled',
        'is_visible_to_users',
        'is_default',
        'display_order',
        'supports_web',
        'supports_mobile',
        'supports_api',
        'supports_webhooks',
        'supports_refunds',
        'supports_partial_refunds',
        'supports_subscriptions',
        'supports_upi',
        'supports_cards',
        'supports_netbanking',
        'supports_wallets',
        'supports_emi',
        'supports_pay_later',
        'maintenance_mode',
        'maintenance_message',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_visible_to_users' => 'boolean',
        'is_default' => 'boolean',
        'display_order' => 'integer',
        'supports_web' => 'boolean',
        'supports_mobile' => 'boolean',
        'supports_api' => 'boolean',
        'supports_webhooks' => 'boolean',
        'supports_refunds' => 'boolean',
        'supports_partial_refunds' => 'boolean',
        'supports_subscriptions' => 'boolean',
        'supports_upi' => 'boolean',
        'supports_cards' => 'boolean',
        'supports_netbanking' => 'boolean',
        'supports_wallets' => 'boolean',
        'supports_emi' => 'boolean',
        'supports_pay_later' => 'boolean',
        'maintenance_mode' => 'boolean',
        'metadata' => 'array',
    ];

    public function purposes(): HasMany
    {
        return $this->hasMany(PaymentGatewayPurpose::class, 'gateway_id');
    }

    public function methods(): HasMany
    {
        return $this->hasMany(PaymentGatewayMethod::class, 'gateway_id');
    }
}
