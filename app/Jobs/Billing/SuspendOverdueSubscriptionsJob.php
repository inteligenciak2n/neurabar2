<?php

namespace App\Jobs\Billing;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueSubscription;
use App\Notifications\Billing\SubscriptionSuspended;
use App\Services\Billing\BillingStatusService;
use App\Services\Billing\GracePeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SuspendOverdueSubscriptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function handle(): void
    {
        $this->suspendExpiredTrials();
        $this->suspendOverdueUnifiedCorporations();
        $this->suspendOverdueVenues();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('billing.suspend_overdue_subscriptions.failed', [
            'message' => $exception->getMessage(),
        ]);
    }

    private function suspendExpiredTrials(): void
    {
        $query = CorporationSubscription::query()
            ->where('status', SubscriptionStatus::PastDue->value);

        GracePeriod::elapsed($query, 'trial_ends_at', 'grace_period_days');

        // Carregar todas as assinaturas de uma vez estourava a memória do worker
        // conforme a base cresce.
        $query->chunkById(100, function ($subscriptions): void {
            foreach ($subscriptions as $subscription) {
                $this->suspendCorporationSubscription($subscription, 'trial_grace_period_elapsed');
            }
        });
    }

    private function suspendOverdueUnifiedCorporations(): void
    {
        $threshold = now();

        $corporationIds = CorporationInvoice::query()
            ->select('corporation_id')
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('is_finalized', false)
            ->whereExists(function ($query) use ($threshold): void {
                $query->select(DB::raw(1))
                    ->from('corporation_subscriptions')
                    ->whereColumn('corporation_subscriptions.id', 'corporation_invoices.corporation_subscription_id')
                    ->where('corporation_subscriptions.billing_mode', BillingMode::Unified->value);

                GracePeriod::elapsed(
                    $query,
                    'corporation_invoices.due_date',
                    'corporation_subscriptions.grace_period_days',
                    $threshold,
                );
            })
            ->groupBy('corporation_id')
            ->cursor();

        foreach ($corporationIds as $row) {
            $subscription = CorporationSubscription::query()
                ->where('corporation_id', $row->corporation_id)
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->first();

            if ($subscription) {
                $this->suspendCorporationSubscription($subscription, 'overdue_invoice_grace_period_elapsed');
            }
        }
    }

    private function suspendOverdueVenues(): void
    {
        $threshold = now();

        $venueIds = VenueInvoice::query()
            ->select('venue_id')
            ->where('status', InvoiceStatus::Overdue->value)
            ->where('is_finalized', false)
            ->whereExists(function ($query) use ($threshold): void {
                $query->select(DB::raw(1))
                    ->from('corporation_subscriptions')
                    ->join('venue_subscriptions', 'venue_subscriptions.corporation_subscription_id', '=', 'corporation_subscriptions.id')
                    ->whereColumn('venue_subscriptions.venue_id', 'venue_invoices.venue_id')
                    ->where('corporation_subscriptions.billing_mode', BillingMode::PerVenue->value);

                GracePeriod::elapsed(
                    $query,
                    'venue_invoices.due_date',
                    'corporation_subscriptions.grace_period_days',
                    $threshold,
                );
            })
            ->groupBy('venue_id')
            ->cursor();

        foreach ($venueIds as $row) {
            $subscription = VenueSubscription::query()
                ->where('venue_id', $row->venue_id)
                ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
                ->with('venue')
                ->first();

            if (! $subscription) {
                continue;
            }

            $subscription->statusChangeReason = 'overdue_invoice_grace_period_elapsed';
            $subscription->update([
                'status' => SubscriptionStatus::Suspended->value,
                'ended_at' => now(),
            ]);

            $venue = $subscription->venue;

            if ($venue) {
                BillingStatusService::flushBlockedCache($venue);
            }
        }
    }

    private function suspendCorporationSubscription(CorporationSubscription $subscription, string $reason): void
    {
        $subscription->statusChangeReason = $reason;
        $subscription->update([
            'status' => SubscriptionStatus::Suspended->value,
            'ended_at' => now(),
        ]);

        // Atualizar em massa não dispara eventos de model e deixaria as
        // assinaturas das venues sem histórico de suspensão.
        $venueSubscriptions = VenueSubscription::query()
            ->where('corporation_subscription_id', $subscription->id)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::PastDue->value])
            ->with('venue')
            ->get();

        foreach ($venueSubscriptions as $venueSubscription) {
            $venueSubscription->statusChangeReason = 'corporation_subscription_suspended';
            $venueSubscription->update([
                'status' => SubscriptionStatus::Suspended->value,
                'ended_at' => now(),
            ]);
        }

        $corporation = $subscription->corporation;

        if ($corporation) {
            foreach ($corporation->venues as $venue) {
                BillingStatusService::flushBlockedCache($venue);
            }

            $owner = $corporation->owner;

            if ($owner) {
                Notification::send($owner, new SubscriptionSuspended($corporation));
            }
        }
    }
}
