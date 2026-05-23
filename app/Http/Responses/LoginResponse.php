<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $redirectUrl = $request->wantsJson()
            ? url(config('fortify.home', '/dashboard'))
            : redirect()->intended(config('fortify.home', '/dashboard'))->getTargetUrl();

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended(config('fortify.home', '/dashboard'));
    }
}
