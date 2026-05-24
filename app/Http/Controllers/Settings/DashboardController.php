<?php

namespace App\Http\Controllers\Settings;

use App\Models\Orders\Attendance;
use App\Models\Orders\OrderItem;
use App\Models\Payment\Payment;
use App\Models\Settings\KitchenStation;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function index(): Response
    {
        $venueId = app('tenant')->id;

        $openAttendancesCount = Attendance::open()->count();

        $itemsInPreparation = OrderItem::whereNull('ready_at')
            ->whereHas(
                'order.attendance',
                fn ($q) => $q->where('venue_id', $venueId)->where('status', 'open')
            )
            ->count();

        $todaysRevenue = Payment::today()
            ->whereHas('attendance', fn ($q) => $q->where('venue_id', $venueId))
            ->sum('grand_total');

        $attendancesList = Attendance::open()
            ->with(['serviceLocation', 'orders'])
            ->latest()
            ->take(20)
            ->get();

        $stationsSummary = KitchenStation::active()
            ->get()
            ->map(function (KitchenStation $station) use ($venueId) {
                $pendingCount = OrderItem::whereNull('ready_at')
                    ->whereHas('product', fn ($q) => $q->where('kitchen_station_id', $station->id))
                    ->whereHas(
                        'order.attendance',
                        fn ($q) => $q->where('venue_id', $venueId)->where('status', 'open')
                    )
                    ->count();

                return array_merge($station->toArray(), ['pending_items_count' => $pendingCount]);
            });

        return Inertia::render('Dashboard', [
            'open_attendances_count' => $openAttendancesCount,
            'items_in_preparation' => $itemsInPreparation,
            'todays_revenue' => (float) $todaysRevenue,
            'attendances_list' => $attendancesList,
            'stations_summary' => $stationsSummary,
        ]);
    }
}
