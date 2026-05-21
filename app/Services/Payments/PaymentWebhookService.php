<?php

namespace App\Services\Payments;

use App\Services\Payments\DTO\PaymentResult;
use RuntimeException;

class PaymentWebhookService
{
    protected PaymentManager $paymentManager;
    protected PaymentLedgerService $ledger;

    /**
     * Create a new PaymentWebhookService instance.
     */
    public function __construct(PaymentManager $paymentManager, PaymentLedgerService $ledger)
    {
        $this->paymentManager = $paymentManager;
        $this->ledger = $ledger;
    }

    /**
     * Handle incoming gateway webhook event notifications.
     */
    public function handle(string $gateway, array $payload, ?string $signature = null, ?string $rawBody = null): array
    {
        try {
            // Resolve the gateway adapter dynamically
            $driver = $this->paymentManager->getGateway($gateway);

            // Delegate to driver handleWebhook method
            $result = $driver->handleWebhook($payload, $signature, $rawBody);

            // If webhook successfully processes or is valid, record the event
            $this->ledger->recordEvent([
                'gateway' => $gateway,
                'event_type' => $payload['event'] ?? 'webhook.received',
                'gateway_event_id' => $payload['id'] ?? null,
                'payload' => $payload,
                'signature_valid' => true,
                'processed_at' => now(),
            ]);

            return [
                'status' => 'processed',
                'gateway' => $gateway,
                'result' => $result->toArray(),
            ];
        } catch (RuntimeException $e) {
            // Handle the placeholder controlled RuntimeException from drivers gracefully
            // Still log the raw event for debugging / audit trail in this phase
            $this->ledger->recordEvent([
                'gateway' => $gateway,
                'event_type' => $payload['event'] ?? 'webhook.placeholder_error',
                'gateway_event_id' => $payload['id'] ?? null,
                'payload' => array_merge($payload, [
                    '__error_caught' => $e->getMessage()
                ]),
                'signature_valid' => false,
                'processed_at' => now(),
            ]);

            return [
                'status' => 'ignored',
                'reason' => 'Controlled webhook placeholder error: ' . $e->getMessage(),
                'gateway' => $gateway,
            ];
        } catch (\Exception $e) {
            // Log any unexpected error
            return [
                'status' => 'error',
                'reason' => $e->getMessage(),
                'gateway' => $gateway,
            ];
        }
    }
}
