<?php

namespace App\Http\Responses;

use App\Enums\ProfileEnum;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $home = $this->resolveHome($request);

        return redirect()->intended($home);
    }

    private function resolveHome(Request $request): string
    {
        $user = $request->user();
        $profile = $user?->profile;

        if ($profile instanceof ProfileEnum && in_array($profile->value, ProfileEnum::platformProfiles(), true)) {
            $platformPath = config('platform.path', 'backoffice');

            return url($platformPath);
        }

        if ($user && ! $user->onboarding_completed_at) {
            return route('onboarding.subscription.create');
        }

        return config('fortify.home', '/dashboard') ?? '/dashboard';
    }
}
