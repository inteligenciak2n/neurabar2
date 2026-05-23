<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VenueSelectorController extends Controller
{
    public function store(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();

        $venue = Venue::where('id', $id)
            ->where('corporation_id', $user->corporation_id)
            ->where('active', true)
            ->firstOrFail();

        $request->session()->put('active_venue_id', $venue->id);

        return redirect()->route('dashboard');
    }
}
