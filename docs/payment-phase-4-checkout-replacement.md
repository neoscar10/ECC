# Phase 4 Checkout Replacement Audit & Plan

## Audit of Current Web Checkout Flow
- **Checkout Component:** `app/Livewire/Shop/CheckoutPage.php` handles the user interaction, address selection, and calls `placeOrder()`.
- **Order Creation Service:** `app/Services/Shop/CheckoutService.php` (`placeOrder` method)
  - This method creates the order, deducts inventory stock, and clears the user's cart unconditionally within a database transaction.
  - Currently, `CheckoutPage.php` passes dummy `$paymentDetails` directly into `placeOrder()`, which marks the order as `paid` instantly and sets the status to `paid`.
- **Shiprocket Logic:** Inside `placeOrder()`, if the order is successfully placed, it invokes `prepareCourierSelectionForShopOrder`.
- **Order Finalization:** `CheckoutService::confirmPayment($order, $paymentDetails)` is currently implemented. It updates `payment_status` to `paid` and `status` to `paid`, and triggers Shiprocket courier selection (if not already handled or as a retry).
- **Success Route:** `route('shop.order-success', ['orderId' => $order->id])`
- **Failure Route:** None currently. We will create a clean failure route or handle failures within the new payment page.

## Planned Order Payment States
- **Order payment_status:**
  - `unpaid` (initial)
  - `paid` (after Razorpay verification)
- **Order status:**
  - `pending_payment` (initial)
  - `paid` (after Razorpay verification)
- **Payment Table status:**
  - `initiated`
  - `pending`
  - `paid`
  - `failed`

## Integration Points
1. **Razorpay Initiation:** Inserted in `CheckoutPage::placeOrder()`. It will create an order via `CheckoutService` with no payment details (unpaid). Then, it will invoke `PaymentManager::initiatePayment` and redirect to a new Razorpay web payment page.
2. **Backend Verification:** Inserted in a new controller `RazorpayPaymentController@verify`. It receives the Razorpay signature via AJAX from the web payment page, verifies it via `PaymentManager::verifyPayment()`, and then triggers `OrderPaymentService::finalizePaidOrder()`.

## Files to be Changed/Created
1. **Modified:**
   - `app/Livewire/Shop/CheckoutPage.php`
   - `app/Services/Payments/Gateways/RazorpayGateway.php`
   - `app/Services/Payments/PaymentManager.php`
   - `routes/web.php`
2. **Created:**
   - `app/Services/Shop/OrderPaymentService.php` (Finalizes order post-payment)
   - `app/Http/Controllers/Web/Payment/RazorpayPaymentController.php` (Handles render and verification)
   - `resources/views/shop/payment/razorpay.blade.php` (Blade view integrating Razorpay Checkout JS)
