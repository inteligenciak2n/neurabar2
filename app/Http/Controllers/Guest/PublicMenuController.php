<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Menu\Menu;
use App\Models\Tenant\Venue;
use Inertia\Inertia;
use Inertia\Response;

class PublicMenuController extends Controller
{
    public function show(string $slug): Response
    {
        $venue = Venue::withoutGlobalScopes()
            ->where('call_waiter_slug', $slug)
            ->where('active', true)
            ->firstOrFail();

        $menu = Menu::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->with([
                'categories' => function ($q) {
                    $q->orderBy('sort_order')
                        ->with([
                            'products' => fn ($q) => $q->where('active', true)->orderBy('name'),
                        ]);
                },
            ])
            ->first();

        $categories = $menu ? $menu->categories : collect();

        return Inertia::render('Guest/Menu', [
            'venue' => $venue->only('id', 'name', 'logo_url'),
            'categories' => $categories,
        ]);
    }
}
