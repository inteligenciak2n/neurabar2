<?php

namespace App\Http\Controllers\Delivery;

use App\Actions\Delivery\UpdateDeliverySettingsAction;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Delivery\UpdateDeliverySettingsRequest;
use App\Http\Resources\DeliveryFeeZoneResource;
use App\Models\Settings\VenueSettings;
use App\Services\GuestTokenService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly GuestTokenService $tokenService) {}

    public function index(): Response
    {
        $venue = app('tenant');

        $settings = VenueSettings::where('venue_id', $venue->id)->first();

        return Inertia::render('Delivery/Index', [
            'deliveryLink' => url('/delivery/'.$this->tokenService->encodeVenueOnly($venue)),
            'feeZones' => DeliveryFeeZoneResource::collection($venue->deliveryFeeZones()->orderBy('sort_order')->get()),
            'availablePaymentMethods' => PaymentMethod::values(),
            'settings' => [
                'accepted_delivery_payment_methods' => $settings?->acceptedDeliveryPaymentMethods() ?? [],
                'delivery_enabled' => $settings?->delivery_enabled ?? true,
                'pickup_enabled' => $settings?->pickup_enabled ?? true,
            ],
        ]);
    }

    public function updateSettings(UpdateDeliverySettingsRequest $request, UpdateDeliverySettingsAction $action): RedirectResponse
    {
        $venue = app('tenant');

        $action->execute($venue, $request->validated());

        return back()->with('success', 'Delivery settings updated.');
    }
}
