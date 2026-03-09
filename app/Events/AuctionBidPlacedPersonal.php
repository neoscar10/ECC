<?php

namespace App\Events;

use App\Models\Auctions\AuctionBid;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionBidPlacedPersonal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public $bid;
    public $viewerUserId;

    /**
     * Create a new event instance.
     * 
     * @param AuctionBid $bid
     * @param int|string $viewerUserId The user who is successfully placing this bid (owner )
     */
    public function __construct(AuctionBid $bid, $viewerUserId)
    {
        $this->bid = $bid;
        $this->viewerUserId = $viewerUserId;
    }

    /**
     * Get the channels the event should broadcast on.
     * Reuse the existing pattern: App.Models.User.{id}
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->viewerUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bid.placed'; // Same event name, just different channel
    }

    public function broadcastWith(): array
    {
        $lot = $this->bid->lot; 
        
        return [
            'lot_id' => $lot->id,
            'bidder_id' => $this->bid->user_id,
            // Pass viewerUserId to Presenter to unlock is_me=true
            'bid' => \App\Support\Auctions\BidPresenter::present($this->bid, $this->viewerUserId),
            'current_bid' => number_format($lot->current_highest_bid, 2, '.', ''), 
            'bids_count_total' => $lot->bids()->count(),
            'ends_at' => $lot->ends_at?->toIso8601String(),
        ];
    }
}
