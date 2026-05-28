<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Mail\VenueInvitationMail;
use App\Models\Tenant\Venue;
use App\Models\User;
use App\Models\VenueInvitation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InviteUserToVenueAction
{
    public function execute(Venue $venue, string $email, UserRole $role, ?User $invitedBy = null): VenueInvitation
    {
        $invitation = VenueInvitation::create([
            'email' => $email,
            'venue_id' => $venue->id,
            'role' => $role,
            'invited_by_id' => $invitedBy?->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
        ]);

        Mail::to($email)->send(new VenueInvitationMail($invitation));

        return $invitation;
    }
}
