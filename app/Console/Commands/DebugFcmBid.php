<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Auctions\AuctionLot;
use App\Models\Auctions\AuctionBid;
use App\Models\User;
use App\Services\Notifications\AuctionNotificationFormatter;
use App\Jobs\Notifications\SendFcmToTopicJob;
use App\Jobs\Notifications\SendFcmToUserJob;
use App\Support\Notifications\FcmTopicNamer;

class DebugFcmBid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:debug-bid {lot_id} {bidder_id} {amount} {--autobid-owner=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate sending bid notifications (Topic + AutoBid Personal)';

    /**
     * Execute the console command.
     */
    public function handle(AuctionNotificationFormatter $formatter)
    {
        $lotId = $this->argument('lot_id');
        $bidderId = $this->argument('bidder_id');
        $amount = (float) $this->argument('amount');
        $autoBidOwnerId = $this->option('autobid-owner');

        $lot = AuctionLot::find($lotId);
        if (!$lot) {
            $this->error("Lot {$lotId} not found.");
            return 1;
        }

        $bidder = User::find($bidderId);
        if (!$bidder) {
            $this->error("Bidder {$bidderId} not found.");
            return 1;
        }

        // Mock a Bid object (not saving to DB)
        $bid = new AuctionBid();
        $bid->id = 999999;
        $bid->auction_lot_id = $lot->id;
        $bid->user_id = $bidder->id;
        $bid->amount = $amount;
        $bid->placed_at = now();

        $isAuto = !empty($autoBidOwnerId);

        $this->info("Simulating Bid Notification Dispatch...");
        $this->info("Lot: {$lot->id} ({$lot->lot_no})");
        $this->info("Bidder: {$bidder->id}");
        $this->info("Amount: {$amount}");
        $this->info("Is Auto: " . ($isAuto ? 'Yes' : 'No'));

        // 1. TOPIC NOTIFICATION (Public)
        [$title, $body] = $formatter->bidPlaced($lot, $bid, $bidder, $isAuto);
        
        $extraData = [
            'bid_id' => (string)$bid->id,
            'bid_amount' => (string)$bid->amount,
            'currency' => $lot->currency ?? 'INR',
            'actor_user_id' => (string)$bidder->id,
            'new_ends_at' => $lot->ends_at ? $lot->ends_at->toIso8601String() : null,
        ];

        $eventId = "bid_placed:{$bid->id}";
        $payload = $formatter->buildPayload($lot, 'bid_placed', $extraData, $eventId);
        $topic = FcmTopicNamer::auctionTopic($lot);

        $this->info("--> Dispatching SendFcmToTopicJob [Topic: {$topic}]");
        dispatch(new SendFcmToTopicJob($topic, $title, $body, $payload));

        // 2. AUTO-BID NOTIFICATION (Private)
        if ($isAuto) {
            $ownerId = (int)$autoBidOwnerId;
            $owner = User::find($ownerId);
            
            if (!$owner) {
                 $this->error("AutoBid Owner {$ownerId} not found.");
            } else {
                [$autoTitle, $autoBody] = $formatter->autoBidExecuted($lot, $bid, $owner);
                
                $autoExtra = [
                    'bid_id' => (string)$bid->id,
                    'bid_amount' => (string)$bid->amount,
                    'currency' => $lot->currency, 
                    'status' => $lot->status,
                    'actor_user_id' => (string)$owner->id
                ];
                
                $autoEventId = "auto_bid_executed:{$bid->id}";
                $autoPayload = $formatter->buildPayload($lot, 'auto_bid_executed', $autoExtra, $autoEventId);

                $this->info("--> Dispatching SendFcmToUserJob [User: {$owner->id}]");
                dispatch(new SendFcmToUserJob($owner->id, $autoTitle, $autoBody, $autoPayload));
            }
        }

        $this->info("Done. Check logs for 'Job [...] starting' and 'FCM_SEND...' entries.");
        return 0;
    }
}
