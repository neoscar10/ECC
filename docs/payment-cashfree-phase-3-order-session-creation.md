# Payment — Cashfree Phase 3: Order / Payment Session Creation

**Status:** Phase 3 Complete  
**Date:** 2026-05-22  
**Purpose:** Implement `CashfreeGateway::createOrder()` to call the Cashfree Create Order API and return a `payment_session_id` for frontend/mobile SDK checkout initiation.

---

## 1. Cashfree Create Order Endpoint Used

| Field | Value |
|-------|-------|
| Method | `POST` |
| Sandbox URL | `https://sandbox.cashfree.com/pg/orders` |
| Live URL | `https://api.cashfree.com/pg/orders` |

---

## 2. Sandbox vs Live Base URLs

Mode is determined by `CASHFREE_MODE` env variable.

| Mode value | Base URL used |
|------------|--------------|
| `sandbox` | `https://sandbox.cashfree.com/pg` |
| `test` | `https://sandbox.cashfree.com/pg` |
| `local` | `https://sandbox.cashfree.com/pg` |
| `live` | `https://api.cashfree.com/pg` |
| `production` | `https://api.cashfree.com/pg` |

Only `live` and `production` use the production endpoint. Everything else uses sandbox.

---

## 3. Required Environment Keys

```dotenv
CASHFREE_ENABLED=true           # Must be true to allow checkout to select cashfree
CASHFREE_CLIENT_ID=             # Your Cashfree App ID
CASHFREE_CLIENT_SECRET=         # Your Cashfree Secret Key (backend only, NEVER exposed)
CASHFREE_MODE=sandbox           # sandbox or live
CASHFREE_API_VERSION=2023-08-01 # Cashfree API version header
CASHFREE_RETURN_URL=            # Optional: return URL after payment
CASHFREE_NOTIFY_URL=            # Optional: webhook notification URL
```

---

## 4. Cashfree Request Payload Shape

```json
{
  "order_id": "ECCPAY{payment_id}",
  "order_amount": 9109.25,
  "order_currency": "INR",
  "customer_details": {
    "customer_id": "user_{user_id}",
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "9876543210"
  },
  "order_note": "Executive Club Cricket - Shop Order",
  "order_tags": {
    "internal_payment_id": "123",
    "purpose": "shop_order",
    "payable_type": "ShopOrder",
    "payable_id": "456",
    "user_id": "789"
  },
  "order_meta": {
    "return_url": "https://ecc.example.com/payments/cashfree/return?payment_id=123",
    "notify_url": "https://ecc.example.com/webhooks/cashfree"
  }
}
```

> **Note:** `order_amount` is in decimal INR (not paise). Cashfree accepts `9109.25`, not `910925`.

---

## 5. Cashfree Response Fields Used

| Response field | Used for |
|---------------|---------|
| `order_id` | Stored as `payments.gateway_order_id` |
| `cf_order_id` | Stored in `payments.meta.cf_order_id` |
| `payment_session_id` | Stored in `payments.meta.payment_session_id` and `meta.checkout.payment_session_id` |
| `order_status` | Stored in `checkout.order_status` for reference |
| `order_amount` | Cross-referenced with internal payment amount |

---

## 6. Mapping to ECC Internal Payment Fields

| ECC Field | Source | Notes |
|-----------|--------|-------|
| `payments.gateway_order_id` | Cashfree `order_id` (e.g. `ECCPAY123`) | Stored by `PaymentManager` after `createOrder` |
| `payments.gateway_payment_id` | **Not set in Phase 3** | Set only after actual payment in Phase 4 |
| `payments.meta.cf_order_id` | Cashfree `cf_order_id` | Cashfree's numeric order identifier |
| `payments.meta.payment_session_id` | Cashfree `payment_session_id` | Required by Cashfree SDK to start checkout |
| `payments.meta.checkout` | Full checkout payload | Used by web controller and API response |
| `payments.status` | Always `pending` in Phase 3 | Never `paid` until Phase 4 verification |

---

## 7. Checkout Response Structure

`data.payment.checkout` in the API response:

```json
{
  "gateway": "cashfree",
  "order_id": "ECCPAY123",
  "cf_order_id": "1234567890",
  "payment_session_id": "session_xxxxxxxxxx",
  "amount": 9109.25,
  "currency": "INR",
  "display_amount": 9109.25,
  "mode": "sandbox",
  "environment": "sandbox",
  "return_url": null,
  "notify_url": null,
  "name": "Executive Club Cricket",
  "description": "Executive Club Cricket - Shop Order",
  "customer": {
    "id": "user_42",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "9876543210"
  },
  "order_status": "ACTIVE"
}
```

> **Security:** `client_secret`, `webhook_secret`, and `x-client-secret` are **never** included in any checkout payload, API response, or log output.

---

## 8. What Is NOT Implemented Yet

| Feature | Phase |
|---------|-------|
| Cashfree payment checkout UI (JS/mobile SDK) | Phase 4 |
| Cashfree payment verification (`verifyPayment`) | Phase 4 |
| Cashfree payment fetch (`fetchPayment`) | Phase 4 |
| Marking payment/order as paid | Phase 4 |
| Cashfree webhook processing (`handleWebhook`) | Phase 5 |
| Cashfree return route (`payments.cashfree.return`) | Phase 4 |
| Cashfree webhook route (`webhooks.cashfree`) | Phase 5 |

---

## 9. How to Manually Test

### Setup
```dotenv
CASHFREE_ENABLED=true
CASHFREE_MODE=sandbox
CASHFREE_CLIENT_ID=your_sandbox_app_id
CASHFREE_CLIENT_SECRET=your_sandbox_secret_key
CASHFREE_API_VERSION=2023-08-01
```

```bash
php artisan config:clear
php artisan cache:clear
```

### API Test (Shop Checkout)
```http
POST /api/v1/shop/checkout/place-order
Authorization: Bearer {token}

{
  "shipping_address_id": 1,
  "payment_gateway": "cashfree"
}
```

**Expected response includes:**
```json
{
  "success": true,
  "data": {
    "payment": {
      "gateway": "cashfree",
      "status": "pending",
      "checkout": {
        "gateway": "cashfree",
        "payment_session_id": "session_...",
        "cf_order_id": "...",
        "environment": "sandbox"
      }
    }
  }
}
```

### Verify in DB
```sql
SELECT id, gateway, gateway_order_id, gateway_payment_id, status, paid_at, meta
FROM payments
WHERE gateway = 'cashfree'
ORDER BY id DESC LIMIT 1;
```

Expected:
- `gateway_order_id` = `ECCPAY{id}`
- `gateway_payment_id` = `NULL`
- `status` = `pending`
- `paid_at` = `NULL`
- `meta->cf_order_id` = Cashfree numeric ID
- `meta->payment_session_id` = session token

### Web Debug View
```
GET /payments/cashfree/{payment_id}/pay
```
Shows the Phase 3 developer debug page with session details.

---

## 10. How Mobile Should Interpret Checkout Data

Mobile clients should read `data.payment.gateway` from the checkout response:

```
if gateway == "razorpay" → initialize Razorpay Checkout SDK with `key`, `order_id`, `amount`
if gateway == "cashfree" → initialize Cashfree SDK with `payment_session_id` (Phase 4)
```

In Phase 3, mobile receives the `payment_session_id` but there is no Cashfree SDK route/UI yet. The mobile app should present a "Cashfree payment coming soon" message if Cashfree is selected, or simply use Razorpay as the default.

---

## 11. Error Handling Summary

| Scenario | Failure Code | HTTP Status |
|----------|-------------|------------|
| Missing `client_id`/`client_secret` | `RuntimeException` (credential config error) | Rethrown |
| Amount ≤ 0 | `invalid_amount` | `PaymentResult::failed` |
| Non-INR currency | `unsupported_currency` | `PaymentResult::failed` |
| No customer email/phone | `missing_customer_details` | `PaymentResult::failed` |
| Cashfree 401 auth failure | `authentication_failed` | `PaymentResult::failed` |
| Cashfree 400 validation error | Cashfree error code | `PaymentResult::failed` |
| Connection timeout | `cashfree_connection_failed` | `PaymentResult::failed` |
| Missing `payment_session_id` in response | `missing_payment_session_id` | `PaymentResult::failed` |

---

## 12. Razorpay Regression Confirmation

- ✅ `RazorpayGateway` class unchanged
- ✅ `config/payments.gateways.razorpay.enabled` defaults to `true`
- ✅ `PaymentManager::getGateway('razorpay')` resolves to `RazorpayGateway`
- ✅ Default gateway remains `razorpay`
- ✅ Razorpay `createOrder`, `verifyPayment`, `handleWebhook` all unchanged
- ✅ All existing Razorpay tests pass
