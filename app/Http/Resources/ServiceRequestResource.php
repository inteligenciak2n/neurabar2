<?php

namespace App\Http\Resources;

use App\Models\Orders\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceRequest */
class ServiceRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'status' => $this->status,
            'assigned_user_id' => $this->assigned_user_id,
            'attendance_id' => $this->attendance_id,
            'created_at' => $this->created_at,
            'service_location' => $this->whenLoaded('serviceLocation', fn () => [
                'id' => $this->serviceLocation->id,
                'name' => $this->serviceLocation->name,
            ]),
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => $this->assignedUser && [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
            ]),
            'acknowledged_by' => $this->whenLoaded('acknowledgedBy', fn () => $this->acknowledgedBy && [
                'id' => $this->acknowledgedBy->id,
                'name' => $this->acknowledgedBy->name,
            ]),
        ];
    }
}
