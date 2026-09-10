<?php

namespace App\Actions\Orders;

use App\Events\Orders\ServiceRequestUpdated;
use App\Models\Orders\Attendance;
use App\Models\Orders\ServiceRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReleaseAttendanceAction
{
    public function execute(Attendance $attendance, User $user): Attendance
    {
        if ($attendance->claimed_by_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'attendance' => 'You are not assigned to this session.',
            ]);
        }

        $attendance->update(['claimed_by_user_id' => null]);

        ServiceRequest::open()
            ->where('attendance_id', $attendance->id)
            ->where('assigned_user_id', $user->id)
            ->get()
            ->each(function (ServiceRequest $serviceRequest): void {
                $serviceRequest->update(['assigned_user_id' => null]);

                event(new ServiceRequestUpdated($serviceRequest));
            });

        return $attendance;
    }
}
