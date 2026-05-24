<?php

namespace App\Events\Kitchen;

use App\Models\Orders\OrderItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(public OrderItem $item) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("venue.{$this->item->order->attendance->venue_id}.kitchen"),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'item_id' => $this->item->id,
            'preparation_status_id' => $this->item->preparation_status_id,
            'ready_at' => $this->item->ready_at?->toISOString(),
        ];
    }
}
