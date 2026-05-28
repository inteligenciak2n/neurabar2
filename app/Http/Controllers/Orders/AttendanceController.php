<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\OpenAttendanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreAttendanceRequest;
use App\Http\Requests\Orders\UpdateAttendanceRequest;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        $attendances = Attendance::open()
            ->with(['serviceLocation', 'createdBy', 'orders' => fn ($q) => $q->latest(), 'orders.items'])
            ->latest()
            ->get();

        $serviceLocations = ServiceLocation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->get(['id', 'name', 'type']);

        $channels = AttendanceChannel::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'value']);

        return Inertia::render('Attendances/Index', [
            'attendances' => $attendances,
            'serviceLocations' => $serviceLocations,
            'channels' => $channels,
            'venueId' => $venue->id,
        ]);
    }

    public function store(StoreAttendanceRequest $request, OpenAttendanceAction $action)
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return redirect()->route('attendances.index');
    }

    public function show(Attendance $attendance): Response
    {
        $venue = app('tenant');

        $attendance->load([
            'orders' => fn ($q) => $q->latest(), 
            'orders.items.product', 
            'orders.items.preparationStatus', 
            'serviceLocation', 
            'createdBy',
            ]);

        return Inertia::render('Attendances/Show', [
            'attendance' => $attendance,
            'venueId' => $venue->id,
        ]);
    }

    public function orders(Attendance $attendance): JsonResponse
    {
        $attendance->load(['orders' => fn ($q) => $q->latest(), 'orders.items.product', 'orders.items.preparationStatus']);

        return response()->json($attendance->orders);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $attendance->update($request->validated());

        return redirect()->route('attendances.show', $attendance->id);
    }
}
