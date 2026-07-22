# ECC Payment Gateway-Neutral Refactor Summary (Pre-Cashfree)

This document provides a summary of the Phase 1 refactoring of the Executive Club Cricket (ECC) payment system. The goal was to establish a gateway-neutral architecture that preserves full support for the active **Razorpay** integration while preparing the system to support a second gateway (**Cashfree**).

---

## 1. Structural Changes Overview

The refactoring addresses the coupling issues identified in the Cashfree readiness audit. All core checkout, upgrade, and payment retrieval flows are now gateway-agnostic.

### A. Core Codebase Decoupling
* **`PaymentGatewayInterface.php`**: Extended the interface to include the `extractIdentifiers(array $payload): array` method. This delegates webhook payload parsing to the respective gateway driver instead of coupling the webhook service to a specific payload structure.
* **`RazorpayGateway.php`**: Implemented `extractIdentifiers()` to parse nested payloads and fall back to top-level arrays if needed (ensuring compatibility with test mocks).
* **`PaymentManager.php`**: Removed hardcoded `RazorpayGateway` dependency injection from the constructor. Drivers are resolved dynamically from the service container (`app($driverClass)`) using configuration maps. Unsupported gateways throw `InvalidArgumentException`, and configured but unimplemented drivers throw a `RuntimeException`.
* **`PaymentWebhookService.php`**: Refactored payload extraction. Instead of digging into Razorpay-specific paths directly, it now delegates identifier extraction to the resolved gateway driver.

### B. Configuration Structure
Added driver mappings inside `config/payments.php`. Gateways now define their own implementation driver class string:
```php
'gateways' => [
    'razorpay' => [
        'driver' => \App\Services\Payments\Gateways\RazorpayGateway::class,
        // ... razorpay credentials
    ],
    'cashfree' => [
        'driver' => null, // Configured but unimplemented for now
        // ... cashfree credentials
    ],
]
```

### C. Generic Web Payment Routing
* **`GenericPaymentController.php` [NEW]**: Created to handle dynamic redirection of payment attempts based on `$payment->gateway`.
  * `GET /payments/{payment}/pay` resolves the gateway driver and redirects to the appropriate payment view or gateway flow.
  * `GET /payments/{payment}/retry` verifies the user, re-initiates the payment using `PaymentManager::initiatePayment()`, and redirects to the new payment view.
* **`routes/web.php`**: Registered the generic `payments.pay` and `payments.retry` routes. Existing Razorpay-specific routes are preserved for backward compatibility.
* **`failed.blade.php`**: Updated the retry link to point to the generic `payments.retry` route rather than hardcoding the Razorpay-specific endpoint.

### D. Storefront Checkout and Membership Wizard Alignment
* **`CheckoutPage.php`**: Leverages the default configuration gateway (`config('payments.default_gateway')`), validates driver serviceability, and redirects to the generic `payments.pay` web route.
* **`Payment.php` & `Step7Payment.php`**: Refactored membership payment Livewire components to fetch the default gateway from config dynamically and direct users to `payments.pay`.
* **API Endpoints (`CheckoutController.php` & `MembershipUpgradeController.php`)**:
  * Accept an optional `payment_gateway` input parameter (fallback to default config).
  * Validate gateway name against `config('payments.supported_gateways')`.
  * Reject unimplemented drivers (e.g. `cashfree`) with a clean 422 validation response: `"Selected payment gateway is not available yet."`

---

## 2. Webhook Extraction Interface Details

Below is the signature added to the gateway contract:

```php
/**
 * Extract internal payment ID and gateway order ID from webhook payload.
 *
 * @param array $payload
 * @return array{
 *     internal_payment_id: ?int,
 *     gateway_order_id: ?string,
 *     gateway_payment_id: ?string,
 *     gateway_event_id: ?string,
 *     event_type: ?string
 * }
 */
public function extractIdentifiers(array $payload): array;
```

This design keeps the core `PaymentWebhookService` completely free of JSON nesting differences between Razorpay and Cashfree, facilitating clean, low-risk integration of Cashfree's payload format in the next phase.

---

## 3. Verification & Test Execution Results

All verification targets have passed successfully.

### Command Execution Log Summary

1. **`CheckoutPaymentTest`**
   ```bash
   vendor/bin/phpunit --filter CheckoutPaymentTest
   
   OK (4 tests, 18 assertions)
   ```
   *Verified order creation in `pending_payment` status, mock Razorpay order endpoint response, signature validation, and subsequent finalization transition to `paid`.*

2. **`PaymentsFoundationTest`**
   ```bash
   vendor/bin/phpunit --filter PaymentsFoundationTest
   
   OK (5 tests, 27 assertions)
   ```
   *Verified core payment ledger transitions, amount formatting, and order/upgrade states.*

3. **`RazorpayGatewayTest`**
   ```bash
   vendor/bin/phpunit --filter RazorpayGatewayTest
   
   OK (12 tests, 43 assertions)
   ```
   *Verified payload structure validations, order creation, signature checking, and error mapping for Razorpay client transactions.*

4. **`PaymentsServiceLayerTest`**
   ```bash
   vendor/bin/phpunit --filter PaymentsServiceLayerTest
   
   OK (9 tests, 48 assertions)
   ```
   *Verified that service layer classes interact cleanly using polymorphic types.*

5. **`PaymentsPhase8Test`**
   ```bash
   vendor/bin/phpunit --filter PaymentsPhase8Test
   
   OK (10 tests, 54 assertions)
   ```
   *Verified comprehensive end-to-end integration and lifecycle.*

---

## 4. Next Steps for Phase 2 (Cashfree Integration)

With the codebase successfully decoupled, the Cashfree integration can be executed safely by:
1. Creating the `CashfreeGateway` implementing `PaymentGatewayInterface`.
2. Setting `'driver' => \App\Services\Payments\Gateways\CashfreeGateway::class` under `config/payments.php` for `'cashfree'`.
3. Creating the Cashfree controller endpoints (`pay`, `verify`, and webhook endpoints).
4. Updating storefront/wizard components to support UI toggles between Razorpay and Cashfree.
