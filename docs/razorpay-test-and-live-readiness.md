# Razorpay — Test & Live Readiness Guide

> **ECC Project** | Last updated: 2026-05-21

---

## Current Status

| Item | Status |
|------|--------|
| Razorpay Standard Checkout | ✅ Implemented |
| Server-side order creation | ✅ Implemented |
| Signature verification (HMAC-SHA256) | ✅ Implemented |
| Tokenization / saved-card disabled | ✅ `remember_customer: false` |
| UPI test payments | ✅ Working |
| Webhook endpoint | ✅ Ready (not yet wired in dashboard) |
| Test mode helper on payment page | ✅ Shown when `RAZORPAY_MODE=test` |
| Live mode readiness | ✅ Config-driven — `.env` swap only |

---

## Testing Payments Right Now

### ✅ Recommended: UPI Test Payments

UPI is the most reliable test payment method on Razorpay test accounts.

**1. Open the Razorpay Checkout popup**
   — It auto-opens when you reach `/payments/razorpay/{id}/pay`.

**2. Select "UPI" from the payment options.**

**3. Enter one of these test UPI IDs:**

| Test UPI ID | Expected Result |
|-------------|----------------|
| `success@razorpay` | Payment succeeds → order marked paid |
| `failure@razorpay` | Payment fails → order stays unpaid |

**4. Click "Pay Now".**

The test-mode helper box is shown directly on the payment page when `RAZORPAY_MODE=test`.

---

### ⚠️ Why Card Payments May Fail in Test Mode

Razorpay test accounts have restrictions on card tokenization, international cards, and 3DS flows:

- Tokenization (saved cards) is now **explicitly disabled** via `remember_customer: false`.
- Test cards like `4111 1111 1111 1111` may still fail depending on your Razorpay test account configuration.
- If a card payment fails in test mode, **use UPI `success@razorpay` instead**.
- In live mode with real cards, these restrictions do not apply.

---

## What Works Without Webhook Dashboard Access

The **normal payment flow** does **not** require webhooks:

```
Razorpay Checkout (popup)
  → User pays
  → Razorpay calls handler() in JavaScript
  → Frontend POSTs to POST /payments/razorpay/verify
  → Backend verifies HMAC signature
  → Backend marks payment paid
  → Backend marks order paid
  → User redirected to success page
```

✅ This complete flow works **right now** without any webhook configuration.

---

## What Cannot Be Fully Tested Without Webhook URL

Webhooks are only triggered **automatically** when the webhook URL is configured in the Razorpay dashboard. Until then:

| Scenario | Works without webhook? |
|----------|------------------------|
| Normal payment via Checkout popup | ✅ Yes (via verify endpoint) |
| Payment marked paid when user closes browser after paying | ❌ No (webhook needed) |
| Razorpay `payment.captured` event received | ❌ No (webhook needed) |
| Razorpay `payment.failed` event received | ❌ No (webhook needed) |
| Auto-retry on partial capture | ❌ No (webhook needed) |

---

## Manual Webhook Testing with Postman (Without Dashboard Access)

You can manually simulate Razorpay webhook calls to test the `/webhooks/razorpay` endpoint.

**Endpoint:** `POST https://your-domain.com/webhooks/razorpay`

**Required headers:**
```
Content-Type: application/json
X-Razorpay-Signature: <generated_hmac>
```

**Generate X-Razorpay-Signature:**

In PHP (run locally):
```php
$webhookSecret = env('RAZORPAY_WEBHOOK_SECRET'); // from your .env
$rawBody       = json_encode($payload);           // exact JSON string to send
$signature     = hash_hmac('sha256', $rawBody, $webhookSecret);
echo $signature;
```

**Sample payload — payment.captured:**
```json
{
  "event": "payment.captured",
  "payload": {
    "payment": {
      "entity": {
        "id": "pay_test_xxxxxx",
        "order_id": "order_test_xxxxxx",
        "status": "captured"
      }
    }
  }
}
```

> ⚠️ The `RAZORPAY_WEBHOOK_SECRET` must be set in `.env` for webhook signature verification to pass. Leave it empty for now if not testing webhooks.

---

## Razorpay Checkout Options Used

```javascript
{
    "key":              "<RAZORPAY_KEY_ID>",   // public key only — secret never in JS
    "amount":           <paise>,               // server-side, from Payment.amount × 100
    "currency":         "INR",                 // from Payment.currency
    "name":             "Executive Club Cricket",
    "description":      "<order description>",
    "order_id":         "<gateway_order_id>",  // created server-side via Razorpay API
    "prefill":          { name, email, contact },
    "notes":            { internal_payment_id, purpose },
    "theme":            { "color": "#d4af37" },

    // Tokenization / saved-card: DISABLED
    "remember_customer": false,

    "modal": {
        "escape":       true,
        "backdropclose": false   // prevent accidental dismiss
    }
}
```

**Key security properties:**
- `RAZORPAY_KEY_SECRET` is **never** sent to the browser, JS, or any page source
- Payment/order is only marked paid after **backend HMAC signature verification**
- `gateway_order_id` is stored server-side and used for verification — not trusted from client

---

## Live Mode Readiness Checklist

Follow these steps when you are ready to go live:

### Pre-requisites
- [ ] Client has completed Razorpay KYC and account is activated
- [ ] Live credentials are available from the Razorpay dashboard

### Steps

**1. Update `.env` with live credentials:**
```dotenv
RAZORPAY_KEY_ID=rzp_live_xxxxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxxxxxxxxxxxxxxxxxx
RAZORPAY_WEBHOOK_SECRET=your_live_webhook_secret
RAZORPAY_MODE=live
```

**2. Set payment gateway mode:**
```dotenv
PAYMENT_DEFAULT_GATEWAY=razorpay
PAYMENT_DEFAULT_CURRENCY=INR
```

**3. Configure the webhook URL in Razorpay Dashboard:**
- Go to: Dashboard → Settings → Webhooks → Add New Webhook
- URL: `https://your-live-domain.com/webhooks/razorpay`
- Secret: same value as `RAZORPAY_WEBHOOK_SECRET`
- Enable events:
  - ✅ `payment.captured`
  - ✅ `payment.failed`
  - ✅ `order.paid`

**4. Clear Laravel caches:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

**5. Verify the test-mode helper is gone:**
- The UPI test helper box on the payment page is only shown when `RAZORPAY_MODE=test` or app is in non-production environment.
- In production with `RAZORPAY_MODE=live`, it is automatically hidden.

**6. Test one small real payment:**
- Place a minimal real order
- Complete payment with a real card or UPI
- Confirm in Razorpay dashboard: payment captured
- Confirm in ECC admin: order payment_status = paid
- Confirm `gateway_payment_id` saved in DB

---

## Architecture — No Business Logic Coupling

Razorpay logic is **only** in:

| Layer | File |
|-------|------|
| Gateway adapter | `app/Services/Payments/Gateways/RazorpayGateway.php` |
| Payment orchestration | `app/Services/Payments/PaymentManager.php` |
| Ledger / state transitions | `app/Services/Payments/PaymentLedgerService.php` |
| Web controller (checkout flow) | `app/Http/Controllers/Web/Payment/RazorpayPaymentController.php` |
| Webhook controller | `app/Http/Controllers/Webhooks/RazorpayWebhookController.php` |
| Payment page view | `resources/views/shop/payment/razorpay.blade.php` |

**Domain services** (`OrderPaymentService`, `CheckoutService`, `VaultDeliveryPaymentService`, `MembershipPaymentService`) contain **zero Razorpay-specific code**. Swapping to Cashfree only requires implementing `CashfreeGateway` and updating `.env`.

---

## Retry Behaviour

| Scenario | Behaviour |
|----------|-----------|
| User closes Razorpay modal | "Payment Not Completed" panel shown; order stays unpaid |
| User clicks "Retry Payment" | Same Razorpay popup reopened (same order, no duplicate) |
| User clicks "Retry Payment" on failed page | New `Payment` record created for same order; new Razorpay order created |
| Order already paid | Retry redirects to success page immediately |

---

## Related Files

- [`app/Services/Payments/PaymentLedgerService.php`](../app/Services/Payments/PaymentLedgerService.php) — Critical meta key fix (checkout stored under `meta['checkout']`)
- [`app/Services/Payments/Gateways/RazorpayGateway.php`](../app/Services/Payments/Gateways/RazorpayGateway.php) — Razorpay API calls, HMAC verification
- [`resources/views/shop/payment/razorpay.blade.php`](../resources/views/shop/payment/razorpay.blade.php) — Payment page
- [`docs/payment-razorpay-checkout-fix.md`](./payment-razorpay-checkout-fix.md) — Original root cause analysis
