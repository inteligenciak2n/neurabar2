<?php

namespace App\Actions\Settings;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use App\Models\Settings\TabSeries;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteTabSeriesAction
{
    public function execute(TabSeries $series): void
    {
        DB::connection(OperationalConnection::current())->transaction(function () use ($series): void {
            $inUse = Attendance::query()
                ->whereIn('tab_id', $series->tabs()->select('id'))
                ->where('status', AttendanceStatus::Open)
                ->exists();

            if ($inUse) {
                throw ValidationException::withMessages([
                    'tabs' => 'Some tabs in this prefix are in use on an open attendance.',
                ]);
            }

            $series->tabs()->delete();
            $series->delete();
        });
    }
}
