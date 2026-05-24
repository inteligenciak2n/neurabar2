<?php

namespace App\Actions\Orders;

use App\Models\Orders\Attendance;
use Illuminate\Validation\ValidationException;

class CloseAttendanceAction
{
    public function execute(Attendance $attendance): Attendance
    {
        if ($attendance->status !== 'open') {
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
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $attendance->fresh();
    }
}
