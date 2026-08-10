<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlanCatalogVersionRequest;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlanCatalogVersionController extends Controller
{
    /** @var list<string> */
    private const AUDITED_ATTRIBUTES = [
        'plan_catalog_id', 'version', 'status', 'effective_from',
        'minimum_monthly_price', 'infrastructure_type', 'currency',
    ];

    public function show(PlanCatalog $plan): Response
    {
        return Inertia::render('Platform/Plans/UsagePricing', [
            'plan' => $plan,
            'versions' => $plan->versions()
                ->with(['usageTiers' => fn ($query) => $query->orderBy('module_code')->orderBy('min_quantity')])
                ->latest('version')
                ->get(),
            'modules' => ModuleCatalog::query()
                ->whereIn('billing_type', ['metered', 'hybrid'])
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(['code', 'name', 'unit_of_measure']),
        ]);
    }

    public function store(StorePlanCatalogVersionRequest $request, PlanCatalog $plan): RedirectResponse
    {
        $validated = $request->validated();

        $version = DB::connection('saas')->transaction(function () use ($plan, $validated): PlanCatalogVersion {
            PlanCatalog::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            $version = $plan->versions()->create([
                'version' => ((int) $plan->versions()->max('version')) + 1,
                'status' => 'draft',
                'effective_from' => $validated['effective_from'],
                'minimum_monthly_price' => Money::fromFloat($validated['minimum_monthly_price']),
                'infrastructure_type' => $validated['infrastructure_type'],
                'currency' => strtoupper($validated['currency']),
            ]);

            foreach ($validated['tiers'] as $tier) {
                $version->usageTiers()->create([
                    'module_code' => $tier['module_code'],
                    'min_quantity' => $tier['min_quantity'],
                    'max_quantity' => $tier['max_quantity'],
                    'included_quantity' => $tier['included_quantity'],
                    'price_per_unit' => Money::fromFloat($tier['price_per_unit'], Money::MICRO_SCALE),
                    'flat_price' => $this->optionalMoney($tier['flat_price']),
                    'overage_price_per_unit' => Money::fromFloat($tier['overage_price_per_unit'], Money::MICRO_SCALE),
                    'overage_flat_fee' => $this->optionalMoney($tier['overage_flat_fee']),
                    'currency' => strtoupper($validated['currency']),
                ]);
            }

            return $version;
        });

        AuditLogger::record(
            'plan.version.created',
            $version,
            null,
            AuditLogger::snapshot($version, self::AUDITED_ATTRIBUTES),
        );

        return back()->with('success', 'Plan pricing version created.');
    }

    public function publish(PlanCatalog $plan, PlanCatalogVersion $version): RedirectResponse
    {
        abort_unless($version->plan_catalog_id === $plan->id, 404);

        if ($version->status !== 'draft' || ! $version->usageTiers()->exists()) {
            throw ValidationException::withMessages(['version' => 'Only a complete draft version can be published.']);
        }

        $latestPublishedDate = $plan->versions()
            ->where('status', 'published')
            ->max('effective_from');

        if ($latestPublishedDate !== null && $version->effective_from->lte($latestPublishedDate)) {
            throw ValidationException::withMessages([
                'version' => 'The effective date must be after the latest published version.',
            ]);
        }

        DB::connection('saas')->transaction(function () use ($plan, $version): void {
            PlanCatalog::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            $plan->versions()
                ->where('status', 'published')
                ->where(function ($query) use ($version): void {
                    $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $version->effective_from);
                })
                ->update(['effective_until' => $version->effective_from->copy()->subDay()]);

            $version->update(['status' => 'published']);
        });

        AuditLogger::record('plan.version.published', $version, ['status' => 'draft'], ['status' => 'published']);

        return back()->with('success', 'Plan pricing version published.');
    }

    public function destroy(PlanCatalog $plan, PlanCatalogVersion $version): RedirectResponse
    {
        abort_unless($version->plan_catalog_id === $plan->id, 404);

        if ($version->status !== 'draft') {
            throw ValidationException::withMessages(['version' => 'Published versions cannot be deleted.']);
        }

        $version->delete();

        return back()->with('success', 'Draft version deleted.');
    }

    private function optionalMoney(int|float|string|null $amount): ?int
    {
        return $amount === null || $amount === '' ? null : Money::fromFloat($amount);
    }
}
