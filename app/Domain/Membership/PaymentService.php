<?php

namespace App\Domain\Membership;

use Exception;
use App\Models\MembershipApplication;
use App\Models\Payment;

class PaymentService
{
    /**
     * Process a test payment.
     * 
     * @param MembershipApplication $application
     * @param array $data
     * @return Payment
     */
    public function processTestPayment(MembershipApplication $application, array $data)
    {
        // Security Check: Ensure raw card data is NEVER saved
        if (isset($data['card_number']) || isset($data['cvv'])) {
            throw new Exception("Security Violation: Raw card data detected.");
        }

        // Build base meta (card details)
        $meta = [
            'cardholder_name' => $data['cardholder_name'] ?? null,
            'last4'           => $data['last4'] ?? null,
            'brand'           => $data['brand'] ?? 'unknown',
        ];

        // Merge proration audit trail when provided (upgrade payments)
        if (!empty($data['upgrade_context'])) {
            $meta['upgrade_context'] = $data['upgrade_context'];
        }

        return $application->payments()->create([
            'gateway'  => 'test',
            'method'   => $data['method'] ?? 'card',
            'amount'   => $data['amount'],
            'currency' => $data['currency'] ?? 'INR',
            'status'   => 'test_paid',
            'reference' => 'TEST-' . uniqid(),
            'meta_json' => $meta,
        ]);
    }
}
