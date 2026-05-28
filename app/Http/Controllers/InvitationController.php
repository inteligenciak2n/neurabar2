<?php

namespace App\Http\Controllers;

use App\Actions\Settings\AcceptVenueInvitationAction;
use App\Models\VenueInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = VenueInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('dashboard')
                ->with('info', 'Este convite já foi aceito.');
        }

        if ($invitation->isExpired()) {
            return Inertia::render('Invitations/Expired', [
                'venueName' => $invitation->venue->name,
            ]);
        }

        return Inertia::render('Invitations/Accept', [
            'token' => $token,
            'venueName' => $invitation->venue->name,
            'role' => $invitation->role->label(),
            'invitedBy' => $invitation->invitedBy?->name,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = VenueInvitation::where('token', $token)->firstOrFail();

        $action = app(AcceptVenueInvitationAction::class);
        $action->execute($invitation, $request->user());

        return redirect()->route('dashboard')
            ->with('success', 'Bem-vindo a '.$invitation->venue->name.'!');
    }
}
