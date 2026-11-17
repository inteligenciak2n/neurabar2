<?php

namespace App\Actions\Orders;

use App\Models\GuestSession;
use App\Models\Orders\Attendance;
use App\Models\Orders\Order;
use App\Models\Settings\Tab;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LinkAttendanceTabAction
{
    public function execute(Attendance $attendance, string $tabId): Attendance
    {
        if ($attendance->hasTab()) {
            throw ValidationException::withMessages([
                'tab_id' => 'This attendance already has a tab.',
            ]);
        }

        $tab = Tab::query()
            ->whereKey($tabId)
            ->where('active', true)
            ->first();

        if ($tab === null) {
            throw ValidationException::withMessages([
                'tab_id' => 'This tab is not available.',
            ]);
        }

        $target = Attendance::query()
            ->open()
            ->where('tab_id', $tab->id)
            ->first();

        if ($target !== null && $target->id !== $attendance->id) {
            if ($attendance->service_location_id === null
                || $target->service_location_id !== $attendance->service_location_id) {
                throw ValidationException::withMessages([
                    'tab_id' => 'This tab is already in use.',
                ]);
            }

            return $this->mergeInto($attendance, $target);
        }

        $attendance->update(['tab_id' => $tab->id]);

        return $attendance->fresh(['tab']);
    }

    private function mergeInto(Attendance $source, Attendance $target): Attendance
    {
        return DB::connection(OperationalConnection::current())->transaction(function () use ($source, $target): Attendance {
            $nextNumber = (int) Order::query()
                ->where('attendance_id', $target->id)
                ->max('order_number');

            $source->load('orders');

            foreach ($source->orders as $order) {
                $nextNumber++;
                $order->update([
                    'attendance_id' => $target->id,
                    'order_number' => $nextNumber,
                ]);
            }

            GuestSession::withoutGlobalScopes()
                ->where('attendance_id', $source->id)
                ->update(['attendance_id' => $target->id]);

            $source->delete();

            return $target->fresh(['tab']);
        });
    }
}
