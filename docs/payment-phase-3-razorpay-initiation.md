# ECC Payment Phase 3: Razorpay Payment Initiation

This document describes the design, implementation, and verification of **Phase 3: Razorpay Payment Initiation** for the Executive Cricket Club (ECC) Laravel 11 project.

---

## 1. Overview of Phase 3

In Phase 3, we have successfully implemented server-side Razorpay order creation and payment initiation end-to-end at the service layer, maintaining complete gateway-neutral architecture. When a user initiates a checkout sequence:
1. An internal `Payment` ledger row is created first with status `initiated`.
2. A Razorpay order is requested server-side using the `RazorpayGateway` adapter.
3. The generated `gateway_order_id` is persisted to the ledger, and the status transitions to `pending`.
4. A normalized, secure checkout configuration payload is returned, ready for web (Livewire) or mobile (JWT API) consumption.

---

## 2. Key Architecture Details

### A. How PaymentManager Initiates Payments
All polymorphic initiation workflows go through `App\Services\Payments\PaymentManager::initiatePayment()`. 
- **Nullable Contexts**: The `$payable` model argument is nullable to support isolated tests and arbitrary payable instances.
- **Ledger First**: The payment ledger entry is persisted *before* reaching out to Razorpay.
- **Resilient Error Handling**: The `initiatePayment()` method is wrapped in a `try/catch` block. If the API fails, the ledger entry is marked `failed` and a normalized failed array is returned. If config credentials are missing, it rethrows a standard `RuntimeException` to alert developers.

### B. How RazorpayGateway Creates Orders
`App\Services\Payments\Gateways\RazorpayGateway` acts as a clean adapter under the `PaymentGatewayInterface`.
- **Laravel Http Client**: We use the native `Http` facade to perform Basic Auth requests against `https://api.razorpay.com/v1/orders`. This avoids importing fat third-party SDK dependencies.
- **Secure Prefill**: Prefills customer details gracefully. If contact numbers are missing, it falls back safely without crashing.
- **Audit References**: The receipt is formatted as `'ecc_payment_' . $payment->id`, linking every gateway transaction back to the database.

---

## 3. Paise Conversion & Gateway Validations

- **Integer Conversion**: Internally, amounts are stored as decimal Rupees (e.g. `500.00`). Razorpay requires amounts in paise (cents). The gateway converts this using:
  ```php
  $amountInPaise = (int) round(((float) $payment->amount) * 100);
  ```
- **Gateway-Level Validations**: Before making external requests, `RazorpayGateway` validates:
  1. The payment amount must be greater than zero.
  2. The currency must be `INR` (Razorpay default).
  If any validation fails, a structured `InvalidArgumentException` is thrown.

---

## 4. Required Configuration & Environment Keys

Ensure your `.env` contains the following keys (without real credentials in committed source files):

```env
PAYMENT_DEFAULT_GATEWAY=razorpay
PAYMENT_DEFAULT_CURRENCY=INR

RAZORPAY_KEY_ID=your_key_id_here
RAZORPAY_KEY_SECRET=your_key_secret_here
RAZORPAY_WEBHOOK_SECRET=your_webhook_secret_here
RAZORPAY_MODE=test
```

The configuration is mapped inside `config/payments.php` as:
```php
'razorpay' => [
    'key_id' => env('RAZORPAY_KEY_ID'),
    'key_secret' => env('RAZORPAY_KEY_SECRET'),
    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    'mode' => env('RAZORPAY_MODE', 'test'),
]
```

---

## 5. Normalized Checkout Structure

The checkout payload returned from `RazorpayGateway::createOrder()` is standardized and fully compatible with both web and mobile clients:

```json
{
  "gateway": "razorpay",
  "key": "rzp_test_key_123",
  "amount": 10000,
  "display_amount": 100.00,
  "currency": "INR",
  "order_id": "order_ABC123xyz",
  "internal_payment_id": 42,
  "name": "Executive Cricket Club",
  "description": "Executive Cricket Club - Shop Order",
  "prefill": {
    "name": "Jane Doe",
    "email": "jane@example.com",
    "contact": "9876543210"
  },
  "notes": {
    "internal_payment_id": 42,
    "payment_id": 42,
    "purpose": "shop_order"
  }
}
```

> [!CAUTION]
> **Zero Exposure**: `RAZORPAY_KEY_SECRET` is never exposed to the frontend checkout payload, mobile APIs, or command prints. Only `key_id` (`key` field) is returned.

---

## 6. Safety & Settlement Status

To keep our architecture highly decoupled:
1. **No Automatic Marking Paid**: The payment status remains `pending`. No vault item, shop order, or membership application is marked as paid or settled in this phase.
2. **No Inventory Reduction**: No stock is modified during initiation.
3. **Pristine Dummy Flow**: The existing mock/dummy checkout flow has not been replaced yet; this implementation serves as the service-layer plumbing that will be wired in Phase 4.

---

## 7. Developer Verification (Artisan Command)

You can safely trigger and test Razorpay payment initiation from the command line:

```bash
php artisan payments:test-razorpay-initiation {amount=100}
```

### Example Command Output:
```
==================================================================
🚀 INITIATING TEST RAZORPAY PAYMENT OF INR 150
==================================================================
👤 Using first user for prefill: John Doe <john@example.com>
✅ Internal Payment ledger record created successfully!

+----------------------+------------------------------+
| Attribute            | Value                        |
+----------------------+------------------------------+
| Internal Payment ID  | 1                            |
| Gateway Order ID     | order_K3HsaK2ksJD92          |
| Amount               | 150.00 INR                   |
| Status               | pending                      |
| Checkout Key ID      | rzp_test_key_123             |
| Checkout Amount      | 15000 paise                  |
| Checkout Description | Executive Cricket Club - Test|
+----------------------+------------------------------+
==================================================================
🎉 Razorpay checkout payload is fully verified and stable!
==================================================================
```

---

## 8. Next Steps (Phase 4 and Beyond)

With Phase 3 initiation verified, subsequent phases will implement:
- **Web Checkout Integration**: Replacing the Livewire dummy payment component with the active Razorpay checkout window.
- **Backend Verification API**: A secure endpoint to parse client `order_id`, `payment_id`, and `signature` to verify SHA256 HMAC integrity.
- **Polymorphic Settlement**: Once verified, transition individual modules (orders, memberships) to `paid` status, reduce inventory, and record ledger audits.
- **Webhook confirmation processing** for off-band state synchronization.
