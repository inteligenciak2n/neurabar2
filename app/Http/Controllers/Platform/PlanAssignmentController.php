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
            'plan_start_date' => ['required', 'date'],
            'plan_end_date' => ['nullable', 'date', 'after:plan_start_date'],
        ]);

        $plan = PlanCatalog::findOrFail($validated['plan_catalog_id']);

        $action->execute($corporation, $plan, $validated);

        return back()->with('success', 'Plan assigned successfully.');
    }
}
