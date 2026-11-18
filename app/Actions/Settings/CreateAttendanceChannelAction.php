<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreAttendanceChannelRequest;
use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;

class CreateAttendanceChannelAction
{
    public function execute(Venue $venue, StoreAttendanceChannelRequest $request): AttendanceChannel
    {
        $data = $request->validated();
        $data['sort_order'] = (AttendanceChannel::query()->where('venue_id', $venue->id)->max('sort_order') ?? 0) + 1;

        return AttendanceChannel::create([
            'venue_id' => $venue->id,
            ...$data,
        ]);
    }
}
