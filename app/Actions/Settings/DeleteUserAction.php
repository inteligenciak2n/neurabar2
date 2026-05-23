<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Models\User;

class DeleteUserAction
{
    /** @var list<UserRole> */
    private const RESTRICTED_ROLES = [UserRole::SuperAdmin, UserRole::CorporationAdmin];

    public function execute(User $user): void
    {
        abort_if(
            in_array($user->role, self::RESTRICTED_ROLES, true),
            403,
            'Cannot delete users with this role from venue settings.'
        );

        $user->delete();
    }
}
