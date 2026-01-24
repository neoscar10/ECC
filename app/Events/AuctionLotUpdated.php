<?php

namespace App\Events;

use App\Models\Auctions\AuctionLot;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionLotUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lot;

    /**
     * Create a new event instance.
     */
    public function __construct(AuctionLot $lot)
    {
        $this->lot = $lot;
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
        return 'lot.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'lot_id' => $this->lot->id,
            'status' => $this->lot->status,
            'starts_at' => $this->lot->starts_at?->toIso8601String(),
            'ends_at' => $this->lot->ends_at?->toIso8601String(),
            'current_highest_bid' => $this->lot->current_highest_bid,
        ];
    }
}
