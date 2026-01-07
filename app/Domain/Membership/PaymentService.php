<?php

namespace App\Domain\Membership;

use Exception;

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

        return $application->payments()->create([
            'gateway' => 'test',
            'method' => $data['method'] ?? 'card',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => 'test_paid',
            'reference' => 'TEST-' . uniqid(),
            'meta_json' => [
                'cardholder_name' => $data['cardholder_name'] ?? null,
                'last4' => $data['last4'] ?? null,
                'brand' => $data['brand'] ?? 'unknown',
            ]
        ]);
    }
}
