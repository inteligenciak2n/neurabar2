<?php

namespace App\Http\Controllers\DirectWaiter;

use App\Enums\ServiceRequestType;
use App\Http\Controllers\Controller;
use App\Models\Orders\ServiceRequest;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $requests = ServiceRequest::open()
            ->ofType(ServiceRequestType::Message)
            ->with(['serviceLocation:id,name', 'assignedUser:id,name', 'acknowledgedBy:id,name'])
            ->latest()
            ->get();

        return Inertia::render('DirectWaiter/Index', [
            'requests' => $requests,
        ]);
    }
}
