<?php

namespace App\Actions\Orders;

use App\Enums\AttendanceStatus;
use App\Http\Requests\Orders\StoreAttendanceRequest;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use Illuminate\Validation\ValidationException;

class OpenAttendanceAction
{
    public function execute(Venue $venue, StoreAttendanceRequest $request): Attendance
    {
        $data = $request->validated();

        $channel = AttendanceChannel::find($data['attendance_channel_id']);

        if ($channel?->requires_customer_identifier && empty($data['customer_identifier'])) {
            throw ValidationException::withMessages([
                'customer_identifier' => 'Customer identifier is required for this channel.',
            ]);
        }

        return Attendance::create([
            'venue_id' => $venue->id,
            'attendance_channel_id' => $data['attendance_channel_id'],
            'customer_identifier' => $data['customer_identifier'] ?? null,
            'service_location_id' => $data['service_location_id'] ?? null,
            'party_size' => $data['party_size'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => AttendanceStatus::Open,
            'created_by' => $request->user()->id,
        ]);
    }
}
