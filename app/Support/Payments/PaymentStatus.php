<?php

namespace App\Support\Payments;

class PaymentStatus
{
    const INITIATED = 'initiated';
    const PENDING = 'pending';
    const AUTHORIZED = 'authorized';
    const PAID = 'paid';
    const FAILED = 'failed';
    const CANCELLED = 'cancelled';
    const REFUNDED = 'refunded';
    const REFUND_REQUIRED = 'refund_required';
}
