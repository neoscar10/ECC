<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
 
class VaultRemovalRequest extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $guarded = [];
 
    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_after_payment_at' => 'datetime',
        'refund_required_at' => 'datetime',
        'refunded_at' => 'datetime',
        'delivery_fee' => 'decimal:2',
        'selected_courier_rating' => 'decimal:2',
        'selected_courier_charge' => 'decimal:2',
        'selected_freight_charge' => 'decimal:2',
        'selected_cod_charge' => 'decimal:2',
        'package_weight_kg' => 'decimal:3',
        'package_length_cm' => 'decimal:2',
        'package_breadth_cm' => 'decimal:2',
        'package_height_cm' => 'decimal:2',
        'volumetric_weight_kg' => 'decimal:3',
        'chargeable_weight_kg' => 'decimal:3',
        'package_snapshot' => 'array',
        'shipping_metadata' => 'array',
    ];

    // ── Request Status Constants ──

    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_COMPLETED = 'completed';

    // ── Payment Status Constants ──

    const PAYMENT_NONE             = 'none';
    const PAYMENT_PENDING          = 'pending_payment';
    const PAYMENT_PAID             = 'paid';
    const PAYMENT_FAILED           = 'payment_failed';
    const PAYMENT_REFUND_REQUIRED  = 'refund_required';
    const PAYMENT_REFUNDED         = 'refunded';

    // ── Relationships ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    public function vaultItem()
    {
        return $this->belongsTo(UserVaultItem::class, 'vault_item_id');
    }
 
    public function reviewedByAdmin()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }

    public function address()
    {
        return $this->belongsTo(\App\Models\Shop\UserAddress::class, 'address_id');
    }

    public function shippingRateQuote()
    {
        return $this->belongsTo(\App\Models\Shipping\ShippingRateQuote::class, 'shipping_rate_quote_id');
    }

    /**
     * Polymorphic shipping shipment (via shippable morph on ShippingShipment).
     */
    public function shippingShipment()
    {
        return $this->morphOne(\App\Models\Shipping\ShippingShipment::class, 'shippable')->latestOfMany();
    }

    /**
     * Direct FK link to a specific ShippingShipment record.
     */
    public function linkedShippingShipment()
    {
        return $this->belongsTo(\App\Models\Shipping\ShippingShipment::class, 'shipping_shipment_id');
    }

    // ── Payment Helpers ──

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING;
    }

    public function requiresRefund(): bool
    {
        return $this->payment_status === self::PAYMENT_REFUND_REQUIRED;
    }

    public function hasCourierQuote(): bool
    {
        return filled($this->selected_courier_company_id) && $this->delivery_fee !== null;
    }

    /**
     * Admin can review only after the user has paid (business rule: pay before review).
     */
    public function canBeReviewedByAdmin(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID
            && in_array($this->status, [self::STATUS_PENDING, 'pending_review'], true);
    }

    public function canBeApproved(): bool
    {
        return $this->canBeReviewedByAdmin();
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, 'pending_review', self::STATUS_APPROVED], true)
            && $this->payment_status === self::PAYMENT_PAID;
    }

    public function isReadyForFulfillment(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->payment_status === self::PAYMENT_PAID;
    }

    /**
     * Shipment can be initiated only when both approved AND paid.
     */
    public function canInitiateShipment(): bool
    {
        return $this->isReadyForFulfillment();
    }

    // ── Label Accessors ──

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            self::PAYMENT_NONE => 'None',
            self::PAYMENT_PENDING => 'Awaiting Payment',
            self::PAYMENT_PAID => 'Paid',
            self::PAYMENT_FAILED => 'Payment Failed',
            self::PAYMENT_REFUND_REQUIRED => 'Refund Required',
            self::PAYMENT_REFUNDED => 'Refunded',
            default => ucfirst(str_replace('_', ' ', $this->payment_status)),
        };
    }

    public function getReviewStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_PENDING) {
            if ($this->payment_status === self::PAYMENT_PAID) {
                return 'Pending Review';
            }
            if ($this->payment_status === self::PAYMENT_PENDING) {
                return 'Awaiting Payment';
            }
        }
        
        return ucfirst($this->status);
    }

    public function getFulfillmentStatusLabelAttribute(): string
    {
        if ($this->isReadyForFulfillment()) {
            return 'Ready for Fulfillment';
        }
        if ($this->status === self::STATUS_COMPLETED) {
            return 'Fulfilled & Completed';
        }
        if ($this->status === self::STATUS_REJECTED) {
            if ($this->payment_status === self::PAYMENT_REFUND_REQUIRED) {
                return 'Rejected — Refund Required';
            }
            if ($this->payment_status === self::PAYMENT_REFUNDED) {
                return 'Rejected — Refunded';
            }
            return 'Rejected';
        }
        return 'N/A';
    }
}
