<?php

namespace App\Events\Kitchen;

use App\Models\Orders\Order;
use App\Models\Settings\KitchenStation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    /**
     * @param  array<int, mixed>  $items
     */
    public function __construct(
        public Order $order,
        public KitchenStation $station,
        public array $items
    ) {}

    public function broadcastAs(): string
    {
        return 'NewOrderReceived';
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("venue.{$this->order->attendance->venue_id}.station.{$this->station->id}"),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_identifier' => $this->order->attendance->customer_identifier,
            'station_id' => $this->station->id,
            'items' => $this->items,
        ];
    }
}
