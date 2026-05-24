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

        $query = User::where('venue_id', $venue->id);

        if ($venue->corporation_id !== null) {
            $query->orWhere('corporation_id', $venue->corporation_id);
        }

        $users = $query->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'active', 'venue_id', 'corporation_id']);

        return Inertia::render('Settings/Users', [
            'users' => $users,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request);

        return back()->with('success', 'User created.');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->execute($user, $request);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user, DeleteUserAction $action): RedirectResponse
    {
        $action->execute($user);

        return back()->with('success', 'User deleted.');
    }
}
