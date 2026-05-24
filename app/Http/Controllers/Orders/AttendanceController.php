<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\OpenAttendanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreAttendanceRequest;
use App\Http\Requests\Orders\UpdateAttendanceRequest;
use App\Models\Orders\Attendance;
use App\Models\Settings\ServiceLocation;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        $attendances = Attendance::open()
            ->with(['serviceLocation', 'createdBy', 'orders'])
            ->latest()
            ->get();

        $serviceLocations = ServiceLocation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->get(['id', 'name']);

        return Inertia::render('Attendances/Index', [
            'attendances' => $attendances,
            'serviceLocations' => $serviceLocations,
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
        $attendance->load(['orders.items.product', 'orders.items.preparationStatus', 'serviceLocation', 'createdBy']);

        return Inertia::render('Attendances/Show', [
            'attendance' => $attendance,
        ]);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $attendance->update($request->validated());

        return redirect()->route('attendances.show', $attendance->id);
    }
}
