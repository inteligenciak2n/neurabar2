<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PlanCatalog;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanCatalogController extends Controller
{
    public function index(): Response
    {
        $plans = PlanCatalog::orderBy('sort_order')->get();

        return Inertia::render('Platform/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:plan_catalogs,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $validated['monthly_price'] = Money::fromFloat($validated['monthly_price']);

        PlanCatalog::create($validated);

        return back()->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, PlanCatalog $plan): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:plan_catalogs,code,'.$plan->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $validated['monthly_price'] = Money::fromFloat($validated['monthly_price']);

        $plan->update($validated);

        return back()->with('success', 'Plan updated successfully.');
    }

    public function destroy(PlanCatalog $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('success', 'Plan deleted successfully.');
    }
}
