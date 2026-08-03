<?php

namespace App\Actions\Settings;

use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Models\User;

class DeleteUserAction
{
    public function execute(User $user, string $venueId): void
    {
        $pivotRole = $user->venues()->wherePivot('venue_id', $venueId)->first()?->pivot?->role;
        $pivotRoleValue = $pivotRole instanceof UserRole ? $pivotRole->value : $pivotRole;

        abort_if(
            $pivotRoleValue !== null && in_array($pivotRoleValue, ProfileEnum::platformProfiles(), true),
            403,
            'Cannot delete users with platform roles from venue settings.'
        );

        $user->venues()->detach($venueId);
    }
}
