<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreVenueUsageTierOverrideRequest;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanAssignment;
use App\Services\Audit\AuditLogger;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VenueUsageTierOverrideController extends Controller
{
    public function show(Corporation $corporation, Venue $venue): Response
    {
        $this->ensureVenueBelongsToCorporation($corporation, $venue);

        return Inertia::render('Platform/Corporations/Venues/UsagePricing', [
            'corporation' => $corporation->only(['id', 'name']),
            'venue' => $venue->only(['id', 'name']),
            'assignments' => $venue->planAssignments()
                ->with([
                    'planCatalog:id,name',
                    'planCatalogVersion:id,version,currency',
                    'usageTierOverrides' => fn ($query) => $query->orderBy('module_code')->orderBy('min_quantity'),
                ])
                ->latest('starts_on')
                ->get(),
            'modules' => ModuleCatalog::query()
                ->whereIn('billing_type', ['metered', 'hybrid'])
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(['code', 'name', 'unit_of_measure']),
        ]);
    }

    public function store(
        StoreVenueUsageTierOverrideRequest $request,
        Corporation $corporation,
        Venue $venue,
    ): RedirectResponse {
        $this->ensureVenueBelongsToCorporation($corporation, $venue);
        $validated = $request->validated();
        $assignment = $venue->planAssignments()->findOrFail($validated['venue_plan_assignment_id']);

        DB::connection('saas')->transaction(function () use ($assignment, $validated): void {
            VenuePlanAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            $assignment->usageTierOverrides()->where('module_code', $validated['module_code'])->delete();

            foreach ($validated['tiers'] as $tier) {
                $assignment->usageTierOverrides()->create([
                    'module_code' => $validated['module_code'],
                    'min_quantity' => $tier['min_quantity'],
                    'max_quantity' => $tier['max_quantity'],
                    'included_quantity' => $tier['included_quantity'],
                    'price_per_unit' => Money::fromFloat($tier['price_per_unit'], Money::MICRO_SCALE),
                    'flat_price' => $this->optionalMoney($tier['flat_price']),
                    'overage_price_per_unit' => Money::fromFloat($tier['overage_price_per_unit'], Money::MICRO_SCALE),
                    'overage_flat_fee' => $this->optionalMoney($tier['overage_flat_fee']),
                    'currency' => $assignment->planCatalogVersion->currency,
                ]);
            }
        });

        AuditLogger::record('venue.usage-tier-override.saved', $assignment, null, [
            'module_code' => $validated['module_code'],
            'tier_count' => count($validated['tiers']),
        ]);

        return back()->with('success', __('Venue usage override saved.'));
    }

    public function destroy(
        Corporation $corporation,
        Venue $venue,
        VenuePlanAssignment $assignment,
        string $moduleCode,
    ): RedirectResponse {
        $this->ensureVenueBelongsToCorporation($corporation, $venue);
        abort_unless($assignment->venue_id === $venue->id, 404);

        $deleted = $assignment->usageTierOverrides()->where('module_code', $moduleCode)->delete();

        if ($deleted > 0) {
            AuditLogger::record('venue.usage-tier-override.removed', $assignment, ['module_code' => $moduleCode], null);
        }

        return back()->with('success', __('Venue usage override removed.'));
    }

    private function ensureVenueBelongsToCorporation(Corporation $corporation, Venue $venue): void
    {
        abort_unless($venue->corporation_id === $corporation->id, 404);
    }

    private function optionalMoney(int|float|string|null $amount): ?int
    {
        return $amount === null || $amount === '' ? null : Money::fromFloat($amount);
    }
}
