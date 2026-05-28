<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VenueSelectorController extends Controller
{
    public function store(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();

        $hasAccess = $user->venues()
            ->wherePivot('venue_id', $id)
            ->where('active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Acesso não autorizado a esta venue.');

        $user->current_venue_id = $id;
        $user->save();

        return redirect()->route('dashboard')
            ->with('venue_switched', true);
    }
}
