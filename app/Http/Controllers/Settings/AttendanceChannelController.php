<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateAttendanceChannelAction;
use App\Actions\Settings\DeleteAttendanceChannelAction;
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
        return Inertia::render('Settings/AttendanceChannels', [
            'channels' => app('tenant')->attendanceChannels()->orderBy('sort_order')->get(),
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
}
