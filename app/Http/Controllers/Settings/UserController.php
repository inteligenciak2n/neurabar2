<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\CreateUserAction;
use App\Actions\Settings\DeleteUserAction;
use App\Actions\Settings\UpdateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $venue = app('tenant');

        $users = $venue->users()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email', 'users.active'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => $user->active,
                'role' => $user->pivot->role,
            ]);

        return Inertia::render('Settings/Users', [
            'users' => $users,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $result = $action->execute($venue, $request);

        if ($result === 'invitation_sent') {
            return back()->with('success', 'Invitation sent.');
        }

        return back()->with('success', 'User created.');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $venue = app('tenant');
        abort_unless($venue->users()->wherePivot('user_id', $user->id)->exists(), 403);

        $action->execute($user, $request);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user, DeleteUserAction $action): RedirectResponse
    {
        $venue = app('tenant');
        abort_unless($venue->users()->wherePivot('user_id', $user->id)->exists(), 403);

        $action->execute($user, $venue->id);

        return back()->with('success', 'User deleted.');
    }
}
