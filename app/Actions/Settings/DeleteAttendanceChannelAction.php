<?php

namespace App\Actions\Settings;

use App\Models\Settings\AttendanceChannel;

class DeleteAttendanceChannelAction
{
    public function execute(AttendanceChannel $channel): void
    {
        $channel->delete();
    }
}
