<?php

namespace App\Actions\Settings;

use App\Models\User;
use App\Models\VenueInvitation;
use Illuminate\Validation\ValidationException;

class AcceptVenueInvitationAction
{
    public function execute(VenueInvitation $invitation, User $user): void
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'token' => ['Este convite já foi aceito.'],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['Este convite expirou.'],
            ]);
        }

        if (strcasecmp($user->email, $invitation->email) !== 0) {
            throw ValidationException::withMessages([
                'token' => ['Este convite não pertence à sua conta.'],
            ]);
        }

        $invitation->venue->users()->syncWithoutDetaching([
            $user->id => ['role' => $invitation->role->value],
        ]);

        if (! $user->current_venue_id) {
            $user->current_venue_id = $invitation->venue_id;
            $user->save();
        }

        $invitation->update(['accepted_at' => now()]);
    }
}
