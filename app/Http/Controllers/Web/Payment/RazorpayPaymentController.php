<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use App\Services\Shop\OrderPaymentService;
use App\Support\Payments\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayPaymentController extends Controller
{
    protected PaymentManager $paymentManager;
    protected \App\Services\Payments\PaymentFinalizationService $finalizationService;

    public function __construct(PaymentManager $paymentManager, \App\Services\Payments\PaymentFinalizationService $finalizationService)
    {
        $this->paymentManager = $paymentManager;
        $this->finalizationService = $finalizationService;
    }

    /**
     * Show the Razorpay payment gateway page.
     */
    public function pay(Payment $payment)
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        Log::info('RazorpayPaymentController: pay() called.', [
            'payment_id' => $payment->id,
            'status'     => $payment->status,
            'gateway_order_id' => $payment->gateway_order_id,
            'meta_keys'  => array_keys($payment->meta ?? []),
        ]);

        if ($payment->status === PaymentStatus::PAID) {
            return $this->redirectSuccess($payment);
        }

        if (!in_array($payment->status, [PaymentStatus::PENDING, PaymentStatus::INITIATED])) {
            Log::warning('RazorpayPaymentController: Payment not in processable state.', [
                'payment_id' => $payment->id,
                'status'     => $payment->status,
            ]);
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', 'Payment cannot be processed.');
        }

        $checkoutData = $payment->meta['checkout'] ?? null;

        if (!$checkoutData) {
            Log::error('RazorpayPaymentController: Missing checkout data in payment meta.', [
                'payment_id' => $payment->id,
                'meta'       => $payment->meta,
            ]);
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', 'Payment configuration missing. Please contact support.');
        }

        Log::info('RazorpayPaymentController: Rendering Razorpay pay page.', [
            'payment_id'       => $payment->id,
            'gateway_order_id' => $payment->gateway_order_id,
            'amount'           => $payment->amount,
        ]);

        // Derive test mode from config only — never hardcoded
        $isTestMode = config('payments.gateways.razorpay.mode', 'test') === 'test'
                   || app()->environment(['local', 'staging', 'testing']);

        // Build a safe payable summary for display (no sensitive data)
        $payable = $payment->payable;
        $payableSummary = [
            'label'        => 'Order',
            'reference'    => null,
            'display_amount' => '₹' . number_format((float) $payment->amount, 2),
        ];

        if ($payable && method_exists($payable, 'getKey')) {
            if ($payable instanceof \App\Models\Shop\ShopOrder) {
                $payableSummary['label']     = 'Order';
                $payableSummary['reference'] = $payable->order_number ?? ('#' . $payable->id);
            } elseif ($payable instanceof \App\Models\VaultRemovalRequest) {
                $payableSummary['label']     = 'Vault Delivery';
                $payableSummary['reference'] = '#' . $payable->id;
            } elseif ($payable instanceof \App\Models\MembershipApplication) {
                $payableSummary['label']     = 'Membership';
                $payableSummary['reference'] = '#' . $payable->id;
            }
        }

        return view('shop.payment.razorpay', [
            'payment'        => $payment,
            'checkoutData'   => $checkoutData,
            'isTestMode'     => $isTestMode,
            'payableSummary' => $payableSummary,
        ]);

    }

    /**
     * Verify the Razorpay payment signature via AJAX/fetch.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'internal_payment_id' => 'required|exists:payments,id',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $payment = Payment::findOrFail($request->input('internal_payment_id'));

        if ($payment->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($payment->status === PaymentStatus::PAID) {
            return response()->json([
                'success'      => true,
                'redirect_url' => $this->getSuccessUrl($payment)
            ]);
        }

        Log::info('RazorpayPaymentController: Verify received.', [
            'payment_id'          => $payment->id,
            'razorpay_order_id'   => $request->input('razorpay_order_id'),
            'razorpay_payment_id' => $request->input('razorpay_payment_id'),
        ]);

        try {
            $verificationData = [
                'gateway'           => 'razorpay',
                'gateway_order_id'  => $request->input('razorpay_order_id'),
                'gateway_payment_id'=> $request->input('razorpay_payment_id'),
                'gateway_signature' => $request->input('razorpay_signature'),
                'payload'           => $request->all(),
            ];

            $result = $this->paymentManager->verifyPayment($payment, $verificationData);

            if ($result['result']->success && $result['payment']->status === PaymentStatus::PAID) {
                Log::info('RazorpayPaymentController: Verify success, finalizing.', [
                    'payment_id'          => $result['payment']->id,
                    'gateway_payment_id'  => $result['payment']->gateway_payment_id,
                ]);

                // Finalize payment polymorphically (marks order as paid, etc.)
                $this->finalizationService->finalizePaidPayment($result['payment']);

                return response()->json([
                    'success'      => true,
                    'redirect_url' => $this->getSuccessUrl($result['payment'])
                ]);
            } else {
                Log::warning('RazorpayPaymentController: Verify failed (signature/status mismatch).', [
                    'payment_id' => $payment->id,
                ]);

                $this->finalizationService->markPaymentFailed($result['payment'], 'Verification failed');

                return response()->json([
                    'success'      => false,
                    'message'      => 'Payment verification failed.',
                    'redirect_url' => route('payments.failed', ['payment_id' => $payment->id])
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RazorpayPaymentController: Verify exception.', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'success'      => false,
                'message'      => 'Internal Server Error during verification.',
                'redirect_url' => route('payments.failed', ['payment_id' => $payment->id])
            ], 500);
        }
    }

    /**
     * Re-initiate payment attempt for an unpaid payment/order.
     *
     * @param Payment $payment
     */
    public function retry(Payment $payment)
    {
        if ($payment->user_id !== auth()->id()) {
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

        try {
            // Create a new Payment record/attempt using PaymentManager
            $paymentInitiation = $this->paymentManager->initiatePayment(
                payable: $payable,
                amount: $payment->amount,
                purpose: $payment->purpose ?: 'shop_order',
                user: auth()->user(),
                gateway: $payment->gateway ?: 'razorpay'
            );

            return redirect()->route('payments.razorpay.pay', $paymentInitiation['payment']->id);
        } catch (\Exception $e) {
            Log::error('Razorpay retry exception: ' . $e->getMessage(), [
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
