<?php

namespace App\Actions\Settings;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use App\Models\Settings\Tab;
use Illuminate\Validation\ValidationException;

class DeleteTabAction
{
    public function execute(Tab $tab): void
    {
        $inUse = Attendance::query()
            ->where('tab_id', $tab->id)
            ->where('status', AttendanceStatus::Open)
            ->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'tab' => 'Cannot delete this tab while an attendance is open. Close the attendance first.',
            ]);
        }

        $series = $tab->series;
        $tab->delete();

        if ($series !== null && $series->tabs()->doesntExist()) {
            $series->delete();
        }
    }
}
