<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Subscription\SubscribeModuleAction;
use App\Actions\Subscription\UnsubscribeModuleAction;
use App\Enums\ModuleCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSubscriptionModuleRequest;
use App\Models\Tenant\CorporationModule;
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

        $user = $request->user();
        $venue = $user?->currentVenue;

        if (! $venue?->corporation) {
            abort(403, 'No corporation context found.');
        }

        $corporation = $venue->corporation;
        $subscription = $corporation->subscription;

        $corporationModules = CorporationModule::query()
            ->where('corporation_id', $corporation->id)
            ->whereIn('status', ['active', 'trial'])
            ->with('catalog:id,code,name,description,base_monthly_price')
            ->get();

        $venueModules = VenueModule::query()
            ->whereIn('venue_id', $corporation->venues->pluck('id'))
            ->whereIn('status', ['active', 'trial'])
            ->get()
            ->groupBy('venue_id');

        $venues = $corporation->venues->map(fn (Venue $v) => [
            'id' => $v->id,
            'name' => $v->name,
            'modules' => $venueModules->get($v->id, collect())->map(fn (VenueModule $m) => [
                'code' => $m->module_code,
                'quantity' => $m->quantity,
            ])->values(),
        ])->values();

        return Inertia::render('Settings/Subscription/Index', [
            'subscription' => [
                'status' => $subscription?->status?->value,
                'billing_mode' => $subscription?->billing_mode?->value,
                'billing_day' => $subscription?->billing_day,
                'trial_ends_at' => $subscription?->trial_ends_at,
                'next_due_date' => $subscription ? now()->setDay($subscription->billing_day)->addMonthNoOverflow()->toDateString() : null,
            ],
            'corporation' => [
                'id' => $corporation->id,
                'name' => $corporation->name,
                'tax_id' => $corporation->tax_id,
                'billing_address' => $corporation->billing_address_json,
                'billing_tax_regime' => $corporation->billing_tax_regime,
                'billing_state_registration' => $corporation->billing_state_registration,
            ],
            'availableModules' => $corporationModules->map(fn (CorporationModule $m) => [
                'code' => $m->module_code,
                'name' => $m->catalog?->name ?? ModuleCode::tryFrom($m->module_code)?->label(),
                'description' => $m->catalog?->description,
                'monthly_price' => (float) ($m->custom_monthly_price ?? $m->catalog?->base_monthly_price ?? 0),
            ])->values(),
            'venues' => $venues,
            'blocked' => BillingStatusService::isBlocked($venue),
            'inGracePeriod' => BillingStatusService::isInGracePeriod($venue),
        ]);
    }

    public function store(
        StoreSubscriptionModuleRequest $request,
        Venue $venue,
        SubscribeModuleAction $action,
    ): RedirectResponse {
        Gate::authorize('manage-subscription');

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

        $action->execute($venue, $moduleCode);

        return back()->with('success', __('Module deactivated successfully.'));
    }
}
