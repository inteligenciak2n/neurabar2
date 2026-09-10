<?php

namespace App\Services\Finance;

use App\Models\Tenant\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialMetricsService
{
    public function forVenue(string $connection, Venue $venue, Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $venueIds = [$venue->id];

        $current = $this->aggregate($connection, $venueIds, $from, $to);
        $previous = $this->aggregate($connection, $venueIds, $previousFrom, $previousTo);

        return [
            'gross_revenue' => $current['gross_revenue'],
            'average_ticket' => $current['average_ticket'],
            'attendances_count' => $current['attendances_count'],
            'payment_method_breakdown' => $this->paymentMethodBreakdown($connection, $venueIds, $from, $to),
            'revenue_trend' => $this->revenueTrend($connection, $venueIds, $from, $to),
            'previous_period' => $this->percentChanges($current, $previous),
        ];
    }

    /**
     * @param  Collection<int, Venue>  $venues
     */
    public function forCorporation(string $connection, Collection $venues, Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $venueIds = $venues->pluck('id')->all();

        $current = $this->aggregate($connection, $venueIds, $from, $to);
        $previous = $this->aggregate($connection, $venueIds, $previousFrom, $previousTo);

        return [
            'gross_revenue' => $current['gross_revenue'],
            'average_ticket' => $current['average_ticket'],
            'attendances_count' => $current['attendances_count'],
            'payment_method_breakdown' => $this->paymentMethodBreakdown($connection, $venueIds, $from, $to),
            'revenue_trend' => $this->revenueTrend($connection, $venueIds, $from, $to),
            'previous_period' => $this->percentChanges($current, $previous),
            'venues_breakdown' => $this->venuesBreakdown($connection, $venues, $from, $to),
        ];
    }

    /**
     * @param  array<int, string>  $venueIds
     * @return array{gross_revenue: float, average_ticket: float, attendances_count: int}
     */
    private function aggregate(string $connection, array $venueIds, Carbon $from, Carbon $to): array
    {
        $row = DB::connection($connection)
            ->table('payments')
            ->join('attendances', 'attendances.id', '=', 'payments.attendance_id')
            ->whereIn('attendances.venue_id', $venueIds)
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw('coalesce(sum(payments.grand_total), 0) as gross_revenue, count(*) as attendances_count')
            ->first();

        $grossRevenue = (float) $row->gross_revenue;
        $attendancesCount = (int) $row->attendances_count;

        return [
            'gross_revenue' => $grossRevenue,
            'average_ticket' => $attendancesCount > 0 ? round($grossRevenue / $attendancesCount, 2) : 0.0,
            'attendances_count' => $attendancesCount,
        ];
    }

    /**
     * @param  array<int, string>  $venueIds
     * @return array<int, array{method: string, total: float, percent: float}>
     */
    private function paymentMethodBreakdown(string $connection, array $venueIds, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('payment_items')
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->join('attendances', 'attendances.id', '=', 'payments.attendance_id')
            ->whereIn('attendances.venue_id', $venueIds)
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw('payment_items.method, sum(payment_items.amount) as total')
            ->groupBy('payment_items.method')
            ->get();

        $grandTotal = (float) $rows->sum('total');

        return $rows->map(fn ($row): array => [
            'method' => $row->method,
            'total' => (float) $row->total,
            'percent' => $grandTotal > 0 ? round(((float) $row->total / $grandTotal) * 100, 1) : 0.0,
        ])->values()->all();
    }

    /**
     * @param  array<int, string>  $venueIds
     * @return array<int, array{date: string, total: float}>
     */
    private function revenueTrend(string $connection, array $venueIds, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('payments')
            ->join('attendances', 'attendances.id', '=', 'payments.attendance_id')
            ->whereIn('attendances.venue_id', $venueIds)
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw('date(payments.created_at) as date, sum(payments.grand_total) as total')
            ->groupBy(DB::raw('date(payments.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => (string) $row->date);

        // Preenche os dias sem receita com zero para o gráfico não ter buracos.
        $trend = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $trend[] = [
                'date' => $key,
                'total' => isset($rows[$key]) ? (float) $rows[$key]->total : 0.0,
            ];
            $cursor->addDay();
        }

        return $trend;
    }

    /**
     * @param  Collection<int, Venue>  $venues
     * @return array<int, array{venue_id: string, venue_name: string, gross_revenue: float, average_ticket: float, attendances_count: int}>
     */
    private function venuesBreakdown(string $connection, Collection $venues, Carbon $from, Carbon $to): array
    {
        $rows = DB::connection($connection)
            ->table('payments')
            ->join('attendances', 'attendances.id', '=', 'payments.attendance_id')
            ->whereIn('attendances.venue_id', $venues->pluck('id'))
            ->whereBetween('payments.created_at', [$from, $to])
            ->selectRaw('attendances.venue_id, coalesce(sum(payments.grand_total), 0) as gross_revenue, count(*) as attendances_count')
            ->groupBy('attendances.venue_id')
            ->get()
            ->keyBy('venue_id');

        return $venues->map(function (Venue $venue) use ($rows): array {
            $row = $rows->get($venue->id);
            $grossRevenue = $row ? (float) $row->gross_revenue : 0.0;
            $attendancesCount = $row ? (int) $row->attendances_count : 0;

            return [
                'venue_id' => $venue->id,
                'venue_name' => $venue->name,
                'gross_revenue' => $grossRevenue,
                'average_ticket' => $attendancesCount > 0 ? round($grossRevenue / $attendancesCount, 2) : 0.0,
                'attendances_count' => $attendancesCount,
            ];
        })->values()->all();
    }

    /**
     * @param  array{gross_revenue: float, average_ticket: float, attendances_count: int}  $current
     * @param  array{gross_revenue: float, average_ticket: float, attendances_count: int}  $previous
     * @return array{gross_revenue: float, average_ticket: float, attendances_count: float}
     */
    private function percentChanges(array $current, array $previous): array
    {
        $percentChange = fn (float $curr, float $prev): float => $prev > 0
            ? round((($curr - $prev) / $prev) * 100, 1)
            : ($curr > 0 ? 100.0 : 0.0);

        return [
            'gross_revenue' => $percentChange($current['gross_revenue'], $previous['gross_revenue']),
            'average_ticket' => $percentChange($current['average_ticket'], $previous['average_ticket']),
            'attendances_count' => $percentChange((float) $current['attendances_count'], (float) $previous['attendances_count']),
        ];
    }
}
