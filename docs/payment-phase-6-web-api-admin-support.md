# Phase 6: Web, Mobile/API, and Admin Payment Support

## Step 1 Audit Report

### WEB
- **Order Success Page:** Exists at `route('shop.order-success', ['orderId' => $order->id])` and uses `App\Livewire\Shop\OrderSuccessPage`.
- **Order Failure Page:** Exists at `route('payments.failed')` using `resources/views/shop/payment/failed.blade.php`.
- **Checkout Page:** `App\Livewire\Shop\CheckoutPage` currently redirects to `route('payments.razorpay.pay')` which renders the Razorpay Checkout UI via `RazorpayPaymentController`.
- **Retry Payment:** The failed page has a "Try Again" button that goes to `route('shop.checkout')`. This forces the user to go through checkout again and create a *new* order.

### API
- **Checkout Endpoint:** `POST /api/v1/shop/checkout/place-order` handled by `CheckoutController::placeOrder`. It returns a `ShopOrderResource`. Currently, it does NOT initiate Razorpay payment.
- **Order Detail Endpoint:** `GET /api/v1/shop/orders/{id}` uses `ShopOrderController::show` and returns `ShopOrderResource`.
- **Order List Endpoint:** `GET /api/v1/shop/orders` uses `ShopOrderController::index` and returns a paginated list of `ShopOrderResource`.
- **Current API Response:** Uses a standard envelope (e.g. `success`, `message`, `data`). The `ShopOrderResource` contains totals, status, payment_status, items, dates, and shipment details.
- **Payment Verification:** Currently missing for mobile API.

### ADMIN
- **Order Detail Page:** `App\Livewire\Admin\Shop\Orders\Show` with blade components in `resources/views/livewire/admin/shop/orders/partials/`.
- **Order List Page:** `App\Livewire\Admin\Shop\Orders\Index` with `resources/views/livewire/admin/shop/orders/index.blade.php`.
- **Payment Status Display:** `Index.php` manages status modals and lists payment statuses. `Show.php` manages status updates.
- **Payment Reference/Gateway Display:** Missing from the UI.
- **Payment Logs:** Missing from the UI.

## Planned Additions & Backward Compatibility

### Web
- Enhance `payments.failed` to support retry of the *same* payment/order using a safe `GET /payments/{payment}/retry` route that regenerates the Razorpay checkout and redirects.
- Ensure the success page securely loads only for the user's own orders.

### API
- Modify `CheckoutController::placeOrder` to inject the `PaymentManager->initiatePayment` data into the response as a `payment` nested object under the `data`. This keeps existing `ShopOrderResource` fields intact.
- Create `POST /api/v1/payments/razorpay/verify` to handle mobile SDK verifications securely.
- Extend `ShopOrderResource` to include a `payment` summary when requested, specifically showing `id`, `gateway`, `status`, `amount`, `gateway_order_id`, and `gateway_payment_id`.

### Admin
- Introduce `public function payments()` and `public function latestPayment()` relationships on the `ShopOrder` model (as `morphMany`).
- Add a new `_sidebar-payment.blade.php` partial on the admin order detail page to show payment attributes.
- Add a new `_payment-events.blade.php` partial below the `_items-table` or `_timeline` to display `PaymentEvent` entries safely.
- Add a payment status column to the admin order list if not already detailed.
