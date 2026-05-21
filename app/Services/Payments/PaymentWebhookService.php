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

            // Retrieve payment entity notes or order ID to locate internal payment
            $entity = $payload['payload']['payment']['entity'] ?? [];
            $internalPaymentId = $entity['notes']['internal_payment_id'] ?? null;
            $gatewayOrderId = $result->gatewayOrderId ?: ($entity['order_id'] ?? null);

            $payment = null;
            if ($internalPaymentId) {
                $payment = \App\Models\Payment::find($internalPaymentId);
            }
            if (!$payment && $gatewayOrderId) {
                $payment = \App\Models\Payment::where('gateway_order_id', $gatewayOrderId)->first();
            }

            // Evaluate signature validity
            $signatureValid = !in_array($result->failureCode, ['missing_webhook_signature', 'invalid_webhook_signature']);

            // If webhook successfully processes or is valid, record the event
            $this->ledger->recordEvent([
                'payment_id' => $payment ? $payment->id : null,
                'gateway' => $gateway,
                'event_type' => $payload['event'] ?? 'webhook.received',
                'gateway_event_id' => $payload['id'] ?? null,
                'payload' => $payload,
                'signature_valid' => $signatureValid,
                'processed_at' => now(),
            ]);

            if ($signatureValid && $payment) {
                $finalizer = app(\App\Services\Payments\PaymentFinalizationService::class);
                if ($result->success && $result->status === \App\Support\Payments\PaymentStatus::PAID) {
                    $payment = $this->ledger->markPaid($payment, $result->gatewayPaymentId, $result->raw);
                    $finalizer->finalizePaidPayment($payment);
                } elseif ($result->status === \App\Support\Payments\PaymentStatus::FAILED) {
                    // Protect PAID payments from being downgraded to FAILED
                    if (!$payment->isPaid()) {
                        $payment = $this->ledger->markFailed($payment, $result->failureCode, $result->failureMessage, $result->raw);
                        $finalizer->markPaymentFailed($payment, $result->failureMessage ?? 'Webhook verification failed.');
                    }
                }
            }

            return [
                'status' => $signatureValid ? 'processed' : 'ignored',
                'signature_valid' => $signatureValid,
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
