<?php

namespace App\Services\Billing;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\ModuleUsageTier;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use App\Models\Tenant\VenueUsageRecord;
use Illuminate\Support\Carbon;

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
        $billableModules = $this->resolveBillableModules($venue);
        $modulesValue = $billableModules['value'];
        $metered = $this->calculateMetered($venue, $usagePeriod, $billableModules['codes']);
        $dedicatedSurcharge = (float) ($subscription->dedicated_surcharge ?? 0);

        $total = $base + $modulesValue + $metered + $dedicatedSurcharge;

        $subscription->update([
            'modules_value' => $modulesValue,
            'metered_value' => $metered,
            'total_value' => $total,
        ]);

        return [
            'base' => $base,
            'modules' => $modulesValue,
            'metered' => $metered,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total' => $total,
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
     * Preço das mensalidades de módulo e, junto, a lista de códigos efetivamente
     * contratados — usada para impedir que consumo medido de um módulo nunca
     * contratado (ou já cancelado) gere cobrança de excedente.
     *
     * @return array{value: float, codes: list<string>}
     */
    private function resolveBillableModules(Venue $venue): array
    {
        $venueModules = $venue->modules()
            ->whereIn('status', [ModuleStatus::Active, ModuleStatus::Trial])
            ->where(function ($query): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', now());
            })
            ->get();

        if ($venueModules->isEmpty()) {
            return ['value' => 0.0, 'codes' => []];
        }

        // Carrega todos os módulos ativos da corporation em uma única query (com o
        // catálogo já eager-loaded) para evitar N+1 por módulo/venue durante o
        // faturamento mensal (GenerateInvoicesJob percorre todas as venues).
        $corporationModules = $venue->corporation
            ?->activeModules()
            ->with('catalog:id,code,base_monthly_price')
            ->get()
            ->keyBy('module_code') ?? collect();

        $total = 0.0;
        $codes = [];

        foreach ($venueModules as $venueModule) {
            $corporationModule = $corporationModules->get($venueModule->module_code);

            if (! $corporationModule) {
                continue;
            }

            $codes[] = (string) $venueModule->module_code;

            $unitPrice = $corporationModule->custom_monthly_price !== null
                ? (float) $corporationModule->custom_monthly_price
                : (float) ($corporationModule->catalog?->base_monthly_price ?? 0);

            $total += $unitPrice * max(1, (int) $venueModule->quantity);
        }

        return ['value' => $total, 'codes' => array_values(array_unique($codes))];
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
