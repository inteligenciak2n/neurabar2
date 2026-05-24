<?php

namespace App\Actions\Orders;

use App\Http\Requests\Orders\StoreAttendanceRequest;
use App\Models\Orders\Attendance;
use App\Models\Tenant\Venue;
use Illuminate\Validation\ValidationException;

class OpenAttendanceAction
{
    public function execute(Venue $venue, StoreAttendanceRequest $request): Attendance
    {
        $data = $request->validated();

        if (
            $venue->require_table
            && ($data['channel'] ?? '') === 'table'
            && empty($data['customer_identifier'])
        ) {
            throw ValidationException::withMessages([
                'customer_identifier' => 'Table identifier is required when require_table is enabled.',
            ]);
        }

        return Attendance::create([
            'venue_id' => $venue->id,
            'channel' => $data['channel'],
            'customer_identifier' => $data['customer_identifier'] ?? null,
            'service_location_id' => $data['service_location_id'] ?? null,
            'party_size' => $data['party_size'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);
    }
}
