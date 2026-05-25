# Payment — Cashfree Phase 2: Gateway Shell

**Status:** Phase 2 Complete  
**Date:** 2026-05-22  
**Purpose:** Introduce `CashfreeGateway` as a safe shell implementing `PaymentGatewayInterface` without making any real Cashfree API calls. Lays the groundwork for Phase 3 order/session creation.

---

## 1. What Was Added

| Item | Details |
|------|---------|
| `CashfreeGateway` | New class at `app/Services/Payments/Gateways/CashfreeGateway.php` |
| `config/payments.php` | Cashfree block with `driver`, `enabled`, all credential keys |
| `.env.example` | All Cashfree placeholder keys with `sandbox` mode default |
| `enabled` flag | Added to both Razorpay and Cashfree gateway config |
| Guard refactor | Both checkout controllers now check `enabled` flag instead of `null` driver class |
| `PaymentManager` | Removed stale Razorpay-only hardcoded driver fallback |
| `PaymentManager` | Detects `not implemented until Phase` RuntimeExceptions for specific `_not_implemented` failure codes |
| Tests | `tests/Feature/CashfreeGatewayPhase2Test.php` with 22 test cases |

---

## 2. Cashfree Environment Keys

Add to your `.env` file (use placeholder values in development):

```dotenv
# Cashfree API Credentials (Phase 2 shell — not active until Phase 3)
CASHFREE_ENABLED=false
CASHFREE_CLIENT_ID=
CASHFREE_CLIENT_SECRET=
CASHFREE_WEBHOOK_SECRET=
CASHFREE_MODE=sandbox
CASHFREE_API_VERSION=2023-08-01
CASHFREE_RETURN_URL=
CASHFREE_NOTIFY_URL=
```

> **Note:** `CASHFREE_MODE` uses `sandbox` (not `test`) to align with Cashfree's own terminology. Both `sandbox` and `live` are accepted. Existing environments using `test` will gracefully receive `sandbox` mode behaviour from application code.

---

## 3. Cashfree Config Structure (`config/payments.php`)

```php
'cashfree' => [
    'driver'         => \App\Services\Payments\Gateways\CashfreeGateway::class,
    'enabled'        => env('CASHFREE_ENABLED', false),  // false until Phase 3
    'client_id'      => env('CASHFREE_CLIENT_ID'),
    'client_secret'  => env('CASHFREE_CLIENT_SECRET'),
    'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
    'mode'           => env('CASHFREE_MODE', 'sandbox'),
    'api_version'    => env('CASHFREE_API_VERSION', '2023-08-01'),
    'return_url'     => env('CASHFREE_RETURN_URL'),
    'notify_url'     => env('CASHFREE_NOTIFY_URL'),
],
```

For symmetry, Razorpay also has:

```php
'razorpay' => [
    ...
    'enabled' => env('RAZORPAY_ENABLED', true),
    ...
],
```

---

## 4. CashfreeGateway Methods

All methods are implemented as required by `PaymentGatewayInterface`.

| Method | Status | Phase |
|--------|--------|-------|
| `gatewayName()` | ✅ Implemented | Phase 2 |
| `createOrder()` | 🔶 Placeholder | Phase 3 |
| `verifyPayment()` | 🔶 Placeholder | Phase 4 |
| `fetchPayment()` | 🔶 Placeholder | Phase 4 |
| `handleWebhook()` | 🔶 Placeholder | Phase 5 |
| `extractIdentifiers()` | ✅ Implemented (safe parser) | Phase 2 |

---

## 5. Placeholder Methods

### createOrder()
```php
throw new RuntimeException(
    'Cashfree order/session creation is not implemented until Phase 3.'
);
```

### verifyPayment()
```php
throw new RuntimeException(
    'Cashfree payment verification is not implemented until Phase 4.'
);
```

### fetchPayment()
```php
throw new RuntimeException(
    'Cashfree payment fetch is not implemented until Phase 4.'
);
```

### handleWebhook()
```php
throw new RuntimeException(
    'Cashfree webhook handling is not implemented until Phase 5.'
);
```

**Why throw instead of returning a failed `PaymentResult`?**  
Throwing a `RuntimeException` makes the "not implemented" status explicit and gives developers an immediate, unambiguous signal. `PaymentManager` and `PaymentWebhookService` both catch `RuntimeException` from driver methods and handle them gracefully — marking payments as failed and logging audit events respectively.

---

## 6. No External Cashfree API Calls

Phase 2 contains **zero** external HTTP requests to Cashfree. The `CashfreeGateway` class does not use `Http::` or any Cashfree SDK. This is intentional to:

- Keep the Phase 2 shell safe to deploy to any environment
- Avoid credential validation concerns
- Prevent accidental integration with Cashfree's sandbox or live APIs
- Allow the driver class to be registered and resolvable without risk

---

## 7. How PaymentManager Resolves Cashfree Now

```php
app(PaymentManager::class)->getGateway('cashfree');
// Returns: CashfreeGateway instance (✅ no longer throws "driver not implemented")
```

```php
$gateway->createOrder($payment);
// Throws: RuntimeException("Cashfree order/session creation is not implemented until Phase 3.")
```

`PaymentManager::initiatePayment()` catches this and returns:
```php
[
    'payment'  => $payment, // status = 'failed', failure_code = 'cashfree_not_implemented'
    'result'   => [...],
    'checkout' => null,
]
```

---

## 8. Why Cashfree Is Not Yet Exposed to Users

The `enabled` flag guards all checkout entry points:

- `MembershipUpgradeController::upgrade()` — checks `config('payments.gateways.cashfree.enabled')` → returns 422
- `Shop\CheckoutController::placeOrder()` — same check → returns 422

Response when `payment_gateway=cashfree` is sent before Phase 3:

```json
{
  "success": false,
  "message": "Selected payment gateway is not available yet.",
  "errors": {
    "payment_gateway": ["Selected payment gateway is not available yet."]
  }
}
```

This guard is **declarative** — simply setting `CASHFREE_ENABLED=true` in Phase 3 will unlock the gateway without code changes to controllers.

---

## 9. What Phase 3 Will Implement

- Create Cashfree order/session via `POST /pg/orders` Cashfree API
- Store `cf_order_id` and `payment_session_id` in the `payments` table
- Return a `checkout` payload with the payment session token for the mobile SDK
- Return URL and notification URL handling

Phase 3 will require:
1. `CASHFREE_ENABLED=true` in `.env`
2. Real `CASHFREE_CLIENT_ID` and `CASHFREE_CLIENT_SECRET` credentials
3. Replace `CashfreeGateway::createOrder()` with real Cashfree API implementation

---

## 10. How to Test Gateway Resolution

```bash
# Clear caches first
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Run Phase 2 tests
php artisan test --filter=CashfreeGatewayPhase2

# Run all payment tests
php artisan test --filter=Payment
php artisan test --filter=Razorpay
```

To verify in Tinker:

```php
php artisan tinker

// Should return CashfreeGateway instance
app(App\Services\Payments\PaymentManager::class)->getGateway('cashfree');

// Should return 'cashfree'
app(App\Services\Payments\Gateways\CashfreeGateway::class)->gatewayName();

// Should still return RazorpayGateway instance
app(App\Services\Payments\PaymentManager::class)->getGateway('razorpay');
```

---

## 11. Razorpay Regression Confirmation

Razorpay remains fully operational after Phase 2:

- ✅ `config/payments.php` Razorpay block unchanged (only `enabled` key added with default `true`)
- ✅ `RazorpayGateway` class unchanged
- ✅ `PaymentManager::getGateway('razorpay')` resolves to `RazorpayGateway`
- ✅ Razorpay checkout flow unaffected (enabled flag defaults to `true`)
- ✅ Razorpay webhook handling unchanged
- ✅ `RAZORPAY_ENABLED` defaults to `true` in both `.env.example` and config

---

## 12. Risks Before Phase 3

| Risk | Mitigation |
|------|-----------|
| Developer forgets `CASHFREE_ENABLED=false` and accidentally enables Cashfree | Gateway method throws immediately; no Cashfree API call is ever made in Phase 2 |
| Checkout internally bypasses controller guard | `PaymentManager` marks the payment as `failed` with `cashfree_not_implemented` code |
| Old controller code had `driver == null` guard | Replaced with `enabled` flag guard — more robust for any future disabled gateway |
| Webhook route for `/webhooks/cashfree` receives traffic | `PaymentWebhookService` catches the `RuntimeException` from `handleWebhook()` and logs it safely without processing |
