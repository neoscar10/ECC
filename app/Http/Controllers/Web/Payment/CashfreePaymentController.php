<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Payments\PaymentStatus;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CashfreePaymentController
 *
 * Handles Cashfree payment checkout web pages, callbacks, and verification.
 */
class CashfreePaymentController extends Controller
{
    protected PaymentManager $paymentManager;
    protected PaymentFinalizationService $finalizationService;

    public function __construct(PaymentManager $paymentManager, PaymentFinalizationService $finalizationService)
    {
        $this->paymentManager = $paymentManager;
        $this->finalizationService = $finalizationService;
    }

    /**
     * Display the Cashfree session debug page (Phase 3 developer view).
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

        $checkoutData = $payment->meta['checkout'] ?? null;

        if (!$checkoutData || $checkoutData['gateway'] !== 'cashfree') {
            Log::error('CashfreePaymentController: Missing or invalid checkout data.', [
                'payment_id'    => $payment->id,
                'gateway'       => $payment->gateway,
                'checkout_keys' => $checkoutData ? array_keys($checkoutData) : null,
            ]);
            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', 'Cashfree payment configuration missing. Please contact support.');
        }

        $paymentSessionId = $checkoutData['payment_session_id'] ?? $payment->meta['payment_session_id'] ?? null;
        $cfOrderId        = $checkoutData['cf_order_id'] ?? $payment->meta['cf_order_id'] ?? null;
        $environment      = $checkoutData['environment'] ?? 'sandbox';

        Log::info('CashfreePaymentController: Rendering pay page.', [
            'payment_id'        => $payment->id,
            'gateway_order_id'  => $payment->gateway_order_id,
            'cf_order_id'       => $cfOrderId,
            'environment'       => $environment,
        ]);

        return view('shop.payment.cashfree-phase3', [
            'payment'          => $payment,
            'checkoutData'     => $checkoutData,
            'paymentSessionId' => $paymentSessionId,
            'cfOrderId'        => $cfOrderId,
            'environment'      => $environment,
        ]);
    }

    /**
     * Verify the Cashfree payment via AJAX/fetch.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'payment_id'          => 'required|exists:payments,id',
            'order_id'            => 'nullable|string',
            'cf_order_id'         => 'nullable|string',
            'payment_session_id'  => 'nullable|string',
        ]);

        $payment = Payment::findOrFail($request->input('payment_id'));

        if ($payment->purpose !== \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT && $payment->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to payment.'], 403);
        }

        if ($payment->status === PaymentStatus::PAID) {
            return response()->json([
                'success'      => true,
                'redirect_url' => $this->getSuccessUrl($payment)
            ]);
        }

        Log::info('CashfreePaymentController: Web verify received.', [
            'payment_id'         => $payment->id,
            'gateway_order_id'   => $payment->gateway_order_id,
            'request_order_id'   => $request->input('order_id'),
            'cf_order_id'        => $request->input('cf_order_id'),
        ]);

        try {
            $verificationData = [
                'gateway'            => 'cashfree',
                'gateway_order_id'   => $request->input('order_id') ?? $payment->gateway_order_id,
                'gateway_payment_id' => $request->input('cf_order_id') ?? $payment->gateway_payment_id,
                'payload'            => $request->all(),
            ];

            $result = $this->paymentManager->verifyPayment($payment, $verificationData);

            if ($result['result']->success && $result['payment']->status === PaymentStatus::PAID) {
                Log::info('CashfreePaymentController: Verify success, finalizing.', [
                    'payment_id'          => $result['payment']->id,
                    'gateway_payment_id'  => $result['payment']->gateway_payment_id,
                ]);

                // Finalize payment polymorphically
                $this->finalizationService->finalizePaidPayment($result['payment']->fresh());

                return response()->json([
                    'success'      => true,
                    'redirect_url' => $this->getSuccessUrl($result['payment'])
                ]);
            } elseif ($result['payment']->status === PaymentStatus::PENDING) {
                Log::info('CashfreePaymentController: Verify pending.', [
                    'payment_id' => $payment->id,
                ]);

                return response()->json([
                    'success' => false,
                    'status'  => 'pending',
                    'message' => 'Payment is still pending. Please wait or try again.',
                ]);
            } else {
                $reason = $result['result']->failureMessage ?? 'Verification failed';
                Log::warning('CashfreePaymentController: Verify failed.', [
                    'payment_id' => $payment->id,
                    'reason'     => $reason,
                ]);

                $this->finalizationService->finalizeFailedPayment($result['payment']->fresh(), $reason);

                return response()->json([
                    'success'      => false,
                    'status'       => 'failed',
                    'message'      => $reason,
                    'redirect_url' => route('payments.failed', ['payment_id' => $payment->id])
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CashfreePaymentController: Verify exception.', [
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
     * Handle the GET return redirect from Cashfree.
     */
    public function return(Payment $payment)
    {
        if ($payment->purpose !== \App\Support\Payments\PaymentPurpose::ARCHIVE_ENQUIRY_PAYMENT && $payment->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to payment.');
        }

        if ($payment->status === PaymentStatus::PAID) {
            return $this->redirectSuccess($payment);
        }

        Log::info('CashfreePaymentController: GET return callback received.', [
            'payment_id'       => $payment->id,
            'gateway_order_id' => $payment->gateway_order_id,
        ]);

        try {
            $verificationData = [
                'gateway'            => 'cashfree',
                'gateway_order_id'   => $payment->gateway_order_id,
                'payload'            => request()->all(),
            ];

            $result = $this->paymentManager->verifyPayment($payment, $verificationData);

            if ($result['result']->success && $result['payment']->status === PaymentStatus::PAID) {
                Log::info('CashfreePaymentController: Return verify success, finalizing.', [
                    'payment_id'          => $result['payment']->id,
                ]);

                $this->finalizationService->finalizePaidPayment($result['payment']->fresh());

                return $this->redirectSuccess($result['payment'])->with('success', 'Payment verified successfully.');
            } elseif ($result['payment']->status === PaymentStatus::PENDING) {
                Log::info('CashfreePaymentController: Return verify pending.', [
                    'payment_id' => $payment->id,
                ]);

                return redirect()->route('payments.cashfree.pay', $payment->id)
                    ->with('warning', 'Payment is still pending. Please wait or try again.');
            } else {
                $reason = $result['result']->failureMessage ?? 'Payment failed.';
                Log::warning('CashfreePaymentController: Return verify failed.', [
                    'payment_id' => $payment->id,
                    'reason'     => $reason,
                ]);

                $this->finalizationService->finalizeFailedPayment($result['payment']->fresh(), $reason);

                return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                    ->with('error', $reason);
            }
        } catch (\Exception $e) {
            Log::error('CashfreePaymentController: Return verify exception.', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('payments.failed', ['payment_id' => $payment->id])
                ->with('error', 'Internal Server Error during verification.');
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
