<?php

namespace App\Actions\Settings;

use App\Enums\ProfileEnum;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    public function execute(User $user, UpdateUserRequest $request): User
    {
        $data = $request->validated();

        if (isset($data['role'])) {
            // Venue settings may only assign operational roles; a platform
            // profile string would grant backoffice access to a tenant user.
            abort_if(
                in_array($data['role'], ProfileEnum::platformProfiles(), true),
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
