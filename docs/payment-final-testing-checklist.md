# Payment Gateway Integration Final Testing Checklist

This document provides environment requirements, sandbox credentials, webhook routing configurations, and manual testing procedures to validate the Executive Cricket Club's gateway-neutral payment system for production readiness.

---

## Environment Setup & Required Configuration Keys

Verify that your `.env` file contains the following keys. Refer to `config/payments.php` for internal mapping.

### 1. General Config
* `PAYMENT_DEFAULT_GATEWAY` (e.g. `razorpay`)
* `PAYMENT_DEFAULT_CURRENCY` (e.g. `INR`)

### 2. Razorpay Integration
* `RAZORPAY_ENABLED` (`true` / `false`)
* `RAZORPAY_KEY_ID` (Your Razorpay Test Key ID)
* `RAZORPAY_KEY_SECRET` (Your Razorpay Test Key Secret)
* `RAZORPAY_WEBHOOK_SECRET` (Secret set in Razorpay Dashboard for webhook signature verification)
* `RAZORPAY_MODE` (test / live)

### 3. Cashfree Integration
* `CASHFREE_ENABLED` (`true` / `false`)
* `CASHFREE_CLIENT_ID` (Your Cashfree App ID / Client ID)
* `CASHFREE_CLIENT_SECRET` (Your Cashfree Client Secret)
* `CASHFREE_WEBHOOK_SECRET` (Secret set in Cashfree Dashboard for webhook signature validation)
* `CASHFREE_MODE` (test / live)
* `CASHFREE_API_VERSION` (Must be `2023-08-01` or later supported)
* `CASHFREE_RETURN_URL` (Success return endpoint, e.g. `http://localhost:8000/payments/callback`)
* `CASHFREE_NOTIFY_URL` (Webhook callback endpoint, e.g. `http://localhost:8000/webhooks/cashfree`)

---

## Sandbox & Webhook Setup Instructions

### Razorpay Sandbox Setup
1. Log in to the [Razorpay Dashboard](https://dashboard.razorpay.com) and switch to **Test Mode**.
2. Navigate to **Account & Settings** -> **API Keys** to generate Test Key Credentials.
3. Switch to **Webhooks** and click **Add New Webhook**:
   - **Webhook URL**: `{APP_URL}/webhooks/razorpay` (or your Ngrok tunneling URL)
   - **Secret**: Enter a secure string and copy it to `RAZORPAY_WEBHOOK_SECRET` in `.env`.
   - **Active Events**: Check `payment.captured` and `payment.failed`.

### Cashfree Sandbox Setup
1. Log in to the [Cashfree Merchant Dashboard](https://merchant.cashfree.com) and navigate to the **Merchant Home** -> **Payment Gateway** -> **Developer Suite**.
2. Generate **API Keys** for the **Test** environment.
3. Switch to the **Webhooks** tab:
   - **Webhook URL**: `{APP_URL}/webhooks/cashfree` (or your Ngrok tunneling URL)
   - **Secret**: Generate a Webhook Secret and copy it to `CASHFREE_WEBHOOK_SECRET` in `.env`.
   - **Events**: Subscribe to `payment.success` and `payment.failed`.

---

## Final Testing Checklist

### 1. Razorpay Gateway Tests
- [ ] **Initiate Payment**: Ensure Razorpay initiates successfully and returns correct metadata/order ID.
- [ ] **Test Card Success**: Complete payment in Razorpay Checkout using test cards (e.g. `4111 1111 1111 1111`).
- [ ] **Test Card Failure**: Input test details that trigger failure (e.g. incorrect OTP or declined card).
- [ ] **Signature Verification**: Verify that a valid `razorpay_signature` verifies successfully on `/api/v1/shop/payments/razorpay/verify`.
- [ ] **Invalid Signature Verification**: Intentionally tamper with the signature and ensure it is cleanly rejected with a validation error.

### 2. Cashfree Gateway Tests
- [ ] **Initiate Order**: Send request with `payment_gateway=cashfree`. Check that a `payment_session_id` is successfully created and returned.
- [ ] **Open Cashfree Sandbox Checkout**: Open Cashfree checkout using the session ID.
- [ ] **Complete Sandbox Payment**: Authenticate successfully using sandbox UPI (`success@cashfree`) or test cards.
- [ ] **Verification Endpoint**: Verify that verification matches the payment state.
- [ ] **Invalid Signature Rejection**: Send a verification payload with an invalid order ID or signature and check for rejection.

### 3. Shop Checkout Tests
- [ ] **Dynamic Selectors**: Confirm that when `CASHFREE_ENABLED=false`, only Razorpay is available. When `CASHFREE_ENABLED=true`, a radio button selector appears.
- [ ] **Standardized Envelope**: Validate that API checkout returns a standardized payload format:
  ```json
  {
    "success": true,
    "data": {
      "payment": {
        "id": 123,
        "gateway": "cashfree",
        "status": "pending",
        "amount": 100.00,
        "currency": "INR",
        "verify_endpoint": "/api/v1/payments/cashfree/verify",
        "checkout": {
          "payment_session_id": "session_...",
          "order_id": "ECCPAY123"
        }
      }
    }
  }
  ```
- [ ] **State Integrity**: Verify that stock quantities are deducted at checkout placement and kept in a locked state, cart items are cleared, and order is flagged `pending_payment`.
- [ ] **Order Finalization**: Confirm that successful verification updates order to `paid`, decrements inventory permanently, and fires invoice events.

### 4. Membership Upgrade/Renewal Tests
- [ ] **Dynamic Selector**: Ensure membership payment views dynamically load all active gateways.
- [ ] **Apply Upgrade**: Execute upgrade flow. Confirm user is assigned the higher membership tier only after payment verification completes.
- [ ] **Apply Renewal**: Renew existing membership. Validate expiry dates extend by the correct duration.
- [ ] **Idempotent Upgrades**: Repeat verification call for an already processed upgrade. Ensure database is not modified and expiry dates are not extended twice.

### 5. Vault Delivery Tests
- [ ] **Vault Delivery Fee**: Request removal and inspect summary total fee.
- [ ] **Initiate Vault Payment**: Select gateway and complete the checkout step.
- [ ] **Finalization State**: Verify that payment verification updates request status to `paid`/`pending_review` (not processed/locked).
- [ ] **Shiprocket Integration**: Confirm that Shiprocket is NOT automatically triggered/called until an admin manual review or explicit action occurs.

### 6. Admin Payment History Tests
- [ ] **Gateway Neutrality**: The admin payments page shows correct statuses, gateway names (`razorpay` or `cashfree`), amounts, and transaction IDs.
- [ ] **Visibility**: Check that failed, pending, and successful attempts are correctly logged with exact details.
- [ ] **Webhook Events logs**: Ensure that registered `payment_events` are viewable and auditable.

### 7. Webhook Handler Tests
- [ ] **Webhook Exemption**: Verify that both `/webhooks/razorpay` and `/webhooks/cashfree` bypass CSRF check.
- [ ] **Signature Check**: Check that headers `X-Razorpay-Signature` or `x-webhook-signature` are verified.
- [ ] **Replay Protection**: Re-send the exact webhook event payload. Ensure it is ignored safely (log displays "Event already processed").
- [ ] **State Transition Integrity**: Verify that a webhook cannot downgrade a payment from `PAID` back to `FAILED`.
- [ ] **Conflict Protection**: Ensure webhook order ID matches the DB payment record's `gateway_order_id`. Rejects mismatches.

### 8. Mobile Compatibility Tests
- [ ] **Amount Formats**: Confirm that Razorpay APIs return amounts in paise (integers) in the `checkout` object, and Cashfree returns amounts in rupees (decimals).
- [ ] **Verify Endpoint**: Confirm the presence of the `verify_endpoint` field pointing to the correct gateway handler.
- [ ] **SDK Handoff Payload**: Ensure all required keys (`key`, `order_id`, `payment_session_id`, `prefill` parameters) are available in the payload.

### 9. Security & Validation Tests
- [ ] **Disabled Gateway Selection**: Set `CASHFREE_ENABLED=false` and request Cashfree payment. Verify it throws a clean 422 JSON validation error.
- [ ] **Cross-User Payment Tampering**: Try to verify another user's payment ID. Verify that verification is rejected with an authorization error.
- [ ] **Exposure Audit**: Search code and logs to confirm client secrets (`CASHFREE_CLIENT_SECRET`, `RAZORPAY_KEY_SECRET`) are never output or logged.

### 10. Regression Tests
- [ ] **Razorpay Checkout**: Ensure Razorpay checkout logic remains fully backward compatible.
- [ ] **Shiprocket Core Logic**: Confirm shipping serviceability and courier fee calculations operate normally.
- [ ] **Database Integrity**: In-memory test suite runs fully green with no constraints violated.

---

## Rollback Guidelines
In case of critical gateway failure post-deployment:
1. Set the failing gateway's env enabled flag to false (e.g. `CASHFREE_ENABLED=false` or `RAZORPAY_ENABLED=false`).
2. Verify that `PAYMENT_DEFAULT_GATEWAY` points to the active gateway.
3. The application will dynamically fall back to the remaining active gateway, disabling UI selectors and preventing new checkouts on the disabled gateway.
