<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-Bid Lag Configuration
    |--------------------------------------------------------------------------
    |
    | Define the delay behavior for system auto-bids.
    |
    | lag_min: Minimum seconds to wait before reacting (if not in cutoff).
    | lag_max: Maximum seconds to wait.
    | lag_cutoff: If remaining seconds < this value, lag is 0 (instant).
    |
    */
    'autobid_lag_min' => env('AUCTION_AUTOBID_LAG_MIN_SECONDS', 60),
    'autobid_lag_max' => env('AUCTION_AUTOBID_LAG_MAX_SECONDS', 120),
    'autobid_lag_cutoff' => env('AUCTION_AUTOBID_LAG_CUTOFF_SECONDS', 120),
];
