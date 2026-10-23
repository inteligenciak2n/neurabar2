<?php

namespace App\Actions\Settings;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteChannelServiceLocationsAction
{
    public function execute(AttendanceChannel $channel): void
    {
        DB::connection(OperationalConnection::current())->transaction(function () use ($channel): void {
            $inUse = Attendance::query()
                ->whereIn('service_location_id', $channel->serviceLocations()->select('id'))
                ->where('status', AttendanceStatus::Open)
                ->exists();

            if ($inUse) {
                throw ValidationException::withMessages([
                    'locations' => 'Some locations are in use on an open attendance.',
                ]);
            }

            $channel->serviceLocations()->delete();
        });
    }
}
