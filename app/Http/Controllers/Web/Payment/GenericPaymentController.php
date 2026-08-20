<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentStatus;
use Illuminate\Support\Facades\Log;

class GenericPaymentController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Route the payment to the appropriate gateway page.
     *
     * @param Payment $payment
     */
    public function pay(Payment $payment)
    {
        if ($payment->purpose !== \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT && $payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        if ($payment->status === PaymentStatus::PAID) {
            return $this->redirectSuccess($payment);
        }

        if (!in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::INITIATED])) {
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', 'Payment cannot be processed.');
        }

        $gateway = $payment->gateway;

        try {
            $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
            $gateway = $availabilityService->validateGateway($gateway);
        } catch (\App\Exceptions\PaymentGatewayValidationException $e) {
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', $e->getMessage());
        }

        if ($gateway === 'razorpay') {
            return redirect()->route('payments.razorpay.pay', $payment);
        }

        if ($gateway === 'cashfree') {
            return redirect()->route('payments.cashfree.pay', $payment);
        }

        abort(400, "Unknown or unsupported payment gateway: {$gateway}");
    }

    /**
     * Re-initiate payment attempt using the same gateway and payable.
     *
     * @param Payment $payment
     */
    public function retry(Payment $payment)
    {
        if ($payment->purpose !== \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT && $payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        $payable = $payment->payable;

        // If the payable/order is already paid, redirect to success
        if ($payable && isset($payable->payment_status) && $payable->payment_status === 'paid') {
            return $this->redirectSuccess($payment);
        }

        if ($payment->status === PaymentStatus::PAID) {
            return $this->redirectSuccess($payment);
        }

        $gateway = $payment->gateway ?: config('payments.default_gateway', 'razorpay');

        try {
            $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
            $gateway = $availabilityService->validateGateway($gateway);
        } catch (\App\Exceptions\PaymentGatewayValidationException $e) {
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', $e->getMessage());
        }

        try {
            // Create a new Payment record/attempt using PaymentManager
            $paymentInitiation = $this->paymentManager->initiatePayment(
                payable: $payable,
                amount: $payment->amount,
                purpose: $payment->purpose ?: 'shop_order',
                user: $payment->user,
                gateway: $gateway
            );

            return redirect()->route('payments.pay', $paymentInitiation['payment']->id);
        } catch (\Exception $e) {
            Log::error('Generic payment retry exception: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
            ]);
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', 'Could not retry payment: ' . $e->getMessage());
        }
    }

    /**
     * Get the success URL based on payable type.
     */
    protected function getSuccessUrl(Payment $payment): string
    {
        if ($payment->payable_type === \App\Models\Shop\ShopOrder::class) {
            return route('shop.order-success', ['orderId' => $payment->payable_id]);
        }

        if ($payment->payable_type === \App\Models\MembershipApplication::class) {
            if ($payment->purpose === \App\Support\Payments\PaymentPurpose::MEMBERSHIP_UPGRADE) {
                return route('membership.upgrade.success');
            }
            return route('membership.application.step8');
        }

        if ($payment->payable_type === \App\Models\VaultRemovalRequest::class) {
            return route('vault.index');
        }

        if ($payment->payable_type === \App\Models\Archive\ArchiveProductEnquiry::class) {
            return route('archive.enquiry.success', ['enquiry' => $payment->payable_id]);
        }

        // Fallback
        return route('home');
    }

    /**
     * Redirect to success URL directly (for standard GET requests).
     */
    protected function redirectSuccess(Payment $payment)
    {
        return redirect()->to($this->getSuccessUrl($payment));
    }
}
