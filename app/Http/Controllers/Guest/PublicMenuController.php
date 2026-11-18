<?php

namespace App\Http\Controllers\Guest;

use App\Enums\ModuleCode;
use App\Enums\ServiceRequestType;
use App\Http\Controllers\Controller;
use App\Models\Orders\ServiceRequest;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use App\Services\GuestTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicMenuController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $token, Request $request): Response
    {
        ['venue' => $venue, 'serviceLocation' => $serviceLocation, 'attendanceChannel' => $attendanceChannel] = $this->tokenService->decode($token);

        abort_unless($venue->active, 404);

        $this->tokenService->ensureSession($request, $venue, $serviceLocation, $attendanceChannel);

        $activeModules = $venue->activeModules();
        $hasMenu = in_array(ModuleCode::Menu->value, $activeModules, true);
        $hasDirectWaiter = in_array(ModuleCode::DirectWaiter->value, $activeModules, true);
        $hasSelfOrder = in_array(ModuleCode::SelfOrder->value, $activeModules, true);

        $categories = $hasMenu ? $this->catalogCategories($venue) : collect();

        return Inertia::render('Guest/Menu', [
            'token' => $token,
            'venue' => $venue->only('id', 'name', 'description', 'logo_url', 'require_geolocation'),
            'serviceLocation' => $serviceLocation?->only('id', 'name'),
            'categories' => $categories,
            'hasMenu' => $hasMenu,
            'hasDirectWaiter' => $hasDirectWaiter,
            'hasSelfOrder' => $hasSelfOrder,
            'pendingWaiterCall' => $hasDirectWaiter
                ? $this->pendingWaiterCall($venue, $serviceLocation)
                : null,
            'waiterCallPhrases' => $hasDirectWaiter
                ? VenueSettings::phrasesForVenue($venue->id)
                : [],
        ]);
    }

    private function catalogCategories(Venue $venue): Collection
    {
        $menu = $venue->menus()
            ->withoutGlobalScopes()
            ->where('active', true)
            ->with([
                'categories' => function ($q) {
                    $q->orderBy('sort_order')
                        ->with([
                            'products' => fn ($q) => $q
                                ->where('active', true)
                                ->orderBy('sort_order')
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

        return $menu
            ? $menu->categories->filter(fn ($category) => $category->products->isNotEmpty())->values()
            : collect();
    }

    /**
     * @return array{id: string, message: ?string, created_at: ?string}|null
     */
    private function pendingWaiterCall(Venue $venue, ?ServiceLocation $serviceLocation): ?array
    {
        $pending = ServiceRequest::withoutGlobalScopes()
            ->open()
            ->ofType(ServiceRequestType::Message)
            ->where('venue_id', $venue->id)
            ->where('service_location_id', $serviceLocation?->id)
            ->latest()
            ->first();

        if ($pending === null) {
            return null;
        }

        return [
            'id' => $pending->id,
            'message' => $pending->message,
            'created_at' => $pending->created_at?->toIso8601String(),
        ];
    }
}
