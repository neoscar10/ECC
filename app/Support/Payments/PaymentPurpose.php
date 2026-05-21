<?php

namespace App\Support\Payments;

class PaymentPurpose
{
    const SHOP_ORDER = 'shop_order';
    const MEMBERSHIP_UPGRADE = 'membership_upgrade';
    const MEMBERSHIP_RENEWAL = 'membership_renewal';
    const VAULT_DELIVERY = 'vault_delivery';
    const AUCTION_SETTLEMENT = 'auction_settlement';
}
