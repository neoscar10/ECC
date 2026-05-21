# Phase 1: ECC Payment Flow & Generic Architecture Audit

This document audits the existing mock/dummy payment pathways in the Executive Cricket Club (ECC) Laravel 11 project. It establishes structural readiness to support multiple gateways—starting with Razorpay, followed by Cashfree—without breaking existing systems, Livewire templates, mobile/API consumers, or dashboard metrics.

---

## A. Current Dummy Payment Locations

We have identified and mapped five key domain flows where payments are simulated or tracked:

### 1. Shop Checkout & Order Creation
- **File**: `app/Services/Shop/CheckoutService.php` ([CheckoutService.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Services/Shop/CheckoutService.php))
  - `placeOrder()`: Creates a new `ShopOrder` record. If payment details are passed directly, it marks the order `paid` immediately, otherwise sets status as `pending_payment` and payment status as `unpaid`.
  - `confirmPayment()`: Simulates mock payment completion. Updates `payment_status` to `'paid'`, `status` to `'paid'`, updates `paid_at`, and appends payment references into the `meta_json` column.
- **File**: `app/Livewire/Shop/CheckoutPage.php` ([CheckoutPage.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Livewire/Shop/CheckoutPage.php))
  - Facilitates user checkout and triggers the simulated mock order creation flow.

### 2. Shop Success & Failure Pages
- **File**: `app/Livewire/Shop/OrderSuccessPage.php` ([OrderSuccessPage.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Livewire/Shop/OrderSuccessPage.php))
  - Displays the order success screen. If `payment_status` is not `'paid'`, it provides an explicit option to complete the pending payment.
- **File**: `resources/views/livewire/shop/order-success-page.blade.php`
  - Presents order total and transaction confirmation visually.

### 3. Membership Applications & Upgrades
- **File**: `app/Domain/Membership/PaymentService.php` ([PaymentService.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Domain/Membership/PaymentService.php))
  - `processTestPayment()`: Validates that raw credit card data was not sent, creates a dummy payment under the `payments` table associated with `MembershipApplication`, sets the status to `'test_paid'`, and generates a `'TEST-' . uniqid()` reference.
- **File**: `app/Http/Controllers/Api/V1/MembershipApplicationController.php` ([MembershipApplicationController.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Http/Controllers/Api/V1/MembershipApplicationController.php))
  - `confirmPayment()`: Handles the API endpoint for membership application payments by invoking `PaymentService::processTestPayment()`.
- **File**: `app/Http/Controllers/Api/V1/MembershipUpgradeController.php` ([MembershipUpgradeController.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Http/Controllers/Api/V1/MembershipUpgradeController.php))
  - `upgrade()`: Initiates proration details, passes proration audit logs to membership payments, and handles upgrading an existing member tier using the mock payments service.

### 4. Vault Removal Requests (Physical Delivery Flow)
- **File**: `app/Services/VaultService.php` ([VaultService.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Services/VaultService.php))
  - `requestRemoval()`: Creates a removal request (`VaultRemovalRequest`). If a courier quote exists, it populates `delivery_fee` and sets `payment_status` to `pending_payment` or `none` accordingly.
  - `rejectRemoval()`: Rejects paid requests, transitioning `payment_status` to `refund_required`.
  - `markRefundHandled()`: Marks a rejected request refund as cleared, moving it to `refunded`.
- **File**: `app/Models/VaultRemovalRequest.php` ([VaultRemovalRequest.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Models/VaultRemovalRequest.php))
  - Maintains `payment_status` tracking values (`none`, `pending_payment`, `paid`, `payment_failed`, `refund_required`, `refunded`).

### 5. Auction Lot Settlements
- **File**: `app/Services/Auctions/AuctionDossierService.php` ([AuctionDossierService.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Services/Auctions/AuctionDossierService.php))
  - Maps sales to `cleared` or `pending` status using `$sale->paid_at` attributes.
- **File**: `app/Livewire/Admin/Auctions/Orders/RecordSaleModal.php` ([RecordSaleModal.php](file:///c:/Users/USER/Desktop/projects/Executive%20Cricket%20Club/app/Livewire/Admin/Auctions/Orders/RecordSaleModal.php))
  - Permits admins to record manual offline lot sales, capturing a manual `paid_at` date.

---

## B. Existing Database Payment Columns

We audited all models containing payment metrics to track where database column transitions will occur:

| Table Name | Column Name | Model Class | Current Purpose & Schema | Transition Plan to Generic Payments Table |
| :--- | :--- | :--- | :--- | :--- |
| **shop_orders** | `payment_status` | `ShopOrder` | Tracking shop transaction status: `unpaid`, `paid`, `failed`, `refunded`. | Remains as a local module summary status field. The gateway source-of-truth will live in the generic `payments` table. |
| **shop_orders** | `paid_at` | `ShopOrder` | Timestamp when payment succeeded. | Synchronized upon webhook/callback clearance from the generic table. |
| **shop_orders** | `meta_json` | `ShopOrder` | Stores manual mock payment objects. | Gateway transaction identifiers will transition to generic table. |
| **payments** *(legacy)* | `membership_application_id` | `Payment` *(legacy)* | Direct FK constraint binding legacy payments exclusively to applications. | **To be dropped and recreated.** Polymorphic fields (`payable_type`/`payable_id`) will resolve all payable entities dynamically. |
| **orders** | `payment_method` | `Order` | Stores manually logged lot payments (offline, cash, transfer). | Retained for manually audited offline auction lots. |
| **orders** | `payment_reference`| `Order` | Logs custom text references for lots. | Retained for manually audited offline lot payments. |
| **orders** | `paid_at` | `Order` | Timestamp when lot was cleared manually. | Synchronized upon webhook/callback clearance. |
| **vault_removal_requests** | `payment_status` | `VaultRemovalRequest` | Custom tracking: `none`, `pending_payment`, `paid`, `payment_failed`, `refund_required`, `refunded`. | Summary state. Will be driven by the generic polymorphic payment table. |
| **vault_removal_requests** | `payment_reference` | `VaultRemovalRequest` | Tracks transaction reference string. | Moves to generic gateway table. |
| **vault_removal_requests** | `paid_at` | `VaultRemovalRequest` | Timestamp delivery fee cleared. | Synchronized upon polymorphic payment clearance. |

---

## C. Existing Service / Controller Flow

1. **Order Creation**:
   - `CheckoutService::placeOrder()` handles inventory decrement logic with database transactional `lockForUpdate()` constraints on variant-level (`ShopProductVariant`) and product-level (`ShopProduct`) models.
   - Restores stock safely inside database transactions in case of cancellations via `CheckoutService::cancelOrder()` and `CheckoutService::adminCancelOrder()`.
2. **Simulated Payment**:
   - A mock array is provided to `CheckoutService::confirmPayment()` or `PaymentService::processTestPayment()`.
   - The status is immediately updated to `paid` or `test_paid`, bypasses asynchronous verification, and completes actions immediately.
3. **Inventory & Logistics Finalization**:
   - Marking a shop order paid immediately invokes Shiprocket logic: `app(\App\Services\Shipping\ShipmentService::class)->prepareCourierSelectionForShopOrder($order)`.
4. **Mobile API Consumers**:
   - Controllers return JSON structures mapped by resources such as `ShopOrderResource` which depend on `$order->payment_status` and `$order->paid_at`. These must not be broken or omitted.

---

## D. Risk Notes & Integration Strategy

- **API Compatibility Protection**:
  - The API structure relies heavily on direct columns (`payment_status`, `paid_at`) on the parent models (`ShopOrder`, `MembershipApplication`, `VaultRemovalRequest`). 
  - **Strategy**: We will keep these local columns as module-level summary summaries. When a payment event succeeds, the ledger updates the generic payment record *and* updates the local columns of the payable entity. This guarantees 100% backward-compatibility for API resource schemas.
- **Double Payment Risks**:
  - Without unique, stateful gateway tracking, a user double-clicking or resubmitting could spawn duplicate checkout items.
  - **Strategy**: The generic `payments` table tracks unique indexes on `gateway_order_id` and `gateway_payment_id` and tracks a strict state machine (`initiated` -> `pending` -> `paid`). Webhook handlers will check for processed states using transactional locking before recording/updating.
- **Gateway Abstraction Isolation**:
  - By using `PaymentGatewayInterface` and `PaymentManager`, we guarantee that Livewire components and models never interact directly with Razorpay SDKs. When Cashfree is integrated, it will simply require writing a class that implements `PaymentGatewayInterface` and registering it in `config/payments.php`.
