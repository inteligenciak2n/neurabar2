<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\AssignPlanToCorporationAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanAssignmentController extends Controller
{
    public function update(Request $request, Corporation $corporation, AssignPlanToCorporationAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'plan_catalog_id' => ['required', 'uuid', 'exists:plan_catalogs,id'],
            'subscription_value' => ['required', 'numeric', 'min:0'],
            'billing_mode' => ['required', 'in:per_venue,unified'],
            'billing_day' => ['required', 'integer', 'min:1', 'max:28'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:30'],
            'started_at' => ['required', 'date'],
            'trial_ends_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ]);

        $plan = PlanCatalog::findOrFail($validated['plan_catalog_id']);

        $action->execute($corporation, $plan, $validated);

        return back()->with('success', 'Plan assigned successfully.');
    }
}
