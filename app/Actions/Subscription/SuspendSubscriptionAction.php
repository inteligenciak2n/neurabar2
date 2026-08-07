<?php

namespace App\Actions\Subscription;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\BillingStatusService;
use Illuminate\Support\Facades\Log;

/**
 * Immediately revokes access for an invoice whose money was taken back.
 *
 * Unlike the grace-period suspension in `SuspendOverdueSubscriptionsJob`, a
 * chargeback means the funds are already gone, so there is nothing to wait for.
 */
class SuspendSubscriptionAction
{
    /** @var list<SubscriptionStatus> */
    private const SUSPENDABLE = [
        SubscriptionStatus::Active,
        SubscriptionStatus::Trial,
        SubscriptionStatus::PastDue,
    ];

    public function execute(VenueInvoice|CorporationInvoice $invoice, string $reason): void
    {
        if ($invoice instanceof CorporationInvoice) {
            $this->suspendCorporation($invoice->corporation, $reason);

            return;
        }

        $venue = $invoice->venue;

        if (! $venue) {
            return;
        }

        $corporation = $venue->corporation;

        if ($corporation && $this->isBillingUnified($corporation)) {
            $this->suspendCorporation($corporation, $reason);

            return;
        }

        $this->suspendVenue($venue, $reason);
    }

    private function isBillingUnified(Corporation $corporation): bool
    {
        return CorporationSubscription::query()
            ->where('corporation_id', $corporation->id)
            ->latest('started_at')
            ->value('billing_mode') === BillingMode::Unified->value;
    }

    private function suspendCorporation(?Corporation $corporation, string $reason): void
    {
        if (! $corporation) {
            return;
        }

        $subscription = CorporationSubscription::query()
            ->where('corporation_id', $corporation->id)
            ->whereIn('status', self::SUSPENDABLE)
            ->latest('started_at')
            ->first();

        if (! $subscription instanceof CorporationSubscription) {
            return;
        }

        $subscription->statusChangeReason = $reason;
        $subscription->update([
            'status' => SubscriptionStatus::Suspended,
            'ended_at' => now(),
        ]);

        $corporation->loadMissing('venues');

        foreach ($corporation->venues as $venue) {
            BillingStatusService::flushBlockedCache($venue);
        }

        Log::warning('billing.subscription.suspended', [
            'scope' => 'corporation',
            'corporation_id' => $corporation->id,
            'reason' => $reason,
        ]);
    }

    private function suspendVenue(Venue $venue, string $reason): void
    {
        $subscription = VenueSubscription::query()
            ->where('venue_id', $venue->id)
            ->whereIn('status', self::SUSPENDABLE)
            ->latest('started_at')
            ->first();

        if (! $subscription instanceof VenueSubscription) {
            return;
        }

        $subscription->statusChangeReason = $reason;
        $subscription->update([
            'status' => SubscriptionStatus::Suspended,
            'ended_at' => now(),
        ]);

        BillingStatusService::flushBlockedCache($venue);

        Log::warning('billing.subscription.suspended', [
            'scope' => 'venue',
            'venue_id' => $venue->id,
            'reason' => $reason,
        ]);
    }
}
