<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdateAttendanceChannelRequest;
use App\Models\Settings\AttendanceChannel;

class UpdateAttendanceChannelAction
{
    public function execute(AttendanceChannel $channel, UpdateAttendanceChannelRequest $request): AttendanceChannel
    {
        $channel->update($request->validated());

        return $channel->fresh();
    }
}
