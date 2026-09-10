<?php

namespace App\Http\Controllers\Guest\Delivery;

use App\Enums\ModuleCode;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Settings\VenueSettings;
use App\Services\GuestTokenService;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryMenuController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function show(string $token): Response
    {
        ['venue' => $venue] = $this->tokenService->decode($token);

        abort_unless($venue->active, 404);
        abort_unless(in_array(ModuleCode::Delivery->value, $venue->activeModules(), true), 404);

        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        $menu = $venue->menus()
            ->withoutGlobalScopes()
            ->where('active', true)
            ->with([
                'categories' => function ($q) {
                    $q->orderBy('sort_order')
                        ->with([
                            'products' => fn ($q) => $q
                                ->where('active', true)
                                ->where('available_for_delivery', true)
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

        $categories = $menu
            ? $menu->categories->filter(fn ($category) => $category->products->isNotEmpty())->values()
            : collect();

        return Inertia::render('Guest/Delivery/Menu', [
            'token' => $token,
            'venue' => $venue->only('id', 'name', 'logo_url'),
            'categories' => $categories,
            'deliveryEnabled' => $settings?->delivery_enabled ?? true,
            'pickupEnabled' => $settings?->pickup_enabled ?? true,
            'acceptedPaymentMethods' => $settings?->acceptedDeliveryPaymentMethods() ?? PaymentMethod::values(),
            'serviceFeePercent' => (float) ($settings?->service_fee_percent ?? 0),
        ]);
    }
}
