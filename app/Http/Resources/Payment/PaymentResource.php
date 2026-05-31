<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $paidAt = null;
        if (isset($this->paid_at)) {
            if (is_string($this->paid_at)) {
                $paidAt = $this->paid_at;
            } elseif ($this->paid_at instanceof \Carbon\Carbon || $this->paid_at instanceof \DateTimeInterface) {
                $paidAt = $this->paid_at->toIso8601String();
            }
        }

        // Support dynamic checkout property (like from mock/controllers) or fallback to meta
        $checkout = null;
        if (isset($this->checkout)) {
            $checkout = $this->checkout;
        } else {
            $meta = isset($this->meta) ? $this->meta : null;
            $checkout = is_array($meta) && isset($meta['checkout']) ? $meta['checkout'] : null;
        }

        return [
            'id' => $this->id,
            'gateway' => $this->gateway ?? null,
            'status' => $this->status ?? null,
            'amount' => isset($this->amount) ? (float) $this->amount : 0.0,
            'currency' => $this->currency ?? 'INR',
            'purpose' => $this->purpose ?? null,
            'verify_endpoint' => url('/api/v1/payments/' . ($this->gateway ?? 'razorpay') . '/verify'),
            'checkout' => $checkout,
            'gateway_order_id' => $this->gateway_order_id ?? null,
            'gateway_payment_id' => $this->gateway_payment_id ?? null,
            'paid_at' => $paidAt,
        ];
    }
}
