<?php

namespace App\Events;

use App\Models\Auctions\AuctionLot;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lot;
    public $newStatus;
    public $endedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(AuctionLot $lot, string $newStatus)
    {
        $this->lot = $lot;
        $this->newStatus = $newStatus;
        $this->endedAt = $lot->ended_at;
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
        return 'status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'lot_id' => $this->lot->id,
            'status' => $this->newStatus,
            'ended_at' => $this->endedAt?->toIso8601String(),
            'winner_user_id' => $this->lot->winner_user_id, // Useful for frontend to show "You won!" or "Sold to X"
        ];
    }
}
