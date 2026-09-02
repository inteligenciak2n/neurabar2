<?php

namespace App\Actions\Orders;

use App\Enums\AttendanceStatus;
use App\Events\Orders\ServiceRequestUpdated;
use App\Models\Orders\Attendance;
use App\Models\Orders\ServiceRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ClaimAttendanceAction
{
    public function execute(Attendance $attendance, User $user): Attendance
    {
        if ($attendance->status !== AttendanceStatus::Open) {
            throw ValidationException::withMessages([
                'attendance' => 'Only open attendances can be claimed.',
            ]);
        }

        if ($attendance->claimed_by_user_id !== null && $attendance->claimed_by_user_id !== $user->id) {
            throw ValidationException::withMessages([
                'attendance' => 'This session is already assigned to another attendant.',
            ]);
        }

        $attendance->update(['claimed_by_user_id' => $user->id]);

        ServiceRequest::open()
            ->where('attendance_id', $attendance->id)
            ->whereNull('assigned_user_id')
            ->get()
            ->each(function (ServiceRequest $serviceRequest) use ($user): void {
                $serviceRequest->update(['assigned_user_id' => $user->id]);

                event(new ServiceRequestUpdated($serviceRequest));
            });

        return $attendance;
    }
}
