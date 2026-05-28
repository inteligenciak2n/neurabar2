<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Models\User;

class DeleteUserAction
{
    public function execute(User $user, string $venueId): void
    {
        $pivotRole = $user->venues()->wherePivot('venue_id', $venueId)->first()?->pivot?->role;

        abort_if(
            $pivotRole && in_array($pivotRole, UserRole::platformRoles(), true),
            403,
            'Cannot delete users with platform roles from venue settings.'
        );

        $user->venues()->detach($venueId);
    }
}
