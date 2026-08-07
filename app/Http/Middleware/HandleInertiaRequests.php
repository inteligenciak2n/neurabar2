<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Billing\BillingStatusService;
use App\Services\Languages\TranslationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'defs' => [
                'venue' => function () use ($request): ?array {
                    $user = $request->user();

                    if (! $user instanceof User) {
                        return null;
                    }

                    $venue = $user->activeVenue();

                    return $venue?->only(['id', 'name', 'timezone']);
                },
                'current_venue_role' => function () use ($request): ?string {
                    $user = $request->user();

                    if (! $user instanceof User) {
                        return null;
                    }

                    return $user->currentVenueRole()?->value;
                },
                'venues' => function () use ($request): array {
                    $user = $request->user();

                    if (! $user instanceof User) {
                        return [];
                    }

                    return $user->venues()
                        ->where('active', true)
                        ->get(['venues.id', 'venues.name'])
                        ->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'role' => $v->pivot->role instanceof UserRole ? $v->pivot->role->value : $v->pivot->role])
                        ->toArray();
                },
                'tenant' => function () use ($request): ?array {
                    $user = $request->user();

                    if (! $user instanceof User) {
                        return null;
                    }

                    $venue = $user->activeVenue();

                    if (! $venue) {
                        return null;
                    }

                    return [
                        'id' => $venue->id,
                        'name' => $venue->name,
                        'modules' => $venue->activeModules(),
                        'role' => $user->currentVenueRole()?->value,
                        'blocked' => BillingStatusService::isBlocked($venue),
                    ];
                },
            ],
            'venue_switched' => fn () => $request->session()->pull('venue_switched', false),
            'language' => TranslationService::getLanguagesDefinitions($request),
            'assets' => asset('storage/assets'),
        ];
    }
}
