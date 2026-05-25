<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Support\Payments\PaymentPurpose;
use App\Services\Shop\OrderPaymentService;
use App\Services\Membership\MembershipPaymentService;
use App\Services\Vault\VaultDeliveryPaymentService;
use App\Services\Auction\AuctionSettlementPaymentService;
use InvalidArgumentException;

class PaymentFinalizationService
{
    protected OrderPaymentService $orderPaymentService;
    protected MembershipPaymentService $membershipPaymentService;
    protected VaultDeliveryPaymentService $vaultDeliveryPaymentService;
    protected AuctionSettlementPaymentService $auctionSettlementPaymentService;

    public function __construct(
        OrderPaymentService $orderPaymentService,
        MembershipPaymentService $membershipPaymentService,
        VaultDeliveryPaymentService $vaultDeliveryPaymentService,
        AuctionSettlementPaymentService $auctionSettlementPaymentService
    ) {
        $this->orderPaymentService = $orderPaymentService;
        $this->membershipPaymentService = $membershipPaymentService;
        $this->vaultDeliveryPaymentService = $vaultDeliveryPaymentService;
        $this->auctionSettlementPaymentService = $auctionSettlementPaymentService;
    }

    /**
     * Finalize the payment sequence upon success.
     */
    public function finalizePaidPayment(Payment $payment)
    {
        switch ($payment->purpose) {
            case PaymentPurpose::SHOP_ORDER:
                return $this->orderPaymentService->finalizePaidOrder($payment);
            case PaymentPurpose::MEMBERSHIP_UPGRADE:
            case PaymentPurpose::MEMBERSHIP_RENEWAL:
                return $this->membershipPaymentService->finalizePaidMembership($payment);
            case PaymentPurpose::VAULT_DELIVERY:
                return $this->vaultDeliveryPaymentService->finalizePaidVaultDelivery($payment);
            case PaymentPurpose::AUCTION_SETTLEMENT:
                return $this->auctionSettlementPaymentService->finalizePaidAuctionSettlement($payment);
            default:
                throw new InvalidArgumentException("Unsupported payment purpose: {$payment->purpose}");
        }
    }

    /**
     * Mark the payment and its payable failed.
     */
    public function markPaymentFailed(Payment $payment, string $reason)
    {
        switch ($payment->purpose) {
            case PaymentPurpose::SHOP_ORDER:
                return $this->orderPaymentService->markPaymentFailedOrder($payment, $reason);
            case PaymentPurpose::MEMBERSHIP_UPGRADE:
            case PaymentPurpose::MEMBERSHIP_RENEWAL:
                return $this->membershipPaymentService->markPaymentFailedMembership($payment, $reason);
            case PaymentPurpose::VAULT_DELIVERY:
                return $this->vaultDeliveryPaymentService->markPaymentFailedVaultDelivery($payment, $reason);
            case PaymentPurpose::AUCTION_SETTLEMENT:
                return $this->auctionSettlementPaymentService->markPaymentFailedAuctionSettlement($payment, $reason);
            default:
                throw new InvalidArgumentException("Unsupported payment purpose: {$payment->purpose}");
        }
    }

    /**
     * Finalize the payment sequence upon failure (alias for markPaymentFailed).
     */
    public function finalizeFailedPayment(Payment $payment, string $reason)
    {
        return $this->markPaymentFailed($payment, $reason);
    }
}
