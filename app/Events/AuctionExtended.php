<?php

namespace App\Events;

use App\Models\Auctions\AuctionLot;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionExtended implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $afterCommit = true;

    public $lot;
    public $newEndsAt;
    public $reason;
    public $extensionsUsed;

    /**
     * Create a new event instance.
     */
    public function __construct(AuctionLot $lot, string $reason = 'manual')
    {
        $this->lot = $lot;
        $this->newEndsAt = $lot->ends_at;
        $this->reason = $reason;
        $this->extensionsUsed = $lot->extensions_used;
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
        return 'auction.extended';
    }

    public function broadcastWith(): array
    {
        return [
            'lot_id' => $this->lot->id,
            'new_ends_at' => $this->newEndsAt->toIso8601String(),
            'reason' => $this->reason,
            'extensions_used' => $this->extensionsUsed,
        ];
    }
}
