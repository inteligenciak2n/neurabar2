<?php

namespace App\Http\Controllers\Corporation;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CorporationDashboardController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');
        $venues = Venue::where('corporation_id', $venue->corporation_id)
            ->with(['attendances' => fn ($q) => $q->whereDate('created_at', today())])
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'active' => $v->active,
                'today_attendances' => $v->attendances->count(),
            ]);

        return Inertia::render('Corporation/Dashboard', [
            'venues' => $venues,
            'currentVenueId' => $venue->id,
        ]);
    }

    public function switchVenue(Request $request, string $id): RedirectResponse
    {
        $currentVenue = app('tenant');
        $target = Venue::where('corporation_id', $currentVenue->corporation_id)
            ->findOrFail($id);

        $request->session()->put('active_venue_id', $target->id);

        return redirect()->route('corporation.dashboard')
            ->with('success', 'Venue switched to '.$target->name.'.');
    }
}
