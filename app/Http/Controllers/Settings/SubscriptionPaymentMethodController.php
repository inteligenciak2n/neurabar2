<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\Subscription\GatewayRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePaymentMethodRequest;
use App\Models\Tenant\UserPaymentMethod;
use App\Services\Subscription\PaymentSaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentSaasService $paymentService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('manage-subscription');

        return Inertia::render('Settings/Subscription/PaymentMethods', [
            'paymentMethods' => $request->user()->paymentMethods()
                ->orderByDesc('is_default')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        try {
            $this->paymentService->saveCard(
                $request->user(),
                $request->cardData(),
                $request->validated('billing_address', []),
            );
        } catch (GatewayRequestException $exception) {
            return back()->withErrors(['number' => $exception->userMessage()]);
        }

        return back()->with('success', __('Payment method saved successfully.'));
    }

    public function setDefault(Request $request, UserPaymentMethod $method): RedirectResponse
    {
        Gate::authorize('update', $method);

        $method->setAsDefault();

        return back()->with('success', __('Default payment method updated.'));
    }

    public function destroy(Request $request, UserPaymentMethod $method): RedirectResponse
    {
        Gate::authorize('delete', $method);

        $method->delete();

        return back()->with('success', __('Payment method removed.'));
    }
}
