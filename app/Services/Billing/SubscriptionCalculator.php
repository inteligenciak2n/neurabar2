<?php

namespace App\Services\Billing;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\PlanModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueModuleUsageTierOverride;
use App\Models\Tenant\VenuePlanAssignment;
use App\Models\Tenant\VenueSubscription;
use App\Models\Tenant\VenueUsageRecord;
use App\Support\Money;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionCalculator
{
    private readonly UsagePricingResolver $usagePricingResolver;

    private readonly UsageTierCalculator $usageTierCalculator;

    /**
     * Fora de um lote o cache fica desligado: telas e ações alteram módulos e
     * recalculam na mesma instância, e um valor memoizado ali seria obsoleto.
     */
    private bool $batching = false;

    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(
        ?UsagePricingResolver $usagePricingResolver = null,
        ?UsageTierCalculator $usageTierCalculator = null,
    ) {
        $this->usagePricingResolver = $usagePricingResolver ?? new UsagePricingResolver;
        $this->usageTierCalculator = $usageTierCalculator ?? new UsageTierCalculator;
    }

    /**
     * Executa o callback com memoização de catálogo/módulos/faixas ativa.
     *
     * Usado pela geração mensal de faturas, onde os mesmos registros são lidos
     * uma vez por venue da mesma corporation.
     */
    public function duringBatch(Closure $callback): mixed
    {
        $this->batching = true;
        $this->memo = [];

        try {
            return $callback();
        } finally {
            $this->batching = false;
            $this->memo = [];
        }
    }

    private function remember(string $key, Closure $resolver): mixed
    {
        if (! $this->batching) {
            return $resolver();
        }

        return $this->memo[$key] ??= $resolver();
    }

    /**
     * Resolve o período de consumo faturado junto com a mensalidade do período
     * informado. A assinatura é pré-paga (mês corrente) e o consumo medido é
     * pós-pago (mês fechado anterior) — cobrar o consumo do próprio mês da
     * fatura significava sempre faturar zero de excedente.
     */
    public static function usagePeriodFor(string $period): string
    {
        return Carbon::parse($period.'-01')->subMonthNoOverflow()->format('Y-m');
    }

    /**
     * Cálculo puro: não escreve nada. Use `refreshVenueSnapshot()` quando o
     * valor recorrente da assinatura também precisar ser regravado.
     *
     * Todos os valores retornados são inteiros em centavos.
     *
     * @param  string|null  $usagePeriod  Período do consumo medido. Padrão: mês anterior a $period.
     * @return array<string, mixed>
     */
    public function calculateVenue(Venue $venue, string $period, ?string $usagePeriod = null): array
    {
        $subscription = $venue->subscription;

        if (! $subscription) {
            return $this->emptyResult();
        }

        $usagePeriod ??= self::usagePeriodFor($period);

        $planAssignment = $this->remember(
            'plan-assignment:'.$venue->id.':'.$period,
            fn () => $this->usagePricingResolver->resolveAssignment($venue, $period),
        );
        $base = (int) ($planAssignment?->planCatalogVersion?->minimum_monthly_price ?? $subscription->base_value);
        $proratedModules = $this->calculateModules($venue, $period, prorate: true);
        $modulesValue = $proratedModules['total'];
        $recurringModulesValue = $this->calculateModules($venue, $period, prorate: false)['total'];
        $meteredResult = $this->calculateMetered($venue, $usagePeriod, $this->contractedModuleCodes($venue, $usagePeriod));
        $metered = $meteredResult['total'];
        $dedicatedSurcharge = (int) ($subscription->dedicated_surcharge ?? 0);

        return [
            'plan_catalog_id' => $planAssignment?->plan_catalog_id,
            'base' => $base,
            'modules' => $modulesValue,
            'metered' => $metered,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total' => $base + $modulesValue + $metered + $dedicatedSurcharge,
            // A assinatura guarda a mensalidade cheia (valor recorrente
            // contratado); a proration vale só para o que será faturado
            // neste período.
            'recurring_modules' => $recurringModulesValue,
            'recurring_total' => $base + $recurringModulesValue + $metered + $dedicatedSurcharge,
            // Linhas detalhadas para a auditoria da fatura.
            'module_lines' => $proratedModules['lines'],
            'metered_lines' => $meteredResult['lines'],
            'usage_period' => $usagePeriod,
        ];
    }

    /**
     * Recalcula e grava a mensalidade recorrente da assinatura da venue.
     *
     * A gravação vivia dentro de `calculateVenue()`, que é chamado também por
     * telas e webhooks: dois processos calculando ao mesmo tempo (registro de
     * consumo e geração de fatura, por exemplo) sobrescreviam o resultado um do
     * outro. Agora a persistência é explícita e acontece sob lock da linha.
     *
     * Quando o período já tem fatura finalizada o snapshot continua sendo
     * regravado (a mensalidade recorrente precisa refletir módulos ativados no
     * meio do mês), mas o retorno é `null` para sinalizar ao faturamento que
     * aquele período não deve ser refaturado.
     *
     * @return array<string, int>|null
     */
    public function refreshVenueSnapshot(Venue $venue, string $period, ?string $usagePeriod = null): ?array
    {
        $calculated = $this->calculateVenue($venue, $period, $usagePeriod);

        if (! $venue->subscription) {
            return $calculated;
        }

        DB::connection($venue->subscription->getConnectionName())->transaction(function () use ($venue, $calculated): void {
            $subscription = VenueSubscription::query()
                ->whereKey($venue->subscription->getKey())
                ->lockForUpdate()
                ->first();

            $subscription?->update([
                'plan_catalog_id' => $calculated['plan_catalog_id'] ?? $subscription->plan_catalog_id,
                'base_value' => $calculated['base'],
                'modules_value' => $calculated['recurring_modules'],
                'metered_value' => $calculated['metered'],
                'total_value' => $calculated['recurring_total'],
            ]);
        });

        $venue->unsetRelation('subscription');

        return $this->isPeriodClosed($venue, $period) ? null : $calculated;
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshCorporationSnapshot(Corporation $corporation, string $period, ?string $usagePeriod = null): array
    {
        $venueTotals = [];
        $grandTotal = 0;

        $usagePeriod ??= self::usagePeriodFor($period);

        foreach ($corporation->venues as $venue) {
            $calculated = $this->refreshVenueSnapshot($venue, $period, $usagePeriod);
            $venueTotals[$venue->id] = $calculated ?? $this->emptyResult();
            $grandTotal += $calculated['total'] ?? 0;
        }

        return [
            'venues' => $venueTotals,
            'total' => $grandTotal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateCorporation(Corporation $corporation, string $period, ?string $usagePeriod = null): array
    {
        $venueTotals = [];
        $grandTotal = 0;

        $usagePeriod ??= self::usagePeriodFor($period);

        foreach ($corporation->venues as $venue) {
            $calculated = $this->calculateVenue($venue, $period, $usagePeriod);
            $venueTotals[$venue->id] = $calculated;
            $grandTotal += $calculated['total'];
        }

        return [
            'venues' => $venueTotals,
            'total' => $grandTotal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResult(): array
    {
        return [
            'base' => 0,
            'modules' => 0,
            'metered' => 0,
            'dedicated_surcharge' => 0,
            'total' => 0,
            'recurring_modules' => 0,
            'recurring_total' => 0,
            'module_lines' => [],
            'metered_lines' => [],
            'usage_period' => null,
        ];
    }

    /**
     * Um período com fatura finalizada não pode ser refaturado, mas continua
     * podendo ter o snapshot da assinatura recalculado.
     */
    public function isPeriodClosed(Venue $venue, string $period): bool
    {
        $invoice = VenueInvoice::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->where('is_finalized', true)
            ->first();

        return $invoice !== null;
    }

    /**
     * Mensalidade dos módulos do período, proporcional aos dias de vigência.
     * Sem proration, um módulo ativado no dia 2 e cancelado no dia 28 nunca era
     * faturado — exploit trivial e repetível todo mês.
     *
     * @return array{total: int, lines: list<array<string, mixed>>} Valores em centavos.
     */
    private function calculateModules(Venue $venue, string $period, bool $prorate): array
    {
        [$periodStart, $periodEnd] = self::periodBounds($period);
        $daysInPeriod = $periodStart->daysInMonth;

        $venueModules = $this->modulesOverlapping($venue, $periodStart, $periodEnd);

        if ($venueModules->isEmpty()) {
            return ['total' => 0, 'lines' => []];
        }

        $corporationModules = $this->corporationModulesOverlapping($venue, $periodStart, $periodEnd);

        $total = 0;
        $lines = [];

        foreach ($venueModules as $venueModule) {
            $corporationModule = $corporationModules->get($venueModule->module_code);

            if (! $corporationModule) {
                continue;
            }

            // O valor recorrente reflete só o que segue vigente; o proporcional
            // ainda cobra os dias usados por módulos encerrados no período.
            if (! $prorate && ($venueModule->ended_at !== null || $corporationModule->ended_at !== null)) {
                continue;
            }

            $factor = $prorate
                ? $this->overlapFactor($venueModule, $periodStart, $periodEnd, $daysInPeriod)
                    * $this->overlapFactor($corporationModule, $periodStart, $periodEnd, $daysInPeriod)
                : 1.0;

            if ($factor <= 0.0) {
                continue;
            }

            $unitPrice = $corporationModule->custom_monthly_price !== null
                ? (int) $corporationModule->custom_monthly_price
                : (int) ($corporationModule->catalog?->base_monthly_price ?? 0);

            // Arredonda por módulo: somar frações de centavo e arredondar só no
            // fim fazia o total da fatura divergir da soma das linhas exibidas.
            $quantity = max(1, (int) $venueModule->quantity);
            $lineTotal = Money::multiply($unitPrice * $quantity, $factor);
            $total += $lineTotal;

            $lines[] = [
                'module_code' => (string) $venueModule->module_code,
                'quantity' => $quantity,
                'unit_amount' => $unitPrice,
                'total_amount' => $lineTotal,
            ];
        }

        return ['total' => $total, 'lines' => $lines];
    }

    /**
     * Códigos de módulo vigentes em algum momento do período. Impede que consumo
     * medido de um módulo nunca contratado gere cobrança de excedente.
     *
     * @return list<string>
     */
    private function contractedModuleCodes(Venue $venue, string $period): array
    {
        [$periodStart, $periodEnd] = self::periodBounds($period);

        $corporationModules = $this->corporationModulesOverlapping($venue, $periodStart, $periodEnd);

        return $this->modulesOverlapping($venue, $periodStart, $periodEnd)
            ->pluck('module_code')
            ->unique()
            ->filter(fn (string $code): bool => $corporationModules->has($code))
            ->values()
            ->all();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function periodBounds(string $period): array
    {
        $start = Carbon::parse($period.'-01')->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * Assinaturas de módulo da venue cuja vigência intersecta o período. Módulos
     * já encerrados entram — é justamente o que a proration precisa cobrar.
     *
     * @return Collection<int, VenueModule>
     */
    private function modulesOverlapping(Venue $venue, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        return $this->remember(
            'venue_modules:'.$venue->id.':'.$periodStart->format('Y-m'),
            fn (): Collection => $venue->modules()
                ->where(function ($query): void {
                    $query->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
                        ->orWhereNotNull('ended_at');
                })
                ->where('started_at', '<=', $periodEnd)
                ->where(function ($query) use ($periodStart): void {
                    $query->whereNull('ended_at')->orWhere('ended_at', '>=', $periodStart);
                })
                ->get(),
        );
    }

    /**
     * Uma única query com o catálogo já eager-loaded, para evitar N+1 por
     * módulo/venue durante o faturamento mensal.
     *
     * @return Collection<string, CorporationModule>
     */
    private function corporationModulesOverlapping(Venue $venue, Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $corporation = $venue->corporation;

        if (! $corporation) {
            return collect();
        }

        return $this->remember(
            'corporation_modules:'.$corporation->id.':'.$periodStart->format('Y-m'),
            fn (): Collection => $corporation->modules()
                ->where(function ($query): void {
                    $query->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
                        ->orWhereNotNull('ended_at');
                })
                ->where('started_at', '<=', $periodEnd)
                ->where(function ($query) use ($periodStart): void {
                    $query->whereNull('ended_at')->orWhere('ended_at', '>=', $periodStart);
                })
                ->with('catalog:id,code,base_monthly_price')
                ->get()
                ->keyBy('module_code'),
        );
    }

    /**
     * Fração do período em que o módulo esteve vigente (dias inclusivos).
     */
    private function overlapFactor(Model $module, Carbon $periodStart, Carbon $periodEnd, int $daysInPeriod): float
    {
        $start = $module->started_at !== null && $module->started_at->greaterThan($periodStart)
            ? $module->started_at->copy()->startOfDay()
            : $periodStart->copy();

        $end = $module->ended_at !== null && $module->ended_at->lessThan($periodEnd)
            ? $module->ended_at->copy()->endOfDay()
            : $periodEnd->copy();

        if ($end->lessThan($start)) {
            return 0.0;
        }

        $activeDays = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;

        return min(1.0, $activeDays / $daysInPeriod);
    }

    /**
     * @param  list<string>  $contractedModuleCodes
     * @return array{total: int, lines: list<array<string, mixed>>} Valores em centavos.
     */
    private function calculateMetered(Venue $venue, string $period, array $contractedModuleCodes): array
    {
        if ($contractedModuleCodes === []) {
            return ['total' => 0, 'lines' => []];
        }

        $total = 0;
        $lines = [];

        $records = VenueUsageRecord::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->whereIn('module_code', $contractedModuleCodes)
            ->get();

        foreach ($records as $record) {
            $recordTotal = $this->calculateRecord($record, $venue, $period);
            $total += $recordTotal;

            if ($recordTotal === 0) {
                continue;
            }

            $lines[] = [
                'module_code' => (string) $record->module_code,
                'quantity' => max(0, (int) $record->quantity),
                'total_amount' => $recordTotal,
            ];
        }

        return ['total' => $total, 'lines' => $lines];
    }

    /**
     * Cobrança graduada: a quantidade é fatiada entre todas as faixas
     * atravessadas e cada fatia é cobrada com o preço da sua própria faixa.
     *
     * A cobrança por faixa única aplicava o preço da faixa alcançada sobre a
     * quantidade inteira, o que tornava a tabela não monotônica — consumir uma
     * unidade a mais podia baratear a fatura. Somando as faixas o valor só
     * pode crescer com a quantidade.
     */
    private function calculateRecord(VenueUsageRecord $record, Venue $venue, string $period): int
    {
        $quantity = max(0, (int) $record->quantity);
        $pricing = $this->resolvePricing($venue, (string) $record->module_code, $period, $quantity);
        $tiers = $pricing['tiers'];

        if ($tiers->isEmpty()) {
            return 0;
        }

        $calculated = $this->usageTierCalculator->calculate($tiers, $quantity);
        $lastTier = $calculated['last_tier'];

        $record->update([
            'tier_id' => $lastTier instanceof ModuleUsageTier ? $lastTier->id : null,
            'plan_module_usage_tier_id' => $lastTier instanceof PlanModuleUsageTier ? $lastTier->id : null,
            'venue_module_usage_tier_override_id' => $lastTier instanceof VenueModuleUsageTierOverride ? $lastTier->id : null,
            'venue_plan_assignment_id' => $pricing['assignment']?->id,
            'plan_catalog_version_id' => $pricing['assignment']?->plan_catalog_version_id,
            'included_quantity' => $calculated['included_quantity'],
            'overage_quantity' => $calculated['overage_quantity'],
            'base_calculated_price' => $calculated['base_price'],
            'overage_calculated_price' => $calculated['overage_price'],
            'total_calculated_price' => $calculated['total_price'],
        ]);

        return $calculated['total_price'];
    }

    /**
     * Todas as faixas alcançadas pela quantidade, da menor para a maior.
     *
     * @return array{assignment: VenuePlanAssignment|null, tiers: Collection<int, ModuleUsageTier|PlanModuleUsageTier|VenueModuleUsageTierOverride>}
     */
    private function resolvePricing(Venue $venue, string $moduleCode, string $period, int $quantity): array
    {
        $pricing = $this->remember(
            'usage-pricing:'.$venue->id.':'.$moduleCode.':'.$period,
            fn (): array => $this->usagePricingResolver->resolve($venue, $moduleCode, $period),
        );

        $pricing['tiers'] = $pricing['tiers']
            ->filter(fn (ModuleUsageTier|PlanModuleUsageTier|VenueModuleUsageTierOverride $tier): bool => (int) $tier->min_quantity <= $quantity)
            ->values();

        return $pricing;
    }
}
