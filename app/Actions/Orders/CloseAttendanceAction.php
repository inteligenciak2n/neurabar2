<?php

namespace App\Actions\Orders;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use Illuminate\Validation\ValidationException;

class CloseAttendanceAction
{
    public function execute(Attendance $attendance): Attendance
    {
        if ($attendance->status !== AttendanceStatus::Open) {
            throw ValidationException::withMessages([
                'attendance' => 'Only open attendances can be closed.',
            ]);
        }

        if (! $attendance->payment()->exists()) {
            throw ValidationException::withMessages([
                'payment' => 'A payment must be registered before closing the attendance.',
            ]);
        }

        $attendance->update([
            'status' => AttendanceStatus::Closed,
            'closed_at' => now(),
            // reset the claim so the next guest at this location can be picked up by anyone.
            'claimed_by_user_id' => null,
        ]);

        return $attendance->fresh();
    }
}
