<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Http\Resources\Payment\PaymentResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentStatusController extends Controller
{
    use ApiResponse;

    /**
     * Get status of a payment.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return $this->error('Payment not found.', 404);
        }

        $user = $request->user();

        // Ensure ownership or admin privilege
        if ($payment->user_id !== $user->id && !$user->hasRole(['super_admin', 'ecc_admin'])) {
            return $this->error('Unauthorized access to payment.', 403);
        }

        return $this->success(new PaymentResource($payment), 'Payment status retrieved successfully.');
    }
}
