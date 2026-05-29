<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\GuestTokenService;
use Inertia\Inertia;
use Inertia\Response;

class PublicMenuController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $token): Response
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation] = $this->tokenService->decode($token);

        abort_unless($venue->active, 404);

        $menu = $venue->menus()
            ->withoutGlobalScopes()
            ->where('active', true)
            ->with([
                'categories' => function ($q) {
                    $q->orderBy('sort_order')
                        ->with([
                            'products' => fn ($q) => $q
                                ->where('active', true)
                                ->orderBy('name')
                                ->with([
                                    'variations' => fn ($q) => $q->where('active', true),
                                    'modifierGroups' => fn ($q) => $q->with([
                                        'options' => fn ($q) => $q->where('active', true),
                                    ]),
                                ]),
                        ]);
                },
            ])
            ->first();

        $categories = $menu ? $menu->categories : collect();

        return Inertia::render('Guest/Menu', [
            'token' => $token,
            'venue' => $venue->only('id', 'name', 'logo_url', 'require_geolocation'),
            'serviceLocation' => $serviceLocation?->only('id', 'name', 'type'),
            'categories' => $categories,
        ]);
    }
}
