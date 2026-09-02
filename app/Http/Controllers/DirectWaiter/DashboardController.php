<?php

namespace App\Http\Controllers\DirectWaiter;

use App\Enums\ServiceRequestType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Orders\ServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $requests = ServiceRequest::open()
            ->ofType(ServiceRequestType::Message)
            ->where(fn ($query) => $query->whereNull('assigned_user_id')->orWhere('assigned_user_id', $request->user()->id))
            ->with(['serviceLocation:id,name', 'assignedUser:id,name', 'acknowledgedBy:id,name'])
            ->latest()
            ->get();

        return Inertia::render('DirectWaiter/Index', [
            'requests' => ServiceRequestResource::collection($requests),
        ]);
    }
}
