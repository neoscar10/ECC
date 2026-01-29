<?php

namespace App\Events;

use App\Models\Auctions\AuctionLot;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionBidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bid;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Models\Auctions\AuctionBid $bid)
    {
        $this->bid = $bid;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('auctions.lot.' . $this->bid->auction_lot_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    public function broadcastWith(): array
    {
        $lot = $this->bid->lot; // Assumes relation is loaded or lazy-loaded
        
        return [
            'lot_id' => $lot->id,
            'bidder_id' => $this->bid->user_id,
            'bid' => \App\Support\Auctions\BidPresenter::present($this->bid),
            'current_bid' => number_format($lot->current_highest_bid, 2, '.', ''), // String money 2dp
            'bids_count_total' => $lot->bids()->count(),
            'ends_at' => $lot->ends_at?->toIso8601String(),
        ];
    }
}
