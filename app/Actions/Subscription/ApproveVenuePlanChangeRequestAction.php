<?php

namespace App\Actions\Subscription;

use App\Enums\VenuePlanChangeStatus;
use App\Models\Tenant\VenuePlanAssignment;
use App\Models\Tenant\VenuePlanChangeRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveVenuePlanChangeRequestAction
{
    public function execute(VenuePlanChangeRequest $changeRequest, User $reviewer, ?string $notes = null): VenuePlanAssignment
    {
        $assignment = DB::connection('saas')->transaction(function () use ($changeRequest, $notes, $reviewer): VenuePlanAssignment {
            $lockedRequest = VenuePlanChangeRequest::query()
                ->with('requestedPlanCatalogVersion')
                ->whereKey($changeRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== VenuePlanChangeStatus::Pending) {
                throw ValidationException::withMessages(['request' => 'Only pending plan changes can be approved.']);
            }

            $version = $lockedRequest->requestedPlanCatalogVersion;
            $effectiveOn = $lockedRequest->effective_on;

            if (
                $version->plan_catalog_id !== $lockedRequest->requested_plan_catalog_id
                || $version->status !== 'published'
                || $version->effective_from->gt($effectiveOn)
                || ($version->effective_until && $version->effective_until->lt($effectiveOn))
            ) {
                throw ValidationException::withMessages(['request' => 'The requested plan version is not valid on the effective date.']);
            }

            $assignments = VenuePlanAssignment::query()
                ->where('venue_id', $lockedRequest->venue_id)
                ->lockForUpdate()
                ->get();

            if ($assignments->contains(fn (VenuePlanAssignment $assignment): bool => $assignment->starts_on->gte($effectiveOn))) {
                throw ValidationException::withMessages(['request' => 'The venue already has a plan assignment scheduled for this date.']);
            }

            $currentAssignment = $assignments
                ->filter(fn (VenuePlanAssignment $assignment): bool => $assignment->starts_on->lte($effectiveOn)
                    && ($assignment->ends_on === null || $assignment->ends_on->gte($effectiveOn)))
                ->sortByDesc('starts_on')
                ->first();

            $currentAssignment?->update(['ends_on' => $effectiveOn->copy()->subDay()]);

            $assignment = VenuePlanAssignment::create([
                'venue_id' => $lockedRequest->venue_id,
                'plan_catalog_id' => $lockedRequest->requested_plan_catalog_id,
                'plan_catalog_version_id' => $lockedRequest->requested_plan_catalog_version_id,
                'starts_on' => $effectiveOn,
                'source' => 'owner_request',
            ]);

            $lockedRequest->update([
                'pending_venue_id' => null,
                'approved_assignment_id' => $assignment->id,
                'reviewed_by' => $reviewer->id,
                'status' => VenuePlanChangeStatus::Approved,
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            return $assignment;
        });

        AuditLogger::record('venue.plan-change.approved', $changeRequest, ['status' => 'pending'], [
            'status' => 'approved',
            'assignment_id' => $assignment->id,
            'reviewer_id' => $reviewer->id,
        ]);

        return $assignment;
    }
}
