<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\UpdateCorporationSubscriptionAction;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateSubscriptionRequest;
use App\Models\Tenant\Corporation;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
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

        $action->execute($subscription, $validated);

        return back()->with('success', __('Subscription updated successfully.'));
    }
}
