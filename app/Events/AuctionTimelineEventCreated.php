<?php

namespace App\Events;

use App\Models\Auctions\AuctionEvent;
use App\Models\Auctions\AuctionLot;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionTimelineEventCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $lotId;
    public $eventModel;

    /**
     * Create a new event instance.
     */
    public function __construct(AuctionEvent $eventModel)
    {
        $this->eventModel = $eventModel;
        $this->lotId = $eventModel->auction_lot_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('auctions.lot.' . $this->lotId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'timeline.created';
    }

    public function broadcastWith(): array
    {
        // Format similar to what UI needs
        return [
            'lot_id' => $this->lotId,
            'event_type' => $this->eventModel->event_type,
            'actor_type' => $this->eventModel->actor_type,
            'actor_id' => $this->eventModel->actor_id,
            'payload' => $this->eventModel->payload,
            'created_at' => $this->eventModel->created_at->toIso8601String(),
            // Helper for simple display if needed, but frontend can format
        ];
    }
}
