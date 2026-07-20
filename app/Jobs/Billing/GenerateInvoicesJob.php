<?php

namespace App\Jobs\Billing;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueInvoice;
use App\Notifications\Billing\InvoiceGenerated;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class GenerateInvoicesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly string $period) {}

    public function handle(SubscriptionCalculator $calculator): void
    {
        CorporationSubscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial, SubscriptionStatus::PastDue])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->cursor()
            ->each(fn (CorporationSubscription $subscription) => $this->generateForSubscription($subscription, $calculator));
    }

    private function generateForSubscription(CorporationSubscription $subscription, SubscriptionCalculator $calculator): void
    {
        $corporation = $subscription->corporation;

        if (! $corporation) {
            return;
        }

        $isUnified = $subscription->billing_mode === BillingMode::Unified;
        $corporationTotal = 0.0;
        $corporationBase = 0.0;
        $corporationModules = 0.0;
        $corporationMetered = 0.0;
        $corporationDedicated = 0.0;
        $venueInvoices = [];

        foreach ($corporation->venues as $venue) {
            $calculated = $calculator->calculateVenue($venue, $this->period);

            if ($calculated === null) {
                $existingInvoice = VenueInvoice::query()
                    ->where('venue_id', $venue->id)
                    ->where('period', $this->period)
                    ->first();

                if ($existingInvoice) {
                    $venueInvoices[] = $existingInvoice;

                    if ($isUnified) {
                        $corporationBase += (float) $existingInvoice->base_value;
                        $corporationModules += (float) $existingInvoice->modules_value;
                        $corporationMetered += (float) $existingInvoice->metered_value;
                        $corporationDedicated += (float) $existingInvoice->dedicated_surcharge;
                        $corporationTotal += (float) $existingInvoice->total_value;
                    }
                }

                continue;
            }

            $invoice = VenueInvoice::updateOrCreate(
                [
                    'venue_id' => $venue->id,
                    'period' => $this->period,
                    'is_finalized' => false,
                ],
                [
                    'venue_subscription_id' => $venue->subscription?->id,
                    'affiliate_code_id' => $venue->subscription?->affiliate_code_id,
                    'due_date' => $this->resolveDueDate($subscription),
                    'status' => InvoiceStatus::Open,
                    'base_value' => $calculated['base'],
                    'modules_value' => $calculated['modules'],
                    'metered_value' => $calculated['metered'],
                    'dedicated_surcharge' => $calculated['dedicated_surcharge'],
                    'discount_value' => 0,
                    'total_value' => $calculated['total'],
                ]
            );

            $venueInvoices[] = $invoice;

            if ($isUnified) {
                $corporationBase += $calculated['base'];
                $corporationModules += $calculated['modules'];
                $corporationMetered += $calculated['metered'];
                $corporationDedicated += $calculated['dedicated_surcharge'];
                $corporationTotal += $calculated['total'];
            }

            if ($invoice->wasRecentlyCreated && ! $isUnified) {
                $this->notifyOwner($invoice);
            }
        }

        if ($isUnified) {
            $corporationInvoice = CorporationInvoice::updateOrCreate(
                [
                    'corporation_id' => $corporation->id,
                    'period' => $this->period,
                    'is_finalized' => false,
                ],
                [
                    'corporation_subscription_id' => $subscription->id,
                    'affiliate_code_id' => $subscription->affiliate_code_id,
                    'due_date' => $this->resolveDueDate($subscription),
                    'status' => InvoiceStatus::Open,
                    'base_value' => $corporationBase,
                    'modules_value' => $corporationModules,
                    'metered_value' => $corporationMetered,
                    'dedicated_surcharge' => $corporationDedicated,
                    'discount_value' => 0,
                    'total_value' => $corporationTotal,
                ]
            );

            if ($corporationInvoice->wasRecentlyCreated || ! $corporationInvoice->wasRecentlyCreated) {
                foreach ($venueInvoices as $venueInvoice) {
                    if ($venueInvoice->corporation_invoice_id === null) {
                        $venueInvoice->update(['corporation_invoice_id' => $corporationInvoice->id]);
                    }
                }
            }

            if ($corporationInvoice->wasRecentlyCreated) {
                $this->notifyOwner($corporationInvoice);
            }
        }
    }

    private function notifyOwner(VenueInvoice|CorporationInvoice $invoice): void
    {
        $owner = $invoice->corporation?->owner;

        if ($owner) {
            Notification::send($owner, new InvoiceGenerated($invoice));
        }
    }

    private function resolveDueDate(CorporationSubscription $subscription): string
    {
        $billingDay = min(28, max(1, (int) $subscription->billing_day));

        return "{$this->period}-{$billingDay}";
    }
}
