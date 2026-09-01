<?php

namespace App\Services\Production;

use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionMetricsService
{
    public function forVenue(string $connection, Venue $venue, Carbon $from, Carbon $to): array
    {
        return [
            'top_items' => $this->topItems($connection, $venue, $from, $to),
            'peak_hours' => $this->peakHours($connection, $venue, $from, $to),
            'peak_weekdays' => $this->peakWeekdays($connection, $venue, $from, $to),
            'station_speed' => $this->stationSpeed($connection, $venue, $from, $to),
            'top_attendants' => $this->topAttendants($connection, $venue, $from, $to),
        ];
    }

    /**
     * @return array<int, array{product_id: string, name: string, quantity: int, revenue: float}>
     */
    private function topItems(string $connection, Venue $venue, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('attendances', 'attendances.id', '=', 'orders.attendance_id')
            ->where('attendances.venue_id', $venue->id)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as quantity, sum(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get();

        $products = Product::whereIn('id', $rows->pluck('product_id'))->get(['id', 'name'])->keyBy('id');

        return $rows->map(fn ($row): array => [
            'product_id' => $row->product_id,
            'name' => $products->get($row->product_id)?->name ?? __('Removed product'),
            'quantity' => (int) $row->quantity,
            'revenue' => (float) $row->revenue,
        ])->values()->all();
    }

    /**
     * @return array<int, array{hour: int, orders_count: int}>
     */
    private function peakHours(string $connection, Venue $venue, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('orders')
            ->join('attendances', 'attendances.id', '=', 'orders.attendance_id')
            ->where('attendances.venue_id', $venue->id)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('extract(hour from orders.created_at)::int as hour, count(*) as orders_count')
            ->groupBy(DB::raw('extract(hour from orders.created_at)'))
            ->get()
            ->keyBy('hour');

        return collect(range(0, 23))->map(fn (int $hour): array => [
            'hour' => $hour,
            'orders_count' => isset($rows[$hour]) ? (int) $rows[$hour]->orders_count : 0,
        ])->values()->all();
    }

    /**
     * @return array<int, array{weekday: int, orders_count: int}>
     */
    private function peakWeekdays(string $connection, Venue $venue, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('orders')
            ->join('attendances', 'attendances.id', '=', 'orders.attendance_id')
            ->where('attendances.venue_id', $venue->id)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('extract(dow from orders.created_at)::int as weekday, count(*) as orders_count')
            ->groupBy(DB::raw('extract(dow from orders.created_at)'))
            ->get()
            ->keyBy('weekday');

        // Postgres DOW: 0=domingo .. 6=sábado
        return collect(range(0, 6))->map(fn (int $weekday): array => [
            'weekday' => $weekday,
            'orders_count' => isset($rows[$weekday]) ? (int) $rows[$weekday]->orders_count : 0,
        ])->values()->all();
    }

    /**
     * @return array<int, array{kitchen_station_id: string, name: string, avg_minutes: float, items_count: int}>
     */
    private function stationSpeed(string $connection, Venue $venue, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('attendances', 'attendances.id', '=', 'orders.attendance_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('attendances.venue_id', $venue->id)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotNull('order_items.ready_at')
            ->whereNotNull('products.kitchen_station_id')
            ->selectRaw('products.kitchen_station_id, avg(extract(epoch from (order_items.ready_at - order_items.created_at)) / 60) as avg_minutes, count(*) as items_count')
            ->groupBy('products.kitchen_station_id')
            ->get();

        $stations = KitchenStation::whereIn('id', $rows->pluck('kitchen_station_id'))->get(['id', 'name'])->keyBy('id');

        return $rows->map(fn ($row): array => [
            'kitchen_station_id' => $row->kitchen_station_id,
            'name' => $stations->get($row->kitchen_station_id)?->name ?? __('Removed station'),
            'avg_minutes' => round((float) $row->avg_minutes, 1),
            'items_count' => (int) $row->items_count,
        ])->sortByDesc('avg_minutes')->values()->all();
    }

    /**
     * @return array<int, array{user_id: string, name: string, attendances_count: int, revenue: float}>
     */
    private function topAttendants(string $connection, Venue $venue, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('attendances')
            ->leftJoin('payments', 'payments.attendance_id', '=', 'attendances.id')
            ->where('attendances.venue_id', $venue->id)
            ->whereNotNull('attendances.created_by')
            ->whereBetween('attendances.created_at', [$from, $to])
            ->selectRaw('attendances.created_by, count(*) as attendances_count, coalesce(sum(payments.grand_total), 0) as revenue')
            ->groupBy('attendances.created_by')
            ->orderByDesc('attendances_count')
            ->limit(10)
            ->get();

        $users = User::whereIn('id', $rows->pluck('created_by'))->get(['id', 'name'])->keyBy('id');

        return $rows->map(fn ($row): array => [
            'user_id' => $row->created_by,
            'name' => $users->get($row->created_by)?->name ?? __('Removed user'),
            'attendances_count' => (int) $row->attendances_count,
            'revenue' => (float) $row->revenue,
        ])->values()->all();
    }
}
