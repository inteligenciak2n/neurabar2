<?php

namespace App\Services\Billing;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Models\Tenant\VenueUsageRecord;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionCalculator
{
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
     * @return array<string, int>
     */
    public function calculateVenue(Venue $venue, string $period, ?string $usagePeriod = null): array
    {
        $subscription = $venue->subscription;

        if (! $subscription) {
            return $this->emptyResult();
        }

        $usagePeriod ??= self::usagePeriodFor($period);

        $base = (int) $subscription->base_value;
        $modulesValue = $this->calculateModules($venue, $period, prorate: true);
        $recurringModulesValue = $this->calculateModules($venue, $period, prorate: false);
        $metered = $this->calculateMetered($venue, $usagePeriod, $this->contractedModuleCodes($venue, $usagePeriod));
        $dedicatedSurcharge = (int) ($subscription->dedicated_surcharge ?? 0);

        return [
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
     * @return array<string, int>
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
     * @return int Centavos.
     */
    private function calculateModules(Venue $venue, string $period, bool $prorate): int
    {
        [$periodStart, $periodEnd] = self::periodBounds($period);
        $daysInPeriod = $periodStart->daysInMonth;

        $venueModules = $this->modulesOverlapping($venue, $periodStart, $periodEnd);

        if ($venueModules->isEmpty()) {
            return 0;
        }

        $corporationModules = $this->corporationModulesOverlapping($venue, $periodStart, $periodEnd);

        $total = 0;

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
            $total += Money::multiply($unitPrice * max(1, (int) $venueModule->quantity), $factor);
        }

        return $total;
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
        return $venue->modules()
            ->where(function ($query): void {
                $query->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
                    ->orWhereNotNull('ended_at');
            })
            ->where('started_at', '<=', $periodEnd)
            ->where(function ($query) use ($periodStart): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', $periodStart);
            })
            ->get();
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

        return $corporation->modules()
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
            ->keyBy('module_code');
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
     * @return int Centavos.
     */
    private function calculateMetered(Venue $venue, string $period, array $contractedModuleCodes): int
    {
        if ($contractedModuleCodes === []) {
            return 0;
        }

        $total = 0;

        $records = VenueUsageRecord::query()
            ->where('venue_id', $venue->id)
            ->where('period', $period)
            ->whereIn('module_code', $contractedModuleCodes)
            ->get();

        foreach ($records as $record) {
            $total += $this->calculateRecord($record);
        }

        return $total;
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
    private function calculateRecord(VenueUsageRecord $record): int
    {
        $quantity = max(0, (int) $record->quantity);
        $tiers = $this->resolveTiers((string) $record->module_code, $quantity);

        if ($tiers->isEmpty()) {
            return 0;
        }

        $basePrice = 0;
        $overagePrice = 0;
        $includedQuantity = 0;
        $overageQuantity = 0;
        $lastTierId = null;
        $consumedUnits = 0;

        foreach ($tiers as $tier) {
            $upperBound = $tier->max_quantity !== null
                ? min($quantity, (int) $tier->max_quantity)
                : $quantity;

            $unitsInTier = max(0, $upperBound - $consumedUnits);
            $consumedUnits = max($consumedUnits, $upperBound);

            if ($unitsInTier === 0) {
                continue;
            }

            $lastTierId = $tier->id;

            $tierIncluded = min($unitsInTier, max(0, (int) ($tier->included_quantity ?? 0)));
            $tierOverage = $unitsInTier - $tierIncluded;

            $includedQuantity += $tierIncluded;
            $overageQuantity += $tierOverage;

            // price_per_unit cobra apenas as unidades dentro do limite incluso
            // da faixa; o excedente da faixa é cobrado exclusivamente via
            // overage_price_per_unit/overage_flat_fee, evitando duplicidade.
            // Os preços unitários estão em centésimos de centavo e só viram
            // centavos depois de multiplicados pela quantidade.
            $basePrice += $tier->flat_price !== null
                ? (int) $tier->flat_price
                : Money::fromMicros((int) $tier->price_per_unit * $tierIncluded);

            if ($tierOverage > 0) {
                $overagePrice += (int) ($tier->overage_flat_fee ?? 0);
                $overagePrice += Money::fromMicros($tierOverage * (int) $tier->overage_price_per_unit);
            }
        }

        $record->update([
            'tier_id' => $lastTierId,
            'included_quantity' => $includedQuantity,
            'overage_quantity' => $overageQuantity,
            'base_calculated_price' => $basePrice,
            'overage_calculated_price' => $overagePrice,
            'total_calculated_price' => $basePrice + $overagePrice,
        ]);

        return $basePrice + $overagePrice;
    }

    /**
     * Todas as faixas alcançadas pela quantidade, da menor para a maior.
     *
     * @return Collection<int, ModuleUsageTier>
     */
    private function resolveTiers(string $moduleCode, int $quantity): Collection
    {
        return ModuleUsageTier::query()
            ->where('module_code', $moduleCode)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity')
            ->get();
    }
}
