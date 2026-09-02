<?php

namespace App\Events\Orders;

use App\Models\Orders\ServiceRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(public ServiceRequest $serviceRequest) {}

    public function broadcastAs(): string
    {
        return 'ServiceRequestUpdated';
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("venue.{$this->serviceRequest->venue_id}.service-requests")];

        if ($this->serviceRequest->assigned_user_id) {
            $channels[] = new PrivateChannel("App.Models.User.{$this->serviceRequest->assigned_user_id}");
        }

        return $channels;
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $this->serviceRequest->loadMissing('serviceLocation');

        return [
            'id' => $this->serviceRequest->id,
            'type' => $this->serviceRequest->type->value,
            'message' => $this->serviceRequest->message,
            'status' => $this->serviceRequest->status->value,
            'location_name' => $this->serviceRequest->serviceLocation?->name,
            'service_location_id' => $this->serviceRequest->service_location_id,
            'attendance_id' => $this->serviceRequest->attendance_id,
            'assigned_user_id' => $this->serviceRequest->assigned_user_id,
            'acknowledged_by' => $this->serviceRequest->acknowledged_by,
            'resolved_by' => $this->serviceRequest->resolved_by,
            'created_at' => $this->serviceRequest->created_at?->toISOString(),
        ];
    }
}
