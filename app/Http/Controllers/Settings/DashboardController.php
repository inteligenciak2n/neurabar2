<?php

namespace App\Http\Controllers\Settings;

use App\Enums\AttendanceStatus;
use App\Models\Orders\Attendance;
use App\Models\Orders\OrderItem;
use App\Models\Payment\Payment;
use App\Models\Settings\KitchenStation;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function index(): Response
    {
        $venueId = app('tenant')->id;

        $openAttendancesCount = Attendance::open()->count();

        $itemsInPreparation = OrderItem::inPreparation()
            ->whereHas(
                'order.attendance',
                fn ($q) => $q->where('venue_id', $venueId)->where('status', AttendanceStatus::Open)
            )
            ->count();

        $todaysRevenue = Payment::today()
            ->whereHas('attendance', fn ($q) => $q->where('venue_id', $venueId))
            ->sum('grand_total');

        $attendancesList = Attendance::open()
            ->with(['serviceLocation', 'orders', 'orders.items'])
            ->latest()
            ->take(20)
            ->get();

        $pendingByStation = OrderItem::inPreparation()
            ->whereHas(
                'order.attendance',
                fn ($q) => $q->where('venue_id', $venueId)->where('status', AttendanceStatus::Open)
            )
            ->whereHas('product')
            ->select(DB::raw('count(*) as pending_count'), 'products.kitchen_station_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('products.kitchen_station_id')
            ->pluck('pending_count', 'kitchen_station_id');

        $stationsSummary = KitchenStation::active()
            ->get()
            ->map(fn (KitchenStation $station) => array_merge(
                $station->toArray(),
                ['pending_items_count' => (int) ($pendingByStation[$station->id] ?? 0)]
            ));

        return Inertia::render('Dashboard', [
            'open_attendances_count' => $openAttendancesCount,
            'items_in_preparation' => $itemsInPreparation,
            'todays_revenue' => (float) $todaysRevenue,
            'attendances_list' => $attendancesList,
            'stations_summary' => $stationsSummary,
        ]);
    }
}
