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
     * @param  string|null  $usagePeriod  Período do consumo medido. Padrão: mês anterior a $period.
     * @return array<string, float>|null
     */
    public function calculateVenue(Venue $venue, string $period, ?string $usagePeriod = null): ?array
    {
        $subscription = $venue->subscription;

        if (! $subscription) {
            return $this->emptyResult();
        }

        if ($this->hasFinalizedInvoice($venue, $period)) {
            return null;
        }

        $usagePeriod ??= self::usagePeriodFor($period);

        $base = (float) $subscription->base_value;
        $modulesValue = $this->calculateModules($venue, $period, prorate: true);
        $recurringModulesValue = $this->calculateModules($venue, $period, prorate: false);
        $metered = $this->calculateMetered($venue, $usagePeriod, $this->contractedModuleCodes($venue, $usagePeriod));
        $dedicatedSurcharge = (float) ($subscription->dedicated_surcharge ?? 0);

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
     * @return array<string, float>|null
     */
    public function refreshVenueSnapshot(Venue $venue, string $period, ?string $usagePeriod = null): ?array
    {
        $calculated = $this->calculateVenue($venue, $period, $usagePeriod);

        if ($calculated === null || ! $venue->subscription) {
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

        return $calculated;
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshCorporationSnapshot(Corporation $corporation, string $period, ?string $usagePeriod = null): array
    {
        $venueTotals = [];
        $grandTotal = 0.0;

        $usagePeriod ??= self::usagePeriodFor($period);

        foreach ($corporation->venues as $venue) {
            $calculated = $this->refreshVenueSnapshot($venue, $period, $usagePeriod);
            $venueTotals[$venue->id] = $calculated ?? $this->emptyResult();
            $grandTotal += $calculated['total'] ?? 0.0;
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
        $grandTotal = 0.0;

        $usagePeriod ??= self::usagePeriodFor($period);

        foreach ($corporation->venues as $venue) {
            $calculated = $this->calculateVenue($venue, $period, $usagePeriod);
            $venueTotals[$venue->id] = $calculated ?? $this->emptyResult();
            $grandTotal += $calculated['total'] ?? 0.0;
        }

        return [
            'venues' => $venueTotals,
            'total' => $grandTotal,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function emptyResult(): array
    {
        return [
            'base' => 0.0,
            'modules' => 0.0,
            'metered' => 0.0,
            'dedicated_surcharge' => 0.0,
            'total' => 0.0,
            'recurring_modules' => 0.0,
            'recurring_total' => 0.0,
        ];
    }

    private function hasFinalizedInvoice(Venue $venue, string $period): bool
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
     */
    private function calculateModules(Venue $venue, string $period, bool $prorate): float
    {
        [$periodStart, $periodEnd] = self::periodBounds($period);
        $daysInPeriod = $periodStart->daysInMonth;

        $venueModules = $this->modulesOverlapping($venue, $periodStart, $periodEnd);

        if ($venueModules->isEmpty()) {
            return 0.0;
        }

        $corporationModules = $this->corporationModulesOverlapping($venue, $periodStart, $periodEnd);

        $total = 0.0;

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
                ? (float) $corporationModule->custom_monthly_price
                : (float) ($corporationModule->catalog?->base_monthly_price ?? 0);

            $total += $unitPrice * max(1, (int) $venueModule->quantity) * $factor;
        }

        return round($total, 2);
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
     */
    private function calculateMetered(Venue $venue, string $period, array $contractedModuleCodes): float
    {
        if ($contractedModuleCodes === []) {
            return 0.0;
        }

        $total = 0.0;

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

    private function calculateRecord(VenueUsageRecord $record): float
    {
        $tier = $this->resolveTier($record);

        if (! $tier) {
            return 0.0;
        }

        $included = (int) ($tier->included_quantity ?? 0);
        $quantity = max(0, (int) $record->quantity);
        $overageQuantity = max(0, $quantity - $included);

        // price_per_unit cobra apenas as unidades dentro do limite incluso; o
        // excedente é cobrado exclusivamente via overage_price_per_unit/overage_flat_fee
        // logo abaixo, evitando cobrança em duplicidade das unidades excedentes.
        $basePrice = $tier->flat_price !== null
            ? (float) $tier->flat_price
            : ((float) $tier->price_per_unit * min($quantity, $included));

        $overagePrice = 0.0;

        if ($overageQuantity > 0) {
            $overagePrice += (float) ($tier->overage_flat_fee ?? 0);
            $overagePrice += $overageQuantity * (float) $tier->overage_price_per_unit;
        }

        $record->update([
            'tier_id' => $tier->id,
            'included_quantity' => min($quantity, $included),
            'overage_quantity' => $overageQuantity,
            'base_calculated_price' => $basePrice,
            'overage_calculated_price' => $overagePrice,
            'total_calculated_price' => $basePrice + $overagePrice,
        ]);

        return $basePrice + $overagePrice;
    }

    private function resolveTier(VenueUsageRecord $record): ?ModuleUsageTier
    {
        $quantity = (int) $record->quantity;

        $query = ModuleUsageTier::query()
            ->where('module_code', $record->module_code)
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($query) use ($quantity): void {
                $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        return $query;
    }
}
