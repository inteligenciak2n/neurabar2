<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DashboardPeriodRequest;
use App\Services\Finance\FinancialMetricsService;
use App\Support\DateRangeResolver;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DateRangeResolver $dateRangeResolver,
        private readonly FinancialMetricsService $metrics,
    ) {}

    public function index(DashboardPeriodRequest $request): Response
    {
        $venue = app('tenant');
        $connection = app()->bound('operational_connection')
            ? app('operational_connection')
            : 'operation_default_1';

        $range = $this->dateRangeResolver->resolve($request->period(), $request->fromDate(), $request->toDate());

        $venues = $venue->corporation->venues()->get(['id', 'name']);
        $canViewCorporation = $venues->count() > 1;
        $scope = $canViewCorporation ? $request->scope() : 'venue';

        $metrics = $scope === 'corporation'
            ? $this->metrics->forCorporation($connection, $venues, $range['from'], $range['to'], $range['previous_from'], $range['previous_to'])
            : $this->metrics->forVenue($connection, $venue, $range['from'], $range['to'], $range['previous_from'], $range['previous_to']);

        return Inertia::render('Finance/Index', [
            'filters' => [
                'period' => $request->period(),
                'from' => $range['from']->toDateString(),
                'to' => $range['to']->toDateString(),
                'scope' => $scope,
            ],
            'canViewCorporation' => $canViewCorporation,
            'metrics' => $metrics,
        ]);
    }
}
