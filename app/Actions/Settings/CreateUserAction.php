<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    /** @var list<UserRole> */
    private const RESTRICTED_ROLES = [UserRole::SuperAdmin, UserRole::CorporationAdmin];

    public function execute(Venue $venue, StoreUserRequest $request): User
    {
        $data = $request->validated();

        abort_if(
            in_array(UserRole::from($data['role']), self::RESTRICTED_ROLES, true),
            403,
            'Cannot create users with this role from venue settings.'
        );

        return User::create([
            'venue_id' => $venue->id,
            'corporation_id' => $venue->corporation_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'pin' => $data['pin'] ?? null,
            'active' => $data['active'] ?? true,
        ]);
    }
}
