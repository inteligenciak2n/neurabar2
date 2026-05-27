<?php

namespace App\Events\Orders;

use App\Models\Orders\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(public Order $order) {}

    public function broadcastAs(): string
    {
        return 'OrderPlaced';
    }

    /** @return array<int, Channel|PrivateChannel> */
    public function broadcastOn(): array
    {
        $venueId = $this->order->attendance->venue_id;

        return [
            new PrivateChannel("venue.{$venueId}.kitchen"),
            new Channel("venue.{$venueId}.display"),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order' => $this->order->load('items.product', 'attendance'),
        ];
    }
}
