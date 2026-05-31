<?php

namespace App\Http\Middleware;

use App\Enums\ProfileEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePlatformProfile
{
    public function handle(Request $request, Closure $next): Response
    {
        $profile = $request->user()?->profile;

        if (! $profile instanceof ProfileEnum || ! in_array($profile->value, ProfileEnum::platformProfiles(), true)) {
            abort(403);
        }

        return $next($request);
    }
}
