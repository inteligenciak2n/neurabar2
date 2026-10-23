<?php

namespace App\Actions\Settings;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use App\Models\Settings\ServiceLocation;
use Illuminate\Validation\ValidationException;

class DeleteServiceLocationAction
{
    public function execute(ServiceLocation $location): void
    {
        $inUse = Attendance::query()
            ->where('service_location_id', $location->id)
            ->where('status', AttendanceStatus::Open)
            ->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'location' => 'This location is in use on an open attendance.',
            ]);
        }

        $location->delete();
    }
}
