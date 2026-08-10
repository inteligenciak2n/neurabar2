<?php

namespace App\Http\Controllers\Settings;

use App\Enums\VenuePlanChangeStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PlanCatalogVersion;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenuePlanChangeRequest;
use App\Services\Audit\AuditLogger;
use App\Services\Billing\UsagePricingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VenuePlanChangeRequestController extends Controller
{
    public function store(Request $request, UsagePricingResolver $pricingResolver): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        $corporation = $request->user()?->currentVenue?->corporation;

        if (! $corporation) {
            abort(403, 'No corporation context found.');
        }

        $validated = $request->validate([
            'venue_id' => ['required', 'uuid'],
            'plan_catalog_id' => ['required', 'uuid'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $venue = $corporation->venues()->whereKey($validated['venue_id'])->firstOrFail();
        $effectiveOn = now()->addMonthNoOverflow()->startOfMonth();
        $version = PlanCatalogVersion::query()
            ->where('plan_catalog_id', $validated['plan_catalog_id'])
            ->whereHas('planCatalog', fn ($query) => $query->where('active', true))
            ->where('status', 'published')
            ->whereDate('effective_from', '<=', $effectiveOn)
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $effectiveOn))
            ->latest('effective_from')
            ->firstOrFail();
        $currentAssignment = $pricingResolver->resolveAssignment($venue, $effectiveOn->format('Y-m'));

        if ($currentAssignment?->plan_catalog_version_id === $version->id) {
            throw ValidationException::withMessages(['plan_catalog_id' => __('This plan is already scheduled for the next cycle.')]);
        }

        $changeRequest = DB::connection('saas')->transaction(function () use ($request, $validated, $venue, $version, $effectiveOn): VenuePlanChangeRequest {
            Venue::query()->whereKey($venue->id)->lockForUpdate()->firstOrFail();

            if (VenuePlanChangeRequest::query()->where('pending_venue_id', $venue->id)->exists()) {
                throw ValidationException::withMessages(['plan_catalog_id' => __('This venue already has a pending plan change request.')]);
            }

            return VenuePlanChangeRequest::create([
                'venue_id' => $venue->id,
                'pending_venue_id' => $venue->id,
                'requested_plan_catalog_id' => $version->plan_catalog_id,
                'requested_plan_catalog_version_id' => $version->id,
                'requested_by' => $request->user()->id,
                'status' => VenuePlanChangeStatus::Pending,
                'effective_on' => $effectiveOn,
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        AuditLogger::record('venue.plan-change.requested', $changeRequest, null, [
            'venue_id' => $venue->id,
            'plan_catalog_version_id' => $version->id,
            'effective_on' => $effectiveOn->toDateString(),
        ]);

        return back()->with('success', __('Plan change requested successfully.'));
    }

    public function destroy(Request $request, VenuePlanChangeRequest $changeRequest): RedirectResponse
    {
        Gate::authorize('manage-subscription');

        $corporationId = $request->user()?->currentVenue?->corporation_id;

        if (! $corporationId || $changeRequest->venue()->where('corporation_id', $corporationId)->doesntExist()) {
            abort(404);
        }

        DB::connection('saas')->transaction(function () use ($changeRequest): void {
            $lockedRequest = VenuePlanChangeRequest::query()->whereKey($changeRequest->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== VenuePlanChangeStatus::Pending) {
                throw ValidationException::withMessages(['request' => __('Only pending plan changes can be canceled.')]);
            }

            $lockedRequest->update([
                'pending_venue_id' => null,
                'status' => VenuePlanChangeStatus::Canceled,
            ]);
        });

        AuditLogger::record('venue.plan-change.canceled', $changeRequest, ['status' => 'pending'], ['status' => 'canceled']);

        return back()->with('success', __('Plan change request canceled.'));
    }
}
