# Payment — Cashfree Phase 4: Web/API Payment Verification

**Status:** Phase 4 Complete  
**Date:** 2026-05-22  
**Purpose:** Implement backend Cashfree payment verification for both web and API controllers. Verification relies entirely on backend validation with Cashfree's GET order API to ensure secure and idempotent payment processing.

---

## 1. Registered Verification Routes

### Web Routes
| Route Name | Method | Path | Controller Action |
|------------|--------|------|-------------------|
| `payments.cashfree.verify` | `POST` | `/payments/cashfree/verify` | `CashfreePaymentController@verify` |
| `payments.cashfree.return` | `GET` | `/payments/cashfree/return/{payment}` | `CashfreePaymentController@return` |

*Note: Web routes are protected by the `auth` and `ensure_registration_complete` middleware.*

### API Routes
| Route Name | Method | Path | Controller Action |
|------------|--------|------|-------------------|
| `shop.payments.cashfree.verify` | `POST` | `/api/v1/shop/payments/cashfree/verify` | `Api\V1\Payment\CashfreePaymentController@verify` |

*Note: API route is protected by `auth:api` (JWT authentication).*

---

## 2. Cashfree Order Status Mapping

When querying the Cashfree GET `/orders/{order_id}` endpoint, the returned `order_status` is mapped to ECC internal payment statuses as follows:

| Cashfree `order_status` | ECC Status | Flow Result |
|-------------------------|------------|-------------|
| `PAID` | `PAID` | Success (marks payment & order/membership paid) |
| `ACTIVE` | `PENDING` | Kept as pending (user checkout in progress) |
| `PENDING` | `PENDING` | Kept as pending (awaiting payment clearance) |
| `EXPIRED` | `FAILED` | Failed (marks payment failed) |
| `TERMINATED` | `FAILED` | Failed (marks payment failed) |
| `CANCELLED` | `FAILED` | Failed (marks payment failed) |
| `FAILED` | `FAILED` | Failed (marks payment failed) |

---

## 3. Core Verification Logic (CashfreeGateway)

The backend verification logic inside `CashfreeGateway::verifyPayment` enforces strict security validations before marking any payment as PAID:

1. **Gateway Check**: Ensures the payment gateway field matches `cashfree`.
2. **Order ID Integrity**: Ensures the requested order ID matches the trusted `gateway_order_id` (e.g., `ECCPAY{payment_id}`).
3. **Amount Validation**: Rounding check up to 2 decimal places using `round($amount, 2)` to match the exact decimal representation used by Cashfree.
4. **Currency Validation**: Ensures Cashfree currency matches local payment currency (INR).
5. **Gateway Payment ID Resolution**: Resolves the transaction reference from Cashfree's nested payment records (`payments.0.cf_payment_id` or `cf_payment_id` or `cf_order_id`).

---

## 4. API Endpoint Payload and Responses

### POST `/api/v1/shop/payments/cashfree/verify`

#### Request Payload
```json
{
  "payment_id": 123,
  "order_id": "ECCPAY123",
  "cf_order_id": "1234567890",
  "payment_session_id": "session_xxxx"
}
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Payment verified successfully.",
  "data": {
    "payment": {
      "id": 123,
      "gateway": "cashfree",
      "status": "paid",
      "amount": 1000.0,
      "currency": "INR",
      "gateway_order_id": "ECCPAY123",
      "gateway_payment_id": "cf_payment_abc123",
      "paid_at": "2026-05-22T21:00:00+01:00"
    },
    "order": {
      "id": 456,
      "order_number": "ORD-123",
      "status": "paid",
      "payment_status": "paid"
    }
  },
  "meta": null,
  "errors": null
}
```

#### Pending Response (`202 Accepted`)
```json
{
  "success": false,
  "message": "Payment is still pending.",
  "data": {
    "payment": {
      "id": 123,
      "gateway": "cashfree",
      "status": "pending",
      "amount": 1000.0,
      "currency": "INR",
      "gateway_order_id": "ECCPAY123"
    }
  },
  "meta": null,
  "errors": null
}
```

#### Failure Response (`422 Unprocessable Content`)
```json
{
  "success": false,
  "message": "Payment was not completed.",
  "data": null,
  "meta": null,
  "errors": {
    "payment": [
      "Payment verification failed or not completed."
    ]
  }
}
```

#### Unauthorized Response (`403 Forbidden`)
Returned if the authenticated user is not the owner of the specified payment record.
```json
{
  "success": false,
  "message": "Unauthorized access to payment.",
  "data": null,
  "meta": null,
  "errors": null
}
```

---

## 5. Web Verification Controller Flows

### POST `/payments/cashfree/verify` (AJAX Verification)
Used by the frontend to trigger verification. Returns JSON containing:
- `success: true` and `redirect_url` to the success page upon verification.
- `success: false` and `status: 'pending'` to keep the loading screen spinner active.
- `success: false`, `status: 'failed'`, and `redirect_url` pointing to the payment failure page on final failure.

### GET `/payments/cashfree/return/{payment}` (Redirect Callback)
Handles users redirected back to the site after a redirect payment attempt.
- **Success**: Redirects to the success page and sets a success session flash message.
- **Pending**: Redirects back to the pay page with a warning session flash message.
- **Failed**: Redirects to the failed payment landing page with an error session flash message.

---

## 6. Security Standards
- **Client Secret**: `client_secret` and `x-client-secret` are utilized strictly in backend headers and are **never** exposed in AJAX responses, redirects, JSON API endpoints, or standard log statements.
- **Ownership Check**: Authentication checks are performed at both the API and web levels to guarantee users can only query status for their own payments.

---

## 7. Razorpay Regression Confirmation
- ✅ `RazorpayGateway` verification is completely unaffected.
- ✅ Razorpay controllers and web routes continue to operate under original paths.
- ✅ Complete test suite verifies no regressions in Razorpay workflows.
