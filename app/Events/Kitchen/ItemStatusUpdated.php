<?php

namespace App\Events\Kitchen;

use App\Models\Orders\OrderItem;
use Illuminate\Broadcasting\Channel;
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

    public function broadcastAs(): string
    {
        return 'ItemStatusUpdated';
    }

    /** @return array<int, Channel|PrivateChannel> */
    public function broadcastOn(): array
    {
        $venueId = $this->item->order->attendance->venue_id;

        return [
            new PrivateChannel("venue.{$venueId}.kitchen"),
            new Channel("venue.{$venueId}.display"),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $status = $this->item->preparationStatus;

        return [
            'item_id' => $this->item->id,
            'order_id' => $this->item->order_id,
            'attendance_id' => $this->item->order->attendance_id,
            'preparation_status_id' => $this->item->preparation_status_id,
            'preparation_status' => $status ? [
                'name' => $status->name,
                'color' => $status->color,
                'is_final' => $status->is_final,
            ] : null,
            'ready_at' => $this->item->ready_at?->toISOString(),
        ];
    }
}
