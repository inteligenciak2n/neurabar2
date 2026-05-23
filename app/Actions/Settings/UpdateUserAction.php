<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    /** @var list<UserRole> */
    private const RESTRICTED_ROLES = [UserRole::SuperAdmin, UserRole::CorporationAdmin];

    public function execute(User $user, UpdateUserRequest $request): User
    {
        $data = $request->validated();

        if (isset($data['role'])) {
            abort_if(
                in_array(UserRole::from($data['role']), self::RESTRICTED_ROLES, true),
                403,
                'Cannot assign this role from venue settings.'
            );
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return $user->fresh();
    }
}
