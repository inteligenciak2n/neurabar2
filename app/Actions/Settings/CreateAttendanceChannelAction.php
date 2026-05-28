<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreAttendanceChannelRequest;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;

class CreateAttendanceChannelAction
{
    public function execute(Venue $venue, StoreAttendanceChannelRequest $request): AttendanceChannel
    {
        return AttendanceChannel::create([
            'venue_id' => $venue->id,
            ...$request->validated(),
        ]);
    }
}
