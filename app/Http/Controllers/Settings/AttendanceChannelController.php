<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateAttendanceChannelAction;
use App\Actions\Settings\DeleteAttendanceChannelAction;
use App\Actions\Settings\DeleteChannelServiceLocationsAction;
use App\Actions\Settings\UpdateAttendanceChannelAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreAttendanceChannelRequest;
use App\Http\Requests\Settings\UpdateAttendanceChannelRequest;
use App\Models\Settings\AttendanceChannel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceChannelController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Settings/AttendanceChannels', [
            'channels' => $venue->attendanceChannels()->orderBy('sort_order')->get(),
            'tabSeries' => $venue->tabSeries()
                ->with(['tabs' => fn ($query) => $query->orderByRaw('length(code), code')])
                ->orderBy('prefix')
                ->get(),
            'serviceLocations' => $venue->serviceLocations()
                ->orderBy('name')
                ->get(['id', 'name', 'default_attendance_channel_id']),
        ]);
    }

    public function store(StoreAttendanceChannelRequest $request, CreateAttendanceChannelAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back()->with('success', 'Attendance channel created.');
    }

    public function update(UpdateAttendanceChannelRequest $request, AttendanceChannel $channel, UpdateAttendanceChannelAction $action): RedirectResponse
    {
        $action->execute($channel, $request);

        return back()->with('success', 'Attendance channel updated.');
    }

    public function destroy(AttendanceChannel $channel, DeleteAttendanceChannelAction $action): RedirectResponse
    {
        $action->execute($channel);

        return back()->with('success', 'Attendance channel deleted.');
    }

    public function destroyLocations(AttendanceChannel $channel, DeleteChannelServiceLocationsAction $action): RedirectResponse
    {
        $action->execute($channel);

        return back()->with('success', 'Locations deleted.');
    }
}
