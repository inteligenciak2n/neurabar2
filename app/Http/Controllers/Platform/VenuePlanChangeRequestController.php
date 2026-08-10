<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Subscription\ApproveVenuePlanChangeRequestAction;
use App\Enums\VenuePlanChangeStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant\VenuePlanChangeRequest;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VenuePlanChangeRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,canceled'],
        ]);
        $status = $validated['status'] ?? VenuePlanChangeStatus::Pending->value;

        return Inertia::render('Platform/PlanChangeRequests/Index', [
            'requests' => VenuePlanChangeRequest::query()
                ->where('status', $status)
                ->with([
                    'venue:id,name,corporation_id',
                    'venue.corporation:id,name',
                    'requestedPlanCatalog:id,name',
                    'requestedPlanCatalogVersion:id,version,infrastructure_type,minimum_monthly_price',
                    'requester:id,name,email',
                    'reviewer:id,name',
                ])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => ['status' => $status],
        ]);
    }

    public function approve(
        Request $request,
        VenuePlanChangeRequest $changeRequest,
        ApproveVenuePlanChangeRequestAction $action,
    ): RedirectResponse {
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

        $action->execute($changeRequest, $request->user(), $validated['review_notes'] ?? null);

        return back()->with('success', __('Plan change approved.'));
    }

    public function reject(Request $request, VenuePlanChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['required', 'string', 'max:1000']]);

        DB::connection('saas')->transaction(function () use ($changeRequest, $request, $validated): void {
            $lockedRequest = VenuePlanChangeRequest::query()->whereKey($changeRequest->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== VenuePlanChangeStatus::Pending) {
                throw ValidationException::withMessages(['request' => __('Only pending plan changes can be rejected.')]);
            }

            $lockedRequest->update([
                'pending_venue_id' => null,
                'reviewed_by' => $request->user()->id,
                'status' => VenuePlanChangeStatus::Rejected,
                'review_notes' => $validated['review_notes'],
                'reviewed_at' => now(),
            ]);
        });

        AuditLogger::record('venue.plan-change.rejected', $changeRequest, ['status' => 'pending'], ['status' => 'rejected']);

        return back()->with('success', __('Plan change rejected.'));
    }
}
