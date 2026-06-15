# Mobile API Documentation: Wallet & Funding

This document details the complete flow and API specifications required to implement the Wallet and Funding features on the mobile application.

All endpoints require the `Authorization: Bearer {sanctum_token}` and `Accept: application/json` headers.

---

## 1. Fetch Wallet Dashboard Details

Fetches the complete state of the user's wallet, including balance, demo properties, and recent failed payments for retry capability.

**Endpoint:** `GET /api/v1/wallet`

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Wallet details retrieved successfully.",
  "data": {
    "id": "9b1c28b5-abcd-...",
    "balance": "1000.0000",
    "real_balance": "0.0000",
    "currency": "INR",
    "status": "active",
    "is_demo": true,
    "demo_credits": "1000.0000",
    "is_low_balance": false,
    "threshold": "100.00",
    "last_transaction_at": "2026-06-09T10:30:00Z",
    "total_funded": "0.0000",
    "total_spent": "0.0000",
    "recent_payment_attempts": [
      {
        "id": "9b1c28b5-xyz...",
        "amount": "500.00",
        "status": "failed",
        "gateway": "razorpay",
        "created_at": "2026-06-08T10:30:00Z"
      }
    ]
  }
}
```
*Note: If `is_demo` is `true`, `balance` will match `demo_credits`. Mobile UI should show "Demo Mode" and offer options to fund the wallet to convert to a real account.*

---

## 2. Wallet Funding Flow

The funding process is a two-step API flow: **Initialize** -> **Verify**.

### Step A: Initialize Funding

Creates an order on the payment gateway and returns the checkout details required to render the native mobile payment SDKs.

**Endpoint:** `POST /api/v1/wallet/fund/initialize`

**Request Body:**
```json
{
  "amount": 500.00,
  "gateway": "razorpay" // Optional: "razorpay" or "cashfree"
}
```

**Success Response (201 Created):**
```json
{
  "status": "success",
  "message": "Payment initialization successful.",
  "data": {
    "transaction_id": "9b1c28c8-...",
    "gateway": "razorpay",
    "gateway_order_id": "order_XYZ123...",
    "amount": 500,
    "currency": "INR",
    "checkout_data": {
      "key": "rzp_test_12345",
      "amount": 50000, // typically in paisa
      "name": "CompliFlow",
      "description": "Wallet Funding"
    }
  }
}
```

**Error Responses:**
- `422 Unprocessable Entity`: Minimum amount validation failed (Must be > 10).
- `500 Internal Server Error`: Payment gateway is down or credentials are wrong.

---

### Step B: Verify Funding

After the native Mobile Payment SDK (Razorpay/Cashfree) completes the transaction, you must send the signature variables to the backend to securely credit the wallet.

**Endpoint:** `POST /api/v1/wallet/fund/{transaction_id}/verify`
*(Use the `transaction_id` received from the Initialize step)*

**Request Body (Razorpay):**
```json
{
  "razorpay_payment_id": "pay_XYZ...",
  "razorpay_order_id": "order_XYZ...",
  "razorpay_signature": "signature_hash..."
}
```

**Request Body (Cashfree):**
```json
{
  "cf_payment_id": "123456789",
  "cf_signature": "signature_hash..."
}
```

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Payment verified and wallet credited successfully.",
  "data": {
    "id": "9b1c28c8-...",
    "gateway": "razorpay",
    "amount": "500.0000",
    "currency": "INR",
    "status": "successful",
    "gateway_order_id": "order_XYZ...",
    "gateway_payment_id": "pay_XYZ...",
    "completed_at": "2026-06-09T10:35:00Z",
    "created_at": "2026-06-09T10:30:00Z"
  }
}
```

**Error Responses:**
- `400 Bad Request`: `Payment signature verification failed. Secure rejection.`
- `422 Unprocessable Entity`: `Cannot verify a failed payment transaction.`

---

## 3. Transaction History

Used to display the list of all incoming and outgoing wallet transactions.

**Endpoint:** `GET /api/v1/wallet/transactions`

**Query Parameters (All Optional):**
- `type`: `credit` or `debit`
- `category`: e.g., `funding`, `subscription`, `whatsapp_message`
- `date_from` / `date_to`: `YYYY-MM-DD`
- `search`: Searches by description or reference
- `per_page`: Defaults to 15

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Wallet transactions retrieved successfully.",
  "data": {
    "items": [
      {
        "id": "9b1c28d1-...",
        "type": "credit",
        "category": "funding",
        "amount": "500.0000",
        "currency": "INR",
        "balance_before": "0.0000",
        "balance_after": "500.0000",
        "description": "Wallet funding via Razorpay",
        "reference": "TXN-123456789",
        "status": "successful",
        "created_at": "2026-06-09T10:35:00Z"
      }
    ],
    "pagination": {
      "total": 1,
      "count": 1,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 1
    }
  }
}
```

## 4. Available Funding Gateways

Fetches dynamically which gateways are enabled from the backend config.

**Endpoint:** `GET /api/v1/wallet/funding-methods`

**Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Funding gateways retrieved successfully.",
  "data": [
    {
      "gateway": "razorpay",
      "enabled": true
    },
    {
      "gateway": "cashfree",
      "enabled": false
    }
  ]
}
```
