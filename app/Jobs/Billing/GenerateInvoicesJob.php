<?php

namespace App\Jobs\Billing;

use App\Enums\BillingMode;
use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationDiscount;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Notifications\Billing\InvoiceGenerated;
use App\Services\Billing\SubscriptionCalculator;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class GenerateInvoicesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * Uma única execução por período, mesmo com retries ou múltiplos workers:
     * gerar faturas duas vezes significa cobrar o cliente duas vezes.
     */
    public int $uniqueFor = 3600;

    public function __construct(private readonly string $period) {}

    public function uniqueId(): string
    {
        return $this->period;
    }

    public function handle(SubscriptionCalculator $calculator): void
    {
        CorporationSubscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trial, SubscriptionStatus::PastDue])
            ->whereNull('gateway_subscription_id')
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
        $aggregate = $this->emptyContribution();
        $venueInvoices = [];

        // Venues desativadas continuavam sendo faturadas todo mês: a coluna
        // `active` precisa filtrar a geração, não só a operação.
        $venues = $corporation->venues()
            ->where('active', true)
            ->with('subscription')
            ->get();

        foreach ($venues as $venue) {
            $result = $this->processVenue($venue, $corporation, $subscription, $calculator, $isUnified);

            if ($result === null) {
                continue;
            }

            [$invoice, $contribution] = $result;
            $venueInvoices[] = $invoice;

            if ($isUnified) {
                $aggregate = $this->addContribution($aggregate, $contribution);
            }
        }

        if ($isUnified) {
            $this->finalizeUnifiedInvoice($corporation, $subscription, $aggregate, $venueInvoices);
        }
    }

    /**
     * Calcula e persiste (ou reaproveita) a fatura de uma venue.
     *
     * @return array{0: VenueInvoice, 1: array<string, int>}|null
     */
    private function processVenue(Venue $venue, Corporation $corporation, CorporationSubscription $subscription, SubscriptionCalculator $calculator, bool $isUnified): ?array
    {
        if ($venue->subscription?->isBilledByGateway()) {
            return null;
        }

        $calculated = $calculator->refreshVenueSnapshot($venue, $this->period);

        if ($calculated === null) {
            return $this->reuseExistingVenueInvoice($venue, $isUnified);
        }

        // Período integralmente coberto pelo trial não gera fatura; trial que
        // termina no meio do mês gera fatura proporcional aos dias restantes.
        $billableFactor = $this->billableFactor($subscription, $venue);

        if ($billableFactor <= 0.0) {
            return null;
        }

        $calculated = $this->applyTrialFactor($calculated, $billableFactor);

        $discount = $isUnified ? null : $this->resolveDiscount($corporation);
        $discountValue = $this->calculateDiscountValue($calculated['total'], $discount);
        $venueTotal = max(0, $calculated['total'] - $discountValue);

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
                'discount_value' => $discountValue,
                'total_value' => $venueTotal,
            ]
        );

        if ($invoice->wasRecentlyCreated && ! $isUnified) {
            $this->notifyOwner($invoice);
        }

        return [$invoice, [
            'base' => $calculated['base'],
            'modules' => $calculated['modules'],
            'metered' => $calculated['metered'],
            'dedicated_surcharge' => $calculated['dedicated_surcharge'],
            'total' => $venueTotal,
        ]];
    }

    /**
     * @return array{0: VenueInvoice, 1: array<string, int>}|null
     */
    private function reuseExistingVenueInvoice(Venue $venue, bool $isUnified): ?array
    {
        $existingInvoice = VenueInvoice::query()
            ->where('venue_id', $venue->id)
            ->where('period', $this->period)
            ->first();

        if (! $existingInvoice) {
            return null;
        }

        if (! $isUnified) {
            return [$existingInvoice, $this->emptyContribution()];
        }

        return [$existingInvoice, [
            'base' => (int) $existingInvoice->base_value,
            'modules' => (int) $existingInvoice->modules_value,
            'metered' => (int) $existingInvoice->metered_value,
            'dedicated_surcharge' => (int) $existingInvoice->dedicated_surcharge,
            'total' => (int) $existingInvoice->total_value,
        ]];
    }

    /**
     * @param  array<int, VenueInvoice>  $venueInvoices
     */
    private function finalizeUnifiedInvoice(Corporation $corporation, CorporationSubscription $subscription, array $aggregate, array $venueInvoices): void
    {
        // O período já foi fechado (fatura paga/cancelada): recriar aqui gerava
        // uma segunda fatura cobrável para o mesmo mês.
        $alreadyFinalized = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->where('period', $this->period)
            ->where('is_finalized', true)
            ->exists();

        if ($alreadyFinalized) {
            return;
        }

        $discount = $this->resolveDiscount($corporation);
        $discountValue = $this->calculateDiscountValue($aggregate['total'], $discount);
        $finalTotal = max(0, $aggregate['total'] - $discountValue);

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
                'base_value' => $aggregate['base'],
                'modules_value' => $aggregate['modules'],
                'metered_value' => $aggregate['metered'],
                'dedicated_surcharge' => $aggregate['dedicated_surcharge'],
                'discount_value' => $discountValue,
                'total_value' => $finalTotal,
            ]
        );

        foreach ($venueInvoices as $venueInvoice) {
            if ($venueInvoice->corporation_invoice_id === null) {
                $venueInvoice->update(['corporation_invoice_id' => $corporationInvoice->id]);
            }
        }

        if ($corporationInvoice->wasRecentlyCreated) {
            $this->notifyOwner($corporationInvoice);
        }
    }

    /**
     * @return array<string, int>
     */
    private function emptyContribution(): array
    {
        return [
            'base' => 0,
            'modules' => 0,
            'metered' => 0,
            'dedicated_surcharge' => 0,
            'total' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $aggregate
     * @param  array<string, int>  $contribution
     * @return array<string, int>
     */
    private function addContribution(array $aggregate, array $contribution): array
    {
        foreach ($aggregate as $key => $value) {
            $aggregate[$key] = $value + $contribution[$key];
        }

        return $aggregate;
    }

    private function notifyOwner(VenueInvoice|CorporationInvoice $invoice): void
    {
        $owner = $invoice->corporation?->owner;

        if ($owner) {
            Notification::send($owner, new InvoiceGenerated($invoice));
        }
    }

    /**
     * @return array{type: string, value: int, months_used: int}|null
     */
    private function resolveDiscount(Corporation $corporation): ?array
    {
        $discount = CorporationDiscount::query()
            ->where('corporation_id', $corporation->id)
            ->activeForPeriod($this->period)
            ->orderByDesc('created_at')
            ->first();

        if (! $discount) {
            return null;
        }

        $monthsUsed = $this->discountedPeriodsBefore($corporation, $discount->valid_from->format('Y-m'));

        if ($discount->max_months !== null && $monthsUsed >= $discount->max_months) {
            return null;
        }

        return [
            'type' => $discount->type,
            'value' => (int) $discount->value,
            'months_used' => $monthsUsed,
        ];
    }

    /**
     * Quantos períodos anteriores já consumiram o desconto.
     *
     * No modo `per_venue` o desconto é gravado nas faturas das venues, então
     * contar apenas `corporation_invoices` tornava qualquer desconto vitalício.
     * O período corrente é excluído para que reexecutar o job no mesmo mês não
     * consuma uma ocorrência extra.
     */
    private function discountedPeriodsBefore(Corporation $corporation, string $validFrom): int
    {
        $corporationPeriods = CorporationInvoice::query()
            ->where('corporation_id', $corporation->id)
            ->where('period', '>=', $validFrom)
            ->where('period', '<', $this->period)
            ->where('discount_value', '>', 0)
            ->distinct()
            ->pluck('period');

        $venuePeriods = VenueInvoice::query()
            ->whereIn('venue_id', Venue::query()->select('id')->where('corporation_id', $corporation->id))
            ->where('period', '>=', $validFrom)
            ->where('period', '<', $this->period)
            ->where('discount_value', '>', 0)
            ->distinct()
            ->pluck('period');

        return $corporationPeriods->merge($venuePeriods)->unique()->count();
    }

    /**
     * @param  array{type: string, value: int, months_used: int}|null  $discount
     * @return int Centavos.
     */
    private function calculateDiscountValue(int $total, ?array $discount): int
    {
        if (! $discount) {
            return 0;
        }

        // Em `percentage` o campo guarda pontos-base (1500 = 15%); em `fixed`,
        // centavos.
        if ($discount['type'] === 'percentage') {
            return Money::percentage($total, $discount['value']);
        }

        return min($discount['value'], $total);
    }

    /**
     * Vencimento da fatura no dia de cobrança contratado.
     *
     * Com `billing_day = 1` a fatura nascia vencida no mesmo dia da geração.
     * Quando o dia contratado não respeita o prazo mínimo, o vencimento é
     * empurrado para a próxima ocorrência do mesmo dia.
     */
    private function resolveDueDate(CorporationSubscription $subscription): string
    {
        $billingDay = min(28, max(1, (int) $subscription->billing_day));
        $dueDate = Carbon::parse($this->period.'-01')->startOfDay()->day($billingDay);

        $minimumDueDate = Carbon::today()->addDays(max(0, (int) config('billing.minimum_due_days', 3)));

        if ($dueDate->lessThan($minimumDueDate)) {
            $dueDate = $minimumDueDate->copy()->day($billingDay);

            if ($dueDate->lessThan($minimumDueDate)) {
                $dueDate->addMonthNoOverflow();
            }
        }

        return $dueDate->toDateString();
    }

    /**
     * Proporção do período que está fora do trial.
     *
     * Faturar o mês inteiro de uma assinatura ainda em trial cobrava dias que
     * o contrato dizia serem gratuitos. Retorna 0.0 quando o período inteiro
     * está coberto pelo trial.
     */
    private function billableFactor(CorporationSubscription $subscription, Venue $venue): float
    {
        $venueSubscription = $venue->subscription;

        // A assinatura da venue, quando existe, é a fonte da verdade do trial:
        // cair para a da corporation aqui reintroduziria trial em venues que
        // já saíram dele.
        $trialEndsAt = $venueSubscription !== null
            ? $venueSubscription->trial_ends_at
            : $subscription->trial_ends_at;

        if ($trialEndsAt === null) {
            return 1.0;
        }

        $periodStart = Carbon::parse($this->period.'-01')->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();
        $daysInPeriod = $periodStart->daysInMonth;

        $subscriptionStart = $venueSubscription !== null
            ? $venueSubscription->started_at
            : $subscription->started_at;

        $trialStart = Carbon::parse($subscriptionStart ?? $periodStart)->startOfDay();
        $trialEnd = Carbon::parse($trialEndsAt)->startOfDay();

        $start = $trialStart->greaterThan($periodStart) ? $trialStart : $periodStart;
        $end = $trialEnd->lessThan($periodEnd) ? $trialEnd : $periodEnd;

        if ($end->lessThan($start)) {
            return 1.0;
        }

        $trialDays = min($daysInPeriod, (int) $start->diffInDays($end) + 1);

        return max(0.0, ($daysInPeriod - $trialDays) / $daysInPeriod);
    }

    /**
     * @param  array<string, int>  $calculated
     * @return array<string, int>
     */
    private function applyTrialFactor(array $calculated, float $factor): array
    {
        if ($factor >= 1.0) {
            return $calculated;
        }

        $base = Money::multiply($calculated['base'], $factor);
        $modules = Money::multiply($calculated['modules'], $factor);
        $dedicatedSurcharge = Money::multiply($calculated['dedicated_surcharge'], $factor);

        // O consumo medido é pós-pago e já aconteceu: não é proporcionalizado.
        return array_merge($calculated, [
            'base' => $base,
            'modules' => $modules,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total' => $base + $modules + $dedicatedSurcharge + $calculated['metered'],
        ]);
    }
}
