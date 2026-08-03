<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\Onboarding\StartCorporationSubscriptionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreSubscriptionRequest;
use App\Models\Tenant\ModuleCatalog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class SubscriptionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        if ($user->ownedCorporation) {
            return redirect()->route('onboarding.corporation.create');
        }

        $modules = ModuleCatalog::where('active', true)
            ->orderBy('sort_order')
            ->get(['code', 'name', 'description', 'category', 'base_monthly_price']);

        return Inertia::render('Onboarding/Subscription', [
            'modules' => $modules,
            'trialDays' => (int) config('billing.trial_days'),
        ]);
    }

    public function store(StoreSubscriptionRequest $request, StartCorporationSubscriptionAction $action): RedirectResponse
    {
        $user = $request->user();

        if ($user->ownedCorporation) {
            return redirect()->route('onboarding.corporation.create');
        }

        try {
            $action->execute($user, $request->validated('module_codes', []), (int) $request->validated('venue_count'));
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['module_codes' => $e->getMessage()]);
        }

        return redirect()->route('onboarding.corporation.create');
    }
}
