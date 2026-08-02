<?php

namespace App\Http\Controllers\Settings;

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

        $data = $request->validated();
        $this->paymentService->saveCard($request->user(), $data, $data['billing_address'] ?? []);

        return back()->with('success', __('Payment method saved successfully.'));
    }

    public function setDefault(Request $request, UserPaymentMethod $method): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        if ($method->user_id !== $request->user()->id) {
            abort(403);
        }

        $method->setAsDefault();

        return back()->with('success', __('Default payment method updated.'));
    }

    public function destroy(Request $request, UserPaymentMethod $method): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        if ($method->user_id !== $request->user()->id) {
            abort(403);
        }

        $method->delete();

        return back()->with('success', __('Payment method removed.'));
    }
}
