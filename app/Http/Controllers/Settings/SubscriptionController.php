<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Subscription\ActivateGatewaySubscriptionAction;
use App\Actions\Subscription\CancelSubscriptionAction;
use App\Actions\Subscription\SubscribeModuleAction;
use App\Actions\Subscription\UnsubscribeModuleAction;
use App\Enums\BillingMode;
use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSubscriptionModuleRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\Billing\BillingStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-subscription');

        $corporation = $this->currentCorporation($request);

        if (! $corporation) {
            abort(403, 'No corporation context found.');
        }

        $subscription = $corporation->subscription;

        return Inertia::render('Settings/Subscription/Index', [
            'subscription' => $this->subscriptionPayload($subscription),
            'corporation' => $this->corporationPayload($corporation),
            'availableModules' => $this->availableModules($corporation),
            'venues' => $this->venuesWithModules($corporation),
            'blocked' => BillingStatusService::isBlocked($request->user()?->currentVenue),
            'inGracePeriod' => BillingStatusService::isInGracePeriod($request->user()?->currentVenue),
            'hasPaymentMethod' => $request->user()?->paymentMethods()->exists() ?? false,
        ]);
    }

    private function currentCorporation(Request $request): ?Corporation
    {
        return $request->user()?->currentVenue?->corporation;
    }

    private function subscriptionPayload(?CorporationSubscription $subscription): array
    {
        return [
            'status' => $subscription?->status?->value,
            'billing_mode' => $subscription?->billing_mode?->value,
            'billing_day' => $subscription?->billing_day,
            'trial_ends_at' => $subscription?->trial_ends_at,
            'next_due_date' => $subscription ? now()->setDay($subscription->billing_day)->addMonthNoOverflow()->toDateString() : null,
            'is_billed_by_gateway' => $subscription?->isBilledByGateway() ?? false,
        ];
    }

    private function corporationPayload(Corporation $corporation): array
    {
        return [
            'id' => $corporation->id,
            'name' => $corporation->name,
            'tax_id' => $corporation->tax_id,
            'billing_address' => $corporation->billing_address_json,
            'billing_tax_regime' => $corporation->billing_tax_regime,
            'billing_state_registration' => $corporation->billing_state_registration,
        ];
    }

    private function availableModules(Corporation $corporation): array
    {
        return CorporationModule::query()
            ->where('corporation_id', $corporation->id)
            ->whereIn('status', ['active', 'trial'])
            ->with('catalog:id,code,name,description,base_monthly_price')
            ->get()
            ->map(fn (CorporationModule $m) => [
                'code' => $m->module_code,
                'name' => $m->catalog?->name ?? ModuleCode::tryFrom($m->module_code)?->label(),
                'description' => $m->catalog?->description,
                'monthly_price' => (float) ($m->custom_monthly_price ?? $m->catalog?->base_monthly_price ?? 0),
            ])
            ->values()
            ->all();
    }

    private function venuesWithModules(Corporation $corporation): array
    {
        $venueIds = $corporation->venues()->pluck('id');

        $venueModules = VenueModule::query()
            ->whereIn('venue_id', $venueIds)
            ->whereIn('status', ['active', 'trial'])
            ->get()
            ->groupBy('venue_id');

        return $corporation->venues()->get()->map(fn (Venue $v) => [
            'id' => $v->id,
            'name' => $v->name,
            'is_billed_by_gateway' => $v->subscription?->isBilledByGateway() ?? false,
            'modules' => $venueModules->get($v->id, collect())->map(fn (VenueModule $m) => [
                'code' => $m->module_code,
                'quantity' => $m->quantity,
            ])->values(),
        ])->values()->all();
    }

    public function store(
        StoreSubscriptionModuleRequest $request,
        Venue $venue,
        SubscribeModuleAction $action,
    ): RedirectResponse {
        Gate::authorize('manage-subscription');

        $this->ensureVenueBelongsToCurrentCorporation($venue);

        $validated = $request->validated();

        $action->execute($venue, $validated['module_code'], $validated['quantity'] ?? 1);

        return back()->with('success', __('Module activated successfully.'));
    }

    public function destroy(
        Request $request,
        Venue $venue,
        string $moduleCode,
        UnsubscribeModuleAction $action,
    ): RedirectResponse {
        Gate::authorize('manage-subscription');

        $this->ensureVenueBelongsToCurrentCorporation($venue);

        $action->execute($venue, $moduleCode);

        return back()->with('success', __('Module deactivated successfully.'));
    }

    public function cancel(Request $request, CancelSubscriptionAction $action): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        $corporation = $this->currentCorporation($request);

        if (! $corporation) {
            abort(403, 'No corporation context found.');
        }

        $action->execute($corporation);

        return back()->with('success', __('Subscription canceled successfully.'));
    }

    public function activateGateway(Request $request, ActivateGatewaySubscriptionAction $action): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        $corporation = $this->currentCorporation($request);

        if (! $corporation) {
            abort(403, 'No corporation context found.');
        }

        $subscription = $corporation->subscription;

        if (! $subscription) {
            abort(404, 'No active subscription found.');
        }

        if ($subscription->billing_mode === BillingMode::PerVenue) {
            $validated = $request->validate([
                'venue_id' => ['required', 'uuid', 'exists:venues,id'],
            ]);

            $venue = Venue::findOrFail($validated['venue_id']);
            $this->ensureVenueBelongsToCurrentCorporation($venue);

            $target = $venue->subscription;
        } else {
            $target = $subscription;
        }

        if (! $target) {
            abort(404, 'No subscription found for the selected venue.');
        }

        $paymentMethod = $request->user()->paymentMethods()->where('is_default', true)->first()
            ?? $request->user()->paymentMethods()->first();

        if (! $paymentMethod) {
            return back()->with('error', __('Add a credit card before enabling automatic billing.'));
        }

        $action->execute($target, $paymentMethod);

        return back()->with('success', __('Automatic billing activated successfully.'));
    }

    private function ensureVenueBelongsToCurrentCorporation(Venue $venue): void
    {
        $corporation = auth()->user()?->currentVenue?->corporation;

        if (! $corporation || $venue->corporation_id !== $corporation->id) {
            abort(403, 'Venue does not belong to the current corporation.');
        }
    }
}
