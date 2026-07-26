<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Subscription\UpdateBillingAddressAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBillingAddressRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionBillingAddressController extends Controller
{
    public function __construct(private readonly UpdateBillingAddressAction $action) {}

    public function edit(Request $request): Response
    {
        Gate::authorize('manage-subscription');

        $user = $request->user();
        $venue = $user?->currentVenue;

        if (! $venue?->corporation) {
            abort(403, 'No corporation context found.');
        }

        $corporation = $venue->corporation;

        return Inertia::render('Settings/Subscription/BillingAddress', [
            'corporation' => [
                'id' => $corporation->id,
                'name' => $corporation->name,
                'tax_id' => $corporation->tax_id,
                'billing_address' => $corporation->billing_address_json,
                'billing_tax_regime' => $corporation->billing_tax_regime,
                'billing_state_registration' => $corporation->billing_state_registration,
            ],
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'tax_id' => $venue->tax_id,
                'billing_address' => $venue->billing_address_json,
                'billing_email' => $venue->billing_email,
                'billing_phone' => $venue->billing_phone,
            ],
        ]);
    }

    public function update(
        UpdateBillingAddressRequest $request,
        string $type,
    ): RedirectResponse {
        Gate::authorize('manage-subscription');

        $user = $request->user();
        $venue = $user?->currentVenue;

        if (! $venue?->corporation) {
            abort(403, 'No corporation context found.');
        }

        $billable = $type === 'venue' ? $venue : $venue->corporation;

        $this->action->execute($billable, $request->validated());

        return back()->with('success', __('Billing address updated successfully.'));
    }
}
