<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auction Reminder Minutes
    |--------------------------------------------------------------------------
    |
    | The list of remaining minutes at which to send a reminder notification
    | to subscribers of a live auction.
    |
    */
    'reminder_minutes' => array_map(
        'intval', 
        explode(',', env('AUCTION_REMINDER_MINUTES', '60,30,15,10,5,1'))
    ),
];
