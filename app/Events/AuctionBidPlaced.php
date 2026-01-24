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

    public $lot;
    public $bidAmount;
    public $bidderId;

    /**
     * Create a new event instance.
     */
    public function __construct(AuctionLot $lot, float $bidAmount, int $bidderId)
    {
        $this->lot = $lot;
        $this->bidAmount = $bidAmount;
        $this->bidderId = $bidderId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('auctions.lot.' . $this->lot->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    public function broadcastWith(): array
    {
        return [
            'lot_id' => $this->lot->id,
            'amount' => $this->bidAmount,
            'formatted_amount' => number_format($this->bidAmount, 2),
            'bidder_id' => $this->bidderId, 
            'current_highest_bid' => $this->lot->current_highest_bid, 
            'ends_at' => $this->lot->ends_at?->toIso8601String(),
            'placed_at' => now()->toIso8601String(),
        ];
    }
}
