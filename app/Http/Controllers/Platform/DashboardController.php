<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\MetricsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(MetricsService $metrics): Response
    {
        return Inertia::render('Platform/Dashboard', [
            'summary' => $metrics->operationalSummary(),
        ]);
    }
}
