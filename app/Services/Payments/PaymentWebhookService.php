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

    public function handle(string $gateway, array $payload, ?string $signature = null, ?string $rawBody = null): array
    {
        try {
            // Resolve the gateway adapter dynamically
            $driver = $this->paymentManager->getGateway($gateway);

            // Extract identifiers dynamically
            $identifiers = $driver->extractIdentifiers($payload);

            $gatewayEventId = $identifiers['gateway_event_id'] ?? $payload['id'] ?? null;
            $eventType = $identifiers['event_type'] ?? $payload['event'] ?? 'webhook.received';

            $internalPaymentId = $identifiers['internal_payment_id'] ?? null;
            $gatewayOrderId = $identifiers['gateway_order_id'] ?? null;
            $gatewayPaymentId = $identifiers['gateway_payment_id'] ?? null;

            $payment = null;

            // Priority 1: Match by internal_payment_id and ensure gateway matches
            if ($internalPaymentId && is_numeric($internalPaymentId)) {
                $payment = \App\Models\Payment::where('id', (int) $internalPaymentId)
                    ->where('gateway', $gateway)
                    ->first();
            }

            // Priority 2: Match by gateway_payment_id and gateway
            if (!$payment && $gatewayPaymentId) {
                $payment = \App\Models\Payment::where('gateway_payment_id', $gatewayPaymentId)
                    ->where('gateway', $gateway)
                    ->first();
            }

            // Priority 3: Match by gateway_order_id and gateway
            if (!$payment && $gatewayOrderId) {
                $payment = \App\Models\Payment::where('gateway_order_id', $gatewayOrderId)
                    ->where('gateway', $gateway)
                    ->first();
            }

            // Conflict protection:
            // If internal_payment_id points to a Payment but webhook gateway_order_id does not match payment.gateway_order_id, do not process.
            if ($payment && $gatewayOrderId && $payment->gateway_order_id !== $gatewayOrderId) {
                \Illuminate\Support\Facades\Log::warning("PaymentWebhookService: Gateway order ID conflict detected.", [
                    'gateway' => $gateway,
                    'payment_id' => $payment->id,
                    'payment_gateway_order_id' => $payment->gateway_order_id,
                    'webhook_gateway_order_id' => $gatewayOrderId,
                ]);
                
                $this->ledger->recordEvent([
                    'payment_id' => $payment->id,
                    'gateway' => $gateway,
                    'event_type' => $eventType,
                    'gateway_event_id' => $gatewayEventId,
                    'payload' => array_merge($payload, ['__conflict' => 'Gateway order ID conflict']),
                    'signature_valid' => true,
                    'processed_at' => now(),
                ]);

                return [
                    'status' => 'conflict',
                    'reason' => 'Gateway order ID conflict.',
                    'gateway' => $gateway,
                ];
            }

            // Check for duplicate event
            $isDuplicate = false;
            if ($gatewayEventId) {
                $isDuplicate = \App\Models\PaymentEvent::where('gateway', $gateway)
                    ->where('gateway_event_id', $gatewayEventId)
                    ->exists();
            } else {
                // fallback to gateway + event_type + gateway_order_id/gateway_payment_id + recent processed event
                if ($gatewayOrderId || $gatewayPaymentId) {
                    $isDuplicate = \App\Models\PaymentEvent::where('gateway', $gateway)
                        ->where('event_type', $eventType)
                        ->where(function($q) use ($gatewayOrderId, $gatewayPaymentId) {
                            if ($gatewayOrderId) {
                                $q->where('payload->order_id', $gatewayOrderId)
                                  ->orWhere('payload->data->order->order_id', $gatewayOrderId);
                            }
                            if ($gatewayPaymentId) {
                                $q->orWhere('payload->cf_payment_id', $gatewayPaymentId)
                                  ->orWhere('payload->data->payment->cf_payment_id', $gatewayPaymentId);
                            }
                        })
                        ->where('created_at', '>=', now()->subMinutes(10))
                        ->exists();
                }
            }

            if ($isDuplicate) {
                \Illuminate\Support\Facades\Log::info("PaymentWebhookService: Duplicate event ignored.", [
                    'gateway' => $gateway,
                    'gateway_event_id' => $gatewayEventId,
                ]);

                $this->ledger->recordEvent([
                    'payment_id' => $payment ? $payment->id : null,
                    'gateway' => $gateway,
                    'event_type' => $eventType,
                    'gateway_event_id' => $gatewayEventId,
                    'payload' => array_merge($payload, ['__duplicate' => true]),
                    'signature_valid' => true,
                    'processed_at' => now(),
                ]);

                return [
                    'status' => 'duplicate',
                    'signature_valid' => true,
                    'gateway' => $gateway,
                ];
            }

            // Delegate signature verification and status extraction to the gateway driver
            $result = $driver->handleWebhook($payload, $signature, $rawBody);

            $signatureValid = !in_array($result->failureCode, ['missing_webhook_signature', 'invalid_webhook_signature']);

            // Store event
            $this->ledger->recordEvent([
                'payment_id' => $payment ? $payment->id : null,
                'gateway' => $gateway,
                'event_type' => $eventType,
                'gateway_event_id' => $gatewayEventId,
                'payload' => $payload,
                'signature_valid' => $signatureValid,
                'processed_at' => now(),
            ]);

            if (!$signatureValid) {
                return [
                    'status' => 'ignored',
                    'signature_valid' => false,
                    'gateway' => $gateway,
                    'result' => $result->toArray(),
                ];
            }

            if ($payment) {
                $finalizer = app(\App\Services\Payments\PaymentFinalizationService::class);
                if ($result->success && $result->status === \App\Support\Payments\PaymentStatus::PAID) {
                    if ($payment->isPaid()) {
                        \Illuminate\Support\Facades\Log::info("PaymentWebhookService: Payment already paid. Skipping finalization.", [
                            'payment_id' => $payment->id,
                        ]);
                        return [
                            'status' => 'duplicate',
                            'signature_valid' => true,
                            'gateway' => $gateway,
                            'result' => $result->toArray(),
                        ];
                    }

                    $payment = $this->ledger->markPaid($payment, $result->gatewayPaymentId, $result->raw);
                    $finalizer->finalizePaidPayment($payment);

                    \Illuminate\Support\Facades\Log::info("PaymentWebhookService: Payment finalized successfully via webhook.", [
                        'payment_id' => $payment->id,
                        'gateway' => $gateway,
                    ]);
                } elseif ($result->status === \App\Support\Payments\PaymentStatus::FAILED) {
                    // Protect PAID payments from being downgraded to FAILED
                    if (!$payment->isPaid()) {
                        $payment = $this->ledger->markFailed($payment, $result->failureCode, $result->failureMessage, $result->raw);
                        $finalizer->markPaymentFailed($payment, $result->failureMessage ?? 'Webhook verification failed.');
                        
                        \Illuminate\Support\Facades\Log::info("PaymentWebhookService: Payment marked failed via webhook.", [
                            'payment_id' => $payment->id,
                            'gateway' => $gateway,
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::info("PaymentWebhookService: Prevented downgrade of paid payment to failed.", [
                            'payment_id' => $payment->id,
                        ]);
                    }
                }
            } else {
                \Illuminate\Support\Facades\Log::info("PaymentWebhookService: Webhook received for unmatched payment.", [
                    'gateway' => $gateway,
                    'gateway_order_id' => $gatewayOrderId,
                ]);
            }

            return [
                'status' => 'processed',
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
