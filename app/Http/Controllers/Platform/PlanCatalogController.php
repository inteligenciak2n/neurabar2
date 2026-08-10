<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ProfileEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\PlanCatalogRequest;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlanCatalogController extends Controller
{
    /** @var list<string> */
    private const AUDITED_ATTRIBUTES = [
        'code', 'name', 'description', 'monthly_price', 'dedicated_surcharge',
        'plan_type', 'included_modules', 'sort_order', 'active',
    ];

    public function index(): Response
    {
        $plans = PlanCatalog::orderBy('sort_order')->get();

        return Inertia::render('Platform/Plans/Index', [
            'plans' => $plans,
            'modules' => ModuleCatalog::query()
                ->orderBy('sort_order')
                ->get(['code', 'name', 'active']),
            'canManage' => in_array(Auth::user()?->profile, [
                ProfileEnum::SuperAdmin,
                ProfileEnum::Finance,
            ], true),
        ]);
    }

    public function store(PlanCatalogRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['monthly_price'] = Money::fromFloat($validated['monthly_price']);
        $validated['dedicated_surcharge'] = Money::fromFloat($validated['dedicated_surcharge'] ?? 0);

        $plan = PlanCatalog::create($validated);

        AuditLogger::record('plan.created', $plan, null, AuditLogger::snapshot($plan, self::AUDITED_ATTRIBUTES));

        return back()->with('success', 'Plan created successfully.');
    }

    public function update(PlanCatalogRequest $request, PlanCatalog $plan): RedirectResponse
    {
        $validated = $request->validated();

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
