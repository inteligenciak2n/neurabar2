<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PlanCatalog;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanCatalogController extends Controller
{
    /** @var list<string> */
    private const AUDITED_ATTRIBUTES = [
        'code', 'name', 'monthly_price', 'dedicated_surcharge', 'sort_order', 'active',
    ];

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
            'dedicated_surcharge' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $validated['monthly_price'] = Money::fromFloat($validated['monthly_price']);
        $validated['dedicated_surcharge'] = Money::fromFloat($validated['dedicated_surcharge'] ?? 0);

        $plan = PlanCatalog::create($validated);

        AuditLogger::record('plan.created', $plan, null, AuditLogger::snapshot($plan, self::AUDITED_ATTRIBUTES));

        return back()->with('success', 'Plan created successfully.');
    }

    public function update(Request $request, PlanCatalog $plan): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:plan_catalogs,code,'.$plan->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'dedicated_surcharge' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $validated['monthly_price'] = Money::fromFloat($validated['monthly_price']);
        $validated['dedicated_surcharge'] = Money::fromFloat($validated['dedicated_surcharge'] ?? 0);

        // Alterar preço de plano muda a fatura de todos os clientes: precisa
        // deixar rastro de quem mudou o quê.
        $before = AuditLogger::snapshot($plan, self::AUDITED_ATTRIBUTES);

        $plan->update($validated);

        AuditLogger::record('plan.updated', $plan, $before, AuditLogger::snapshot($plan, self::AUDITED_ATTRIBUTES));

        return back()->with('success', 'Plan updated successfully.');
    }

    public function destroy(PlanCatalog $plan): RedirectResponse
    {
        $before = AuditLogger::snapshot($plan, self::AUDITED_ATTRIBUTES);

        $plan->delete();

        AuditLogger::record('plan.deleted', $plan, $before, null);

        return back()->with('success', 'Plan deleted successfully.');
    }
}
