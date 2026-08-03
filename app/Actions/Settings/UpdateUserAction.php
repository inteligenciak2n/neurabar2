<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    public function execute(User $user, UpdateUserRequest $request): User
    {
        $data = $request->validated();

        if (isset($data['role'])) {
            // Venue settings may only assign operational roles; platform roles
            // would grant backoffice access to a tenant user.
            abort_if(
                UserRole::from($data['role'])->isPlatform(),
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
