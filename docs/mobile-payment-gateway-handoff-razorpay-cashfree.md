# Executive Club Cricket (ECC) Mobile Payment Gateway Handoff Guide
## Integration Details for Razorpay and Cashfree (Phase 6)

This document provides technical instructions, payload specifications, and flows for mobile developers (specifically Flutter/iOS/Android) integrating the dynamic payment gateway selection system.

ECC supports two payment gateways:
1. **Razorpay** (System default gateway)
2. **Cashfree** (Available when `CASHFREE_ENABLED=true` in the configuration)

---

## 1. Dynamic Gateway Discovery

Before presenting payment options on the checkout, registration, or upgrade screens, the mobile app should fetch the list of currently active and enabled payment gateways.

### Endpoint: Retrieve Enabled Gateways
* **HTTP Method**: `GET`
* **Route**: `/api/v1/payments/gateways`
* **Headers**:
  * `Authorization: Bearer <JWT_TOKEN>`
  * `Accept: application/json`

### Success Response Sample (Both Gateways Enabled)
* **Status**: `200 OK`
```json
{
  "success": true,
  "message": "Payment gateways retrieved successfully.",
  "data": {
    "default_gateway": "razorpay",
    "gateways": [
      {
        "key": "razorpay",
        "label": "Razorpay",
        "description": "Pay using UPI, cards, netbanking and wallets.",
        "enabled": true
      },
      {
        "key": "cashfree",
        "label": "Cashfree",
        "description": "Pay using Cashfree supported methods.",
        "enabled": true
      }
    ]
  }
}
```

### Success Response Sample (Cashfree Disabled / Razorpay Only)
* **Status**: `200 OK`
```json
{
  "success": true,
  "message": "Payment gateways retrieved successfully.",
  "data": {
    "default_gateway": "razorpay",
    "gateways": [
      {
        "key": "razorpay",
        "label": "Razorpay",
        "description": "Pay using UPI, cards, netbanking and wallets.",
        "enabled": true
      }
    ]
  }
}
```

---

## 2. Standard Payment Response Envelope

When placing an order or upgrading a membership, you must optionally supply the chosen `payment_gateway` (string: `razorpay` or `cashfree`). If omitted, the backend falls back to the default gateway (Razorpay).

The response returns a standardized `payment` block containing general transaction metadata and a nested gateway-specific `checkout` block containing config parameters for the respective mobile SDKs.

### A. Shop Checkout Endpoint
* **HTTP Method**: `POST`
* **Route**: `/api/v1/shop/checkout/place-order`
* **Request Payload**:
```json
{
  "shipping_address_id": 12,
  "billing_address_id": 12,
  "billing_same_as_shipping": true,
  "payment_gateway": "cashfree", 
  "notes": "Standard checkout notes."
}
```

### B. Membership Upgrade Endpoint
* **HTTP Method**: `POST`
* **Route**: `/api/v1/membership/upgrade`
* **Request Payload**:
```json
{
  "tier_id": 3,
  "payment_gateway": "razorpay"
}
```

---

## 3. Gateway Checkout Payload Differences

Depending on the chosen `payment_gateway`, the nested `payment.checkout` block varies to match what the respective gateway's mobile SDK expects.

### Scenario A: Razorpay Checkout Payload
For Razorpay, the amount is converted to **paise** (integer value). The mobile app passes this direct payload to the Razorpay Flutter/React Native/Native SDK.

```json
{
  "payment": {
    "id": 182,
    "gateway": "razorpay",
    "status": "initiated",
    "amount": 12650.00,
    "currency": "INR",
    "purpose": "shop_order",
    "verify_endpoint": "https://ecc-backend.test/api/v1/payments/razorpay/verify",
    "checkout": {
      "gateway": "razorpay",
      "key": "rzp_test_xxxxxxxxxxxxxx",
      "amount": 1265000, 
      "display_amount": 12650.00,
      "currency": "INR",
      "order_id": "order_PJk193kdksa",
      "internal_payment_id": 182,
      "name": "Executive Club Cricket",
      "description": "Executive Club Cricket - Shop Order",
      "prefill": {
        "name": "Rohan Sharma",
        "email": "rohan@example.com",
        "contact": "+919876543210"
      },
      "notes": {
        "internal_payment_id": 182,
        "payment_id": 182,
        "purpose": "shop_order"
      }
    }
  }
}
```

### Scenario B: Cashfree Checkout Payload
For Cashfree, the amount remains in **rupees** (decimal float/string). The Mobile SDK requires the `payment_session_id` and `cf_order_id` to initialize the checkout window.

```json
{
  "payment": {
    "id": 183,
    "gateway": "cashfree",
    "status": "initiated",
    "amount": 12650.00,
    "currency": "INR",
    "purpose": "shop_order",
    "verify_endpoint": "https://ecc-backend.test/api/v1/payments/cashfree/verify",
    "checkout": {
      "gateway": "cashfree",
      "cf_order_id": "order_cf_9281a8c88bf2",
      "payment_session_id": "session_HJK2891asdkjhAKJS29188a8asdf890",
      "environment": "sandbox", 
      "internal_payment_id": 183
    }
  }
}
```
> [!NOTE]
> The `environment` variable in the Cashfree checkout object indicates whether the mobile client should initialize the SDK in `SANDBOX` or `PRODUCTION` mode.

---

## 4. Secure Backend Payment Verification

Once the mobile SDK notifies the client of a successful transaction:
1. **Never mark an order/upgrade paid purely from the frontend.**
2. Send the transaction identifiers received from the SDK to the respective verify endpoint.
3. The backend makes a direct API call to the gateway to confirm the payment state before updating the database.

---

### A. Razorpay Verification
* **HTTP Method**: `POST`
* **Route**: `/api/v1/payments/razorpay/verify` (or `/api/v1/shop/payments/razorpay/verify`)
* **Request Payload**:
```json
{
  "payment_id": 182,
  "razorpay_order_id": "order_PJk193kdksa",
  "razorpay_payment_id": "pay_PJk289sjdhkj",
  "razorpay_signature": "7f8b901a89c8911dd12a2307efcfdf88998abce7b8c8d89e90ff011122a2bb43"
}
```

* **Success Response (200 OK)**:
```json
{
  "success": true,
  "message": "Payment verified successfully.",
  "data": {
    "payment": {
      "id": 182,
      "gateway": "razorpay",
      "status": "paid",
      "amount": 12650.00,
      "currency": "INR",
      "gateway_order_id": "order_PJk193kdksa",
      "gateway_payment_id": "pay_PJk289sjdhkj",
      "paid_at": "2026-05-22T10:46:15+01:00"
    }
  }
}
```

---

### B. Cashfree Verification
* **HTTP Method**: `POST`
* **Route**: `/api/v1/payments/cashfree/verify` (or `/api/v1/shop/payments/cashfree/verify`)
* **Request Payload**:
```json
{
  "payment_id": 183,
  "order_id": "order_cf_9281a8c88bf2"
}
```

* **Success Response (200 OK)**:
```json
{
  "success": true,
  "message": "Payment verified successfully.",
  "data": {
    "payment": {
      "id": 183,
      "gateway": "cashfree",
      "status": "paid",
      "amount": 12650.00,
      "currency": "INR",
      "gateway_order_id": "order_cf_9281a8c88bf2",
      "gateway_payment_id": "cf_pay_92019381029",
      "paid_at": "2026-05-22T10:47:05+01:00"
    }
  }
}
```

* **Pending Response (202 Accepted)**:
If the user completed the transaction but Cashfree's status is still pending upstream, the backend will return a `202` response:
```json
{
  "success": false,
  "message": "Payment is still pending.",
  "data": {
    "payment": {
      "id": 183,
      "gateway": "cashfree",
      "status": "pending",
      "amount": 12650.00,
      "currency": "INR",
      "gateway_order_id": "order_cf_9281a8c88bf2"
    }
  }
}
```

* **Failed / Mismatch Verification Response (422 Unprocessable Content)**:
If Cashfree returns a failed state, or the signature mismatch occurs:
```json
{
  "success": false,
  "message": "Payment verification failed or not completed.",
  "errors": {
    "payment": [
      "Payment verification failed or not completed."
    ]
  }
}
```

---

## 5. Summary of Integration Checklist

1. **Discover**: Call `/api/v1/payments/gateways` on app launch/checkout initialization to check what's enabled.
2. **Present**: Render selectors if both `razorpay` and `cashfree` are enabled. If only one is enabled, default to it (or hide selection completely).
3. **Submit**: Pass `payment_gateway` during checkout/upgrade requests.
4. **Initialize SDK**:
   - For **Razorpay**: Feed `payment.checkout` directly to `Razorpay.open()`.
   - For **Cashfree**: Pass `payment_session_id` and `cf_order_id` into the Cashfree Web/Mobile checkout SDK.
5. **Verify**: Always submit the payment identifiers to the backend's `/verify` route before advancing the screen.
