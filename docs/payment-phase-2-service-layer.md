# ECC Core Payment Service Layer (Phase 2 Architecture)

This document describes the design, class responsibilities, and future integration paths for the **Executive Club Cricket (ECC) Generic Payment Architecture**.

---

## 1. Architectural Overview

To ensure that the ECC application remains modular and is not tied exclusively to a single payment gateway (such as **Razorpay**), we have built a **gateway-neutral payment service layer**. Razorpay is simply the first adapter implementing a generic payment contract. Future gateways like **Cashfree** can be plugged in seamlessly without altering order, checkout, or membership modules.

### The Dynamic Flow Diagram

```
Shop Checkout / Membership / Vault Delivery
    │
    ▼ (invokes)
PaymentManager::initiatePayment()
    │
    ├──► PaymentLedgerService::createPayment()  ──► [Creates internal 'payments' DB row]
    │
    ▼ (resolves default or requested gateway adapter)
[Selected Gateway Adapter] (e.g. RazorpayGateway)
    │
    ├──► createOrder() ──► [Calls Gateway API to create Order ID]
    │
    ▼ (sends back normalized PaymentResult DTO)
PaymentManager
    │
    ├──► Updates internal Payment with 'gateway_order_id'
    ├──► Transitions Payment status to 'PENDING'
    │
    ▼ (returns back to Caller)
Unified Payment, DTO result, and Client Checkout parameters
    │
    ▼
[Client Web / Mobile checkout executes payment via JavaScript SDK]
    │
    ▼ (sends payload back to backend on success)
PaymentManager::verifyPayment() OR PaymentWebhookService::handle()
    │
    ├──► Resolves Gateway Adapter ──► verifyPayment() OR handleWebhook()
    │
    ▼ (transitions state on success)
PaymentLedgerService::markPaid() ──► [Marks 'payments' PAID idempotently]
    │
    ▼
[Domain-Specific Event Listeners / Callbacks trigger finalization: e.g. activate membership, deliver order]
```

---

## 2. Core Class Responsibilities

The generic service layer is composed of the following key classes located under `app/Services/Payments/`:

### A. Central Manager Driver
* **`PaymentManager`** (`app/Services/Payments/PaymentManager.php`):
  The orchestrator. All business modules communicate only with the `PaymentManager`.
  * Resolves gateway adapters dynamically based on context or configuration settings via `getGateway($gateway)`.
  * Manages the high-level `initiatePayment()` and `verifyPayment()` pipelines.
  * Ensures that Cashfree configurations throw a structured placeholder error: *"Cashfree gateway is configured but not implemented yet."*

### B. Contracts & Adapters
* **`PaymentGatewayInterface`** (`app/Services/Payments/Contracts/PaymentGatewayInterface.php`):
  Defines the generic contract that all payment gateways must implement.
  * Standardizes order creation, verification, status fetching, and webhook handling.
  * Relies exclusively on normalized DTOs, decoupling database models and business-specific domains.
* **`RazorpayGateway`** (`app/Services/Payments/Gateways/RazorpayGateway.php`):
  The Razorpay-specific adapter. Reads credentials on instantiation from `config/payments.php` and conforms to the `PaymentGatewayInterface` contract. In Phase 2, throws controlled `RuntimeException` placeholder exceptions to prepare safely for Phase 3.

### C. Internal Database Ledger
* **`PaymentLedgerService`** (`app/Services/Payments/PaymentLedgerService.php`):
  Responsible **only** for writing internal transaction ledgers and audit records.
  * **No business finalization** (such as upgrading tiers, sending notifications, or approving shipments) happens here.
  * Normailzes currencies and amounts to precise decimals.
  * Idempotently transitions payments to `PAID` or `FAILED` (safeguards against double processing).
  * Safely records raw gateway payloads into the `payment_events` table (even when the payload cannot be mapped back to a specific payment ID, e.g. rogue/disputed webhook calls).

### D. Webhook Controller Handler
* **`PaymentWebhookService`** (`app/Services/Payments/PaymentWebhookService.php`):
  Delegates signature check and event parsing back to the specific gateway adapter, captures transaction results, and records the raw incoming event to the `payment_events` audit table. Catches the controlled placeholder exception gracefully.

---

## 3. Data Transfer Objects (DTOs)

To keep the gateway contracts completely decoupled from external request parameters or database models, we use standardized Data Transfer Objects under `app/Services/Payments/DTO/`:

1. **`PaymentInitiationData`**:
   Wraps the polymorphic payable entity, amount, currency, and user details into a standardized structure before passing them to the gateway engine.
2. **`PaymentVerificationData`**:
   Maps incoming callback signatures (e.g. `razorpay_signature`, `cf_signature`) automatically through the static `fromArray()` parser.
3. **`PaymentResult`**:
   Encapsulates standard checkout metadata, raw gateway responses, error diagnostics, and status mapping. Includes static factory methods: `success()`, `failed()`, and `pending()`.

---

## 4. Decoupling Business Finalization

### Why decopuling is critical:
The `payments` table and `PaymentLedgerService` serve as the absolute source of truth for **financial gateway transactions**. They do not know what product was bought or what tier was upgraded.

When a payment is marked `PAID`:
1. The **business controller / job** checks the payment status using the `PaymentManager`.
2. Upon confirmation of `PaymentStatus::PAID`, the domain-specific service (e.g. `MembershipService`, `OrderService`, or `VaultService`) updates the domain status (e.g. setting order status to `processing` or activating a user's membership).
3. This maintains perfect **Single Responsibility** and makes it incredibly easy to switch payment providers without rewriting core club logistics.
