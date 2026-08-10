<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\VenueUsageRecord;
use App\Services\Billing\UsagePricingResolver;
use App\Services\Billing\UsageTierCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionUsageController extends Controller
{
    public function __invoke(
        Request $request,
        UsagePricingResolver $pricingResolver,
        UsageTierCalculator $tierCalculator,
    ): Response {
        Gate::authorize('manage-subscription');

        $corporation = $request->user()?->currentVenue?->corporation;

        if (! $corporation) {
            abort(403, 'No corporation context found.');
        }

        $venueIds = $corporation->venues()->pluck('id');
        $validated = $request->validate([
            'venue_id' => ['nullable', 'uuid', Rule::in($venueIds->all())],
            'period' => ['nullable', 'date_format:Y-m'],
        ]);
        $selectedVenueId = $validated['venue_id'] ?? $request->user()?->currentVenue?->id;
        $period = $validated['period'] ?? now()->format('Y-m');
        $venue = $corporation->venues()->whereKey($selectedVenueId)->firstOrFail();
        $nextCycle = now()->addMonthNoOverflow()->startOfMonth();
        $assignment = $venue->planAssignments()
            ->with('planCatalogVersion.planCatalog')
            ->whereDate('starts_on', '<=', Carbon::parse($period.'-01')->endOfMonth())
            ->where(function ($query) use ($period): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $period.'-01');
            })
            ->latest('starts_on')
            ->first();
        $records = VenueUsageRecord::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->get();
        $catalog = ModuleCatalog::query()
            ->whereIn('code', $records->pluck('module_code'))
            ->get()
            ->keyBy('code');

        return Inertia::render('Settings/Subscription/Usage', [
            'venues' => $corporation->venues()->orderBy('name')->get(['id', 'name']),
            'filters' => ['venue_id' => $venue->id, 'period' => $period],
            'plan' => $assignment ? [
                'id' => $assignment->plan_catalog_id,
                'name' => $assignment->planCatalogVersion->planCatalog->name,
                'version' => $assignment->planCatalogVersion->version,
                'minimum_monthly_price' => $assignment->planCatalogVersion->minimum_monthly_price,
                'infrastructure_type' => $assignment->planCatalogVersion->infrastructure_type,
            ] : null,
            'availablePlans' => PlanCatalogVersion::query()
                ->where('status', 'published')
                ->whereDate('effective_from', '<=', $nextCycle)
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $nextCycle))
                ->whereHas('planCatalog', fn ($query) => $query->where('active', true))
                ->with('planCatalog:id,name')
                ->latest('effective_from')
                ->get()
                ->unique('plan_catalog_id')
                ->map(fn (PlanCatalogVersion $version): array => [
                    'id' => $version->plan_catalog_id,
                    'name' => $version->planCatalog->name,
                    'version' => $version->version,
                    'minimum_monthly_price' => $version->minimum_monthly_price,
                    'infrastructure_type' => $version->infrastructure_type,
                ])->values(),
            'pendingPlanChange' => $venue->planChangeRequests()
                ->where('status', 'pending')
                ->with('requestedPlanCatalog:id,name')
                ->first(),
            'nextCycle' => $nextCycle->toDateString(),
            'usage' => $records->map(function (VenueUsageRecord $record) use ($catalog, $period, $pricingResolver, $tierCalculator, $venue): array {
                $pricing = $pricingResolver->resolve($venue, $record->module_code, $period);
                $calculated = $tierCalculator->calculate($pricing['tiers'], (int) $record->quantity);

                return [
                    'module_code' => $record->module_code,
                    'module_name' => $catalog->get($record->module_code)?->name ?? $record->module_code,
                    'unit_of_measure' => $catalog->get($record->module_code)?->unit_of_measure,
                    'quantity' => $record->quantity,
                    'included_quantity' => $calculated['included_quantity'],
                    'overage_quantity' => $calculated['overage_quantity'],
                    'base_calculated_price' => $calculated['base_price'],
                    'overage_calculated_price' => $calculated['overage_price'],
                    'total_calculated_price' => $calculated['total_price'],
                ];
            })->values(),
        ]);
    }
}
