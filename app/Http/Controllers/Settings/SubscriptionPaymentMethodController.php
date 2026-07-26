<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Subscription\SavePaymentMethodAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePaymentMethodRequest;
use App\Models\Tenant\UserPaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPaymentMethodController extends Controller
{
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

    public function store(
        StorePaymentMethodRequest $request,
        SavePaymentMethodAction $action,
    ): RedirectResponse {
        Gate::authorize('manage-subscription');

        $action->execute($request->user(), $request->validated());

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
