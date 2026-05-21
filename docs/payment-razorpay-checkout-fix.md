# Razorpay Checkout Fix — Root Cause & Resolution

## Root Cause

### Primary Bug: Meta Key Mismatch in `PaymentLedgerService::markPending()`

**File:** `app/Services/Payments/PaymentLedgerService.php`

`RazorpayGateway::createOrder()` returns a `PaymentResult` with a `checkout` array containing the Razorpay-specific payload (`key`, `amount`, `order_id`, etc.).

`PaymentManager::initiatePayment()` then calls:
```php
$payment = $this->ledger->markPending($payment, $result->checkout);
```

`PaymentLedgerService::markPending()` was calling `mergeMeta($payment, $meta)` which merges the checkout array **flat** into the payment's `meta` column:
```json
// Incorrect — flat merge:
{ "gateway": "razorpay", "key": "rzp_test_...", "amount": 50000, "order_id": "order_xxx" }
```

But `RazorpayPaymentController::pay()` reads:
```php
$checkoutData = $payment->meta['checkout'] ?? null;
```

Because there was **no `checkout` key**, `$checkoutData` was always `null`, causing the controller to redirect to `/payments/failed` with "Payment configuration missing."

### Secondary Issues
1. Checkout page displayed mock Saved Cards (•••• 4242, •••• 5555) and Apple Pay/Google Pay wallets — dead-end UI that confused users.
2. Dismissing the Razorpay modal caused a hard redirect to `/payments/failed` (alarming UX).

---

## Fix Applied

### 1. `PaymentLedgerService::markPending()` — Wrap under `checkout` key
```php
// Before:
'meta' => $this->mergeMeta($payment, $meta),

// After:
$wrappedMeta = !empty($meta) ? ['checkout' => $meta] : [];
'meta' => $this->mergeMeta($payment, $wrappedMeta),
```
Now `$payment->meta['checkout']` correctly resolves to the Razorpay checkout payload.

### 2. `CheckoutPage.php` — Remove mock payment data
Removed `$savedPaymentMethods`, `$walletOptions`, and `handleAddPaymentMethod()`. Added debug logs around `placeOrder()`.

### 3. `checkout.blade.php` — Replace mock UI with Razorpay card
Replaced Saved Cards / Digital Wallets sections with a single "Razorpay Secure Checkout" card with TEST MODE badge.

### 4. `razorpay.blade.php` — Friendly dismiss behavior
Added three panels: Loading (auto-opens popup), Dismissed (friendly retry), Verifying (backend confirm spinner). Modal dismiss no longer hard-redirects to failed page.

### 5. `RazorpayPaymentController.php` — Added debug logging
Added `Log::info/warning/error` calls at each key step for traceability.

---

## Files Modified

| File | Change |
|------|--------|
| `app/Services/Payments/PaymentLedgerService.php` | **Critical fix** — wrap checkout data under `checkout` key |
| `app/Livewire/Shop/CheckoutPage.php` | Remove mock payment state, add logs |
| `resources/views/livewire/shop/checkout.blade.php` | Replace mock UI with Razorpay card |
| `resources/views/shop/payment/razorpay.blade.php` | Three-panel UX, friendly dismiss |
| `app/Http/Controllers/Web/Payment/RazorpayPaymentController.php` | Debug logs, payment_id in failed redirects |
| `docs/payment-razorpay-checkout-fix.md` | This file |

---

## Payment Flow (After Fix)

```
1. /checkout → User selects address → clicks "Place Order"
2. CheckoutPage::placeOrder()
   → CheckoutService::placeOrder() → creates ShopOrder (status: pending_payment)
   → PaymentManager::initiatePayment()
     → PaymentLedgerService::createPayment() → creates Payment (status: initiated)
     → RazorpayGateway::createOrder() → calls Razorpay API → gets order_id
     → PaymentLedgerService::markPending() → saves {checkout: {...razorpay payload...}} in meta
   → redirect to /payments/razorpay/{payment}/pay

3. RazorpayPaymentController::pay()
   → reads $payment->meta['checkout'] ✓
   → renders razorpay.blade.php

4. Razorpay Checkout popup auto-opens

5. User completes payment → Razorpay calls handler()
   → JS POSTs to /payments/razorpay/verify

6. RazorpayPaymentController::verify()
   → PaymentManager::verifyPayment() → RazorpayGateway::verifyPayment() → HMAC check
   → PaymentLedgerService::markPaid()
   → PaymentFinalizationService::finalizePaidPayment()
     → OrderPaymentService::finalizePaidOrder()
     → CheckoutService::confirmPayment() → marks order paid
   → returns {success: true, redirect_url: /order-success/{id}}

7. JS redirects to success page ✓
```

---

## Test Credentials

```
UPI Success: success@razorpay
UPI Failure: failure@razorpay
Test Card: 4111 1111 1111 1111 / Any future date / Any CVV
```
