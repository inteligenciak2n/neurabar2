<?php

namespace App\Actions\Settings;

use App\Enums\UserRole;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function execute(Venue $venue, StoreUserRequest $request): User|string
    {
        $data = $request->validated();
        $role = UserRole::from($data['role']);

        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            $action = app(InviteUserToVenueAction::class);
            $action->execute($venue, $data['email'], $role, $request->user());

            return 'invitation_sent';
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'pin' => $data['pin'] ?? null,
            'active' => $data['active'] ?? true,
            'onboarding_completed_at' => now(),
        ]);

        $venue->users()->attach($user->id, ['role' => $role->value]);

        $user->current_venue_id = $venue->id;
        $user->save();

        return $user;
    }
}
