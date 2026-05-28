<?php

namespace App\Http\Controllers;

use App\Actions\Corporation\CreateVenueAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class NoVenueController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('NoVenue', [
            'userName' => $user->name,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        $user = $request->user();
        $corporation = $user->ownedCorporation;

        abort_unless($corporation !== null, 403, 'Nenhuma corporation encontrada para este usuário.');

        $action = app(CreateVenueAction::class);
        $venue = $action->execute($corporation, $data);

        $user->current_venue_id = $venue->id;
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', 'Venue criada com sucesso!');
    }
}
