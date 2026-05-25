# Payment — Cashfree Phase 5: Webhook & Idempotent Payment Confirmation

**Status:** Phase 5 Complete  
**Date:** 2026-05-22  
**Purpose:** Implement Cashfree webhook support with signature verification, duplicate event protection, conflict prevention, and idempotent transition handling.

---

## 1. Webhook Endpoint Configuration

### Route Details
| Route Name | Method | Path | Controller Action | CSRF Protection | Authentication |
|------------|--------|------|-------------------|-----------------|----------------|
| `webhooks.cashfree` | `POST` | `/webhooks/cashfree` | `Webhooks\CashfreeWebhookController` | Exempt | None (Signature Verified) |

### CSRF Exemption
The route falls under the `/webhooks/*` pattern, which is configured in `bootstrap/app.php` to bypass CSRF verification:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'webhooks/*',
    ]);
})
```

---

## 2. Signature Verification

Cashfree secures webhooks using an HMAC-SHA256 signature. The signature is computed using:
- The raw request payload body.
- The value of the `x-webhook-timestamp` header.
- The webhook secret (`CASHFREE_WEBHOOK_SECRET`) as the hashing key.

### Calculation Algorithm
```php
$signaturePayload = $timestamp . $rawBody;
$computedSignature = base64_encode(hash_hmac('sha256', $signaturePayload, $webhookSecret, true));
```

### Signature Headers Supported
The implementation checks for the signature in the following headers in order of preference:
1. `x-webhook-signature`
2. `x-cf-signature`
3. `x-cashfree-signature`

If any signature header matches the computed signature, the webhook payload is deemed authentic.

---

## 3. Webhook Event Types & Status Mapping

Only specific webhook events are mapped to internal payment actions. Any other event types are ignored with a `200 OK` response.

| Webhook `type` | Description | Internal Payment Status |
|----------------|-------------|-------------------------|
| `PAYMENT_SUCCESS_WEBHOOK` | The transaction was completed successfully. | `PAID` |
| `PAYMENT_FAILED_WEBHOOK` | The transaction failed on the gateway side. | `FAILED` |
| `PAYMENT_USER_DROPPED_WEBHOOK` | The user abandoned the payment session. | `FAILED` |

---

## 4. Safety & Idempotency Rules

To prevent race conditions, double-processing, and state corruption, the following guards are implemented in `PaymentWebhookService`:

### A. Conflict Protection (Order ID Matching)
The webhook payload contains `data.order.order_id` (e.g. `ECCPAY123`). The system checks that the payment's `gateway_order_id` matches the webhook payload order ID. If they do not match, the request is rejected with a `400 Bad Request` or skipped to prevent cross-payment state corruption.

### B. Duplicate Event Protection
All processed webhook events are recorded in the `payment_events` table:
- **Identifier**: `gateway_event_id` is mapped from the webhook's `event_id` field.
- **Action**: Before processing, the system queries `payment_events` for the incoming `gateway_event_id` with `gateway = 'cashfree'`. If a matching event exists, processing is skipped (idempotent success).

### C. Status Transition Guard
- **No Redundant Transitions**: If a payment is already marked as `PAID` or `FAILED`, subsequent webhooks will not attempt to re-execute transition actions.
- **No Downgrades**: Once a payment is `PAID`, it can never transition to `FAILED` or `PENDING` via webhooks.
- **No Upgrade from Failed**: Once a payment is marked as `FAILED`, it cannot be updated to `PAID` via webhook.

---

## 5. Directory Structure & Key Files

- **Controller**: [CashfreeWebhookController.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Http/Controllers/Webhooks/CashfreeWebhookController.php)
- **Service**: [PaymentWebhookService.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Services/Payments/PaymentWebhookService.php)
- **Gateway**: [CashfreeGateway.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Services/Payments/Gateways/CashfreeGateway.php)
- **Feature Tests**: [CashfreeGatewayPhase5Test.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/tests/Feature/CashfreeGatewayPhase5Test.php)

---

## 6. How to Set Up in Cashfree Dashboard

1. Log in to the [Cashfree Merchant Dashboard](https://merchant.cashfree.com/).
2. Navigate to **Payment Gateway** > **Developers** > **Webhooks**.
3. Under the **Webhooks** section, click **Add Webhook**.
4. Set the **Webhook URL** to: `https://your-domain.com/webhooks/cashfree`.
5. Select the following event groups:
   - **Payment Events** (Specifically `Payment Success`, `Payment Failed`, and `User Dropped`).
6. Copy the generated **Webhook Secret** and set it in your `.env` file:
   ```env
   CASHFREE_WEBHOOK_SECRET=your_webhook_secret_here
   ```
