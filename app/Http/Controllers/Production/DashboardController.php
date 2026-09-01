<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardPeriodRequest;
use App\Services\Production\ProductionMetricsService;
use App\Support\DateRangeResolver;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DateRangeResolver $dateRangeResolver,
        private readonly ProductionMetricsService $metrics,
    ) {}

    public function index(DashboardPeriodRequest $request): Response
    {
        $venue = app('tenant');
        $connection = app()->bound('operational_connection')
            ? app('operational_connection')
            : 'operation_default_1';

        $range = $this->dateRangeResolver->resolve($request->period(), $request->fromDate(), $request->toDate());

        return Inertia::render('Production/Index', [
            'filters' => [
                'period' => $request->period(),
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
            ],
            'metrics' => $this->metrics->forVenue($connection, $venue, $range['from'], $range['to']),
        ]);
    }
}
