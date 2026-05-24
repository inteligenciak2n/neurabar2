<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUserController extends Controller
{
    public function index(): Response
    {
        $users = PlatformUser::orderBy('name')->get();

        return Inertia::render('Platform/Users/Index', [
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:platform_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:super_admin,finance,registration,read_only'],
            'active' => ['boolean'],
        ]);

        PlatformUser::create($validated);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, PlatformUser $platformUser): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:platform_users,email,'.$platformUser->id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'in:super_admin,finance,registration,read_only'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $platformUser->update($validated);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(PlatformUser $platformUser): RedirectResponse
    {
        $platformUser->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
