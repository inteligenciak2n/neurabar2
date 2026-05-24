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

        if ($user->corporation_id) {
            $venue = Venue::where('id', $id)
                ->where('corporation_id', $user->corporation_id)
                ->where('active', true)
                ->firstOrFail();
        } else {
            // Owner without a corporation — can only select their directly-assigned venue
            abort_unless($user->venue_id === $id, 403);
            $venue = Venue::where('id', $id)->where('active', true)->firstOrFail();
        }

        $request->session()->put('active_venue_id', $venue->id);

        return redirect()->route('dashboard');
    }
}
