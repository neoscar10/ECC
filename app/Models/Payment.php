<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Support\Payments\PaymentStatus;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payable_type',
        'payable_id',
        'purpose',
        'gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'amount',
        'currency',
        'status',
        'failure_code',
        'failure_message',
        'meta',
        'paid_at',
        'failed_at',
        
        // Backward compatibility legacy fields
        'method',
        'reference',
        'meta_json'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'meta_json' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // ── Relationships ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    // ── Helper Methods ──

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING || $this->status === PaymentStatus::INITIATED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function markPending(): self
    {
        $this->update([
            'status' => PaymentStatus::PENDING
        ]);

        return $this;
    }

    public function markPaid(?string $gatewayPaymentId = null, array $meta = []): self
    {
        $updateData = [
            'status' => PaymentStatus::PAID,
            'paid_at' => now(),
            'meta' => array_merge($this->meta ?? [], $meta)
        ];

        if ($gatewayPaymentId) {
            $updateData['gateway_payment_id'] = $gatewayPaymentId;
        }

        $this->update($updateData);

        return $this;
    }

    public function markFailed(?string $failureCode = null, ?string $failureMessage = null, array $meta = []): self
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
            'failure_code' => $failureCode,
            'failure_message' => $failureMessage,
            'meta' => array_merge($this->meta ?? [], $meta)
        ]);

        return $this;
    }
}
