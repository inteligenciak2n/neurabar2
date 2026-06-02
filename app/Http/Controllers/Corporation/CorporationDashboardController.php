<?php

namespace App\Http\Controllers\Corporation;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CorporationDashboardController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');
        $venueList = $venue->corporation->venues()->get();

        $operationalConnection = app()->bound('operational_connection')
            ? app('operational_connection')
            : 'operation_default_1';

        $venueIds = $venueList->pluck('id');
        $todayCountsByVenue = DB::connection($operationalConnection)
            ->table('attendances')
            ->whereIn('venue_id', $venueIds)
            ->whereDate('created_at', today())
            ->selectRaw('venue_id, count(*) as attendance_count')
            ->groupBy('venue_id')
            ->pluck('attendance_count', 'venue_id');

        $venues = $venueList->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'active' => $v->active,
            'today_attendances' => $todayCountsByVenue[$v->id] ?? 0,
        ]);

        return Inertia::render('Corporation/Dashboard', [
            'venues' => $venues,
            'currentVenueId' => $venue->id,
        ]);
    }

    public function switchVenue(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $venue = app('tenant');

        $hasAccess = $user->venues()
            ->wherePivot('venue_id', $id)
            ->where('corporation_id', $venue->corporation_id)
            ->exists();

        abort_unless($hasAccess, 403, 'Acesso não autorizado a esta venue.');

        $user->current_venue_id = $id;
        $user->save();

        return redirect()->route('corporation.dashboard')
            ->with('venue_switched', true);
    }
}
