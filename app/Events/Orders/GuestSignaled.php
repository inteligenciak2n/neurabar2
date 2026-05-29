<?php

namespace App\Events\Orders;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuestSignaled implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public readonly string $venueId,
        public readonly string $locationName,
        public readonly ?string $message,
        public readonly bool $signalOnly,
    ) {}

    public function broadcastAs(): string
    {
        return 'GuestSignaled';
    }

    /** @return array<int, Channel|PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("venue.{$this->venueId}.kitchen"),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'location_name' => $this->locationName,
            'message' => $this->message,
            'signal_only' => $this->signalOnly,
            'timestamp' => now()->toISOString(),
        ];
    }
}
