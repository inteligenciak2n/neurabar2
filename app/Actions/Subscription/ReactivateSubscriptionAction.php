<?php

namespace App\Actions\Subscription;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
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
 * Restores access once a payment is confirmed.
 *
 * `past_due` and `suspended` used to be absorbing states: confirming a payment
 * only flipped the invoice row, so a customer who paid stayed locked out
 * forever unless a super-admin edited the subscription by hand.
 */
class ReactivateSubscriptionAction
{
    /** @var list<SubscriptionStatus> */
    private const REACTIVATABLE = [SubscriptionStatus::PastDue, SubscriptionStatus::Suspended];

    public function execute(VenueInvoice|CorporationInvoice $invoice): void
    {
        if ($invoice instanceof CorporationInvoice) {
            $this->reactivateCorporation($invoice->corporation);

            return;
        }

        $venue = $invoice->venue;

        if (! $venue) {
            return;
        }

        $corporation = $venue->corporation;

        if ($corporation && $this->isBillingUnified($corporation)) {
            $this->reactivateCorporation($corporation);

            return;
        }

        $this->reactivateVenue($venue);
    }

    private function isBillingUnified(Corporation $corporation): bool
    {
        return CorporationSubscription::query()
            ->where('corporation_id', $corporation->id)
            ->latest('started_at')
            ->value('billing_mode') === BillingMode::Unified->value;
    }

    private function reactivateCorporation(?Corporation $corporation): void
    {
        if (! $corporation) {
            return;
        }

        // A relação subscription() filtra por active/trial/past_due, ou seja,
        // esconde exatamente a assinatura suspensa que precisamos reativar.
        $subscription = CorporationSubscription::query()
            ->where('corporation_id', $corporation->id)
            ->whereIn('status', self::REACTIVATABLE)
            ->latest('started_at')
            ->first();

        if (! $subscription instanceof CorporationSubscription) {
            return;
        }

        if ($this->hasCorporationDebt($corporation)) {
            return;
        }

        $subscription->statusChangeReason = 'payment_confirmed';
        $subscription->update(['status' => SubscriptionStatus::Active]);

        $corporation->loadMissing('venues');

        foreach ($corporation->venues as $venue) {
            BillingStatusService::flushBlockedCache($venue);
        }

        Log::info('Subscription reactivated after payment', [
            'scope' => 'corporation',
            'corporation_id' => $corporation->id,
        ]);
    }

    private function reactivateVenue(Venue $venue): void
    {
        $subscription = VenueSubscription::query()
            ->where('venue_id', $venue->id)
            ->whereIn('status', self::REACTIVATABLE)
            ->latest('started_at')
            ->first();

        if (! $subscription instanceof VenueSubscription) {
            return;
        }

        if ($this->hasVenueDebt($venue)) {
            return;
        }

        $subscription->statusChangeReason = 'payment_confirmed';
        $subscription->update(['status' => SubscriptionStatus::Active]);

        BillingStatusService::flushBlockedCache($venue);

        Log::info('Subscription reactivated after payment', [
            'scope' => 'venue',
            'venue_id' => $venue->id,
        ]);
    }

    /**
     * Paying one of several overdue invoices must not unlock the account, so
     * reactivation only happens once nothing is left outstanding.
     */
    private function hasCorporationDebt(Corporation $corporation): bool
    {
        $hasCorporationInvoice = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->where('status', InvoiceStatus::Overdue)
            ->exists();

        if ($hasCorporationInvoice) {
            return true;
        }

        return VenueInvoice::query()
            ->whereIn('venue_id', $corporation->venues()->select('venues.id'))
            ->where('status', InvoiceStatus::Overdue)
            ->exists();
    }

    private function hasVenueDebt(Venue $venue): bool
    {
        return VenueInvoice::query()
            ->where('venue_id', $venue->id)
            ->where('status', InvoiceStatus::Overdue)
            ->exists();
    }
}
