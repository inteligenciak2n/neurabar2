<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ProfileEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUserController extends Controller
{
    public function index(): Response
    {
        $users = User::whereIn('profile', ProfileEnum::platformProfiles())
            ->orderBy('name')
            ->get();

        return Inertia::render('Platform/Users/Index', [
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'profile' => ['required', 'string', 'in:'.implode(',', ProfileEnum::platformProfiles())],
            'active' => ['boolean'],
        ]);

        User::create($validated);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensurePlatformUser($user);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'profile' => ['sometimes', 'string', 'in:'.implode(',', ProfileEnum::platformProfiles())],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        Log::info('platform.user.updated', [
            'actor_id' => $request->user()?->id,
            'target_id' => $user->id,
            'changed' => array_keys(array_diff_key($validated, ['password' => null])),
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensurePlatformUser($user);

        abort_if($request->user()?->is($user) === true, 403, 'You cannot delete your own account.');

        $user->delete();

        Log::info('platform.user.deleted', [
            'actor_id' => $request->user()?->id,
            'target_id' => $user->id,
            'target_email' => $user->email,
        ]);

        return back()->with('success', 'User deleted successfully.');
    }

    /**
     * The route model binding accepts any user id, so a backoffice admin could
     * otherwise edit or delete tenant users through this screen.
     */
    private function ensurePlatformUser(User $user): void
    {
        abort_unless(in_array($user->profile?->value, ProfileEnum::platformProfiles(), true), 404);
    }
}
