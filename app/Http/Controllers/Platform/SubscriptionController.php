<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\UpdateCorporationSubscriptionAction;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateSubscriptionRequest;
use App\Models\Tenant\Corporation;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    /** @var list<string> */
    private const AUDITED_ATTRIBUTES = [
        'plan_catalog_id', 'status', 'billing_mode', 'billing_day', 'grace_period_days', 'trial_ends_at', 'ended_at',
    ];

    public function update(
        UpdateSubscriptionRequest $request,
        Corporation $corporation,
        UpdateCorporationSubscriptionAction $action,
    ): RedirectResponse {
        $validated = $request->validated();

        $validated['status'] = SubscriptionStatus::from($validated['status'])->value;

        $subscription = $corporation->subscription;

        if (! $subscription) {
            return back()->with('error', __('No active subscription found.'));
        }

        $before = AuditLogger::snapshot($subscription, self::AUDITED_ATTRIBUTES);

        $action->execute($subscription, $validated);

        AuditLogger::record(
            'subscription.updated',
            $subscription->refresh(),
            $before,
            AuditLogger::snapshot($subscription, self::AUDITED_ATTRIBUTES),
        );

        return back()->with('success', __('Subscription updated successfully.'));
    }
}
