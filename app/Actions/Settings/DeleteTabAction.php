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
                'tab' => 'This tab is in use on an open attendance.',
            ]);
        }

        $series = $tab->series;
        $tab->delete();

        if ($series !== null && $series->tabs()->doesntExist()) {
            $series->delete();
        }
    }
}
