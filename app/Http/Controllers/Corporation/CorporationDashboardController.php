<?php

namespace App\Http\Controllers\Corporation;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CorporationDashboardController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');
        $venues = $venue->corporation->venues()
            ->withCount(['attendances' => fn ($q) => $q->whereDate('created_at', today())])
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'active' => $v->active,
                'today_attendances' => $v->attendances_count,
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
