<?php

namespace App\Actions\Billing;

use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\PlanCatalogVersion;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateCatalogPricesAction
{
    /**
     * @param  array<string, int>  $planPrices
     * @param  array<string, int>  $modulePrices
     * @param  array<string, list<array<string, int|string|null>>>  $planUsageTiers
     * @return array{plans_updated: int, modules_updated: int, tiers_created: int}
     */
    public function execute(array $planPrices, array $modulePrices, Carbon $effectiveFrom, array $planUsageTiers = []): array
    {
        return DB::connection('saas')->transaction(function () use ($planPrices, $modulePrices, $effectiveFrom, $planUsageTiers): array {
            $result = [
                'plans_updated' => 0,
                'modules_updated' => 0,
                'tiers_created' => 0,
            ];

            $planCodes = array_values(array_unique([...array_keys($planPrices), ...array_keys($planUsageTiers)]));

            foreach ($planCodes as $code) {
                $plan = PlanCatalog::query()->where('code', $code)->lockForUpdate()->first();

                if (! $plan) {
                    throw new InvalidArgumentException("Plano [{$code}] não encontrado.");
                }

                $sourceVersion = $plan->versions()
                    ->where('status', 'published')
                    ->with('usageTiers')
                    ->latest('effective_from')
                    ->first();

                if (! $sourceVersion) {
                    throw new InvalidArgumentException("O plano [{$code}] não possui uma versão publicada.");
                }

                if (! array_key_exists($code, $planUsageTiers) && $sourceVersion->usageTiers->isEmpty()) {
                    throw new InvalidArgumentException("O plano [{$code}] não possui faixas de consumo para copiar.");
                }

                if ($sourceVersion->effective_from->gte($effectiveFrom)) {
                    throw new InvalidArgumentException("A nova vigência do plano [{$code}] deve ser posterior a {$sourceVersion->effective_from->toDateString()}.");
                }

                $price = $planPrices[$code] ?? (int) $sourceVersion->minimum_monthly_price;
                $version = $this->createVersion($plan, $sourceVersion, $price, $effectiveFrom);
                $tiers = $planUsageTiers[$code] ?? $sourceVersion->usageTiers
                    ->map(fn ($tier): array => $tier->only([
                        'module_code', 'min_quantity', 'max_quantity', 'included_quantity',
                        'price_per_unit', 'flat_price', 'overage_price_per_unit',
                        'overage_flat_fee', 'currency',
                    ]))
                    ->all();

                $this->validateTiers($code, $tiers);

                foreach ($tiers as $tier) {
                    $version->usageTiers()->create(collect($tier)->only([
                        'module_code', 'min_quantity', 'max_quantity', 'included_quantity',
                        'price_per_unit', 'flat_price', 'overage_price_per_unit',
                        'overage_flat_fee', 'currency',
                    ])->all());
                    $result['tiers_created']++;
                }

                $plan->versions()
                    ->where('status', 'published')
                    ->where('plan_catalog_versions.id', '!=', $version->id)
                    ->where(function ($query) use ($effectiveFrom): void {
                        $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $effectiveFrom);
                    })
                    ->update(['effective_until' => $effectiveFrom->copy()->subDay()]);

                if (array_key_exists($code, $planPrices)) {
                    $previousPrice = (int) $plan->monthly_price;
                    $plan->update(['monthly_price' => $price]);
                    AuditLogger::record(
                        'plan.price.updated',
                        $plan,
                        ['monthly_price' => $previousPrice],
                        ['monthly_price' => $price],
                    );
                }
                AuditLogger::record(
                    'plan.version.published',
                    $version,
                    null,
                    AuditLogger::snapshot($version, [
                        'plan_catalog_id', 'version', 'status', 'effective_from',
                        'minimum_monthly_price', 'infrastructure_type', 'currency',
                    ]),
                );
                $result['plans_updated']++;
            }

            foreach ($modulePrices as $code => $price) {
                $module = ModuleCatalog::query()->where('code', $code)->lockForUpdate()->first();

                if (! $module) {
                    throw new InvalidArgumentException("Módulo [{$code}] não encontrado.");
                }

                $previousPrice = (int) $module->base_monthly_price;
                $module->update(['base_monthly_price' => $price]);
                AuditLogger::record(
                    'module_catalog.updated',
                    $module,
                    ['base_monthly_price' => $previousPrice],
                    ['base_monthly_price' => $price],
                );
                $result['modules_updated']++;
            }

            return $result;
        });
    }

    private function createVersion(
        PlanCatalog $plan,
        PlanCatalogVersion $sourceVersion,
        int $price,
        Carbon $effectiveFrom,
    ): PlanCatalogVersion {
        return $plan->versions()->create([
            'version' => ((int) $plan->versions()->max('version')) + 1,
            'status' => 'published',
            'effective_from' => $effectiveFrom,
            'minimum_monthly_price' => $price,
            'infrastructure_type' => $sourceVersion->infrastructure_type,
            'currency' => $sourceVersion->currency,
        ]);
    }

    /** @param  list<array<string, int|string|null>>  $tiers */
    private function validateTiers(string $planCode, array $tiers): void
    {
        if ($tiers === []) {
            throw new InvalidArgumentException("O plano [{$planCode}] deve possuir ao menos uma faixa de consumo.");
        }

        $moduleCodes = collect($tiers)->pluck('module_code')->unique()->values();
        $existingModuleCodes = ModuleCatalog::query()->whereIn('code', $moduleCodes)->pluck('code');
        $missingModuleCodes = $moduleCodes->diff($existingModuleCodes);

        if ($missingModuleCodes->isNotEmpty()) {
            throw new InvalidArgumentException("Módulos das faixas do plano [{$planCode}] não encontrados: {$missingModuleCodes->implode(', ')}.");
        }

        foreach (collect($tiers)->groupBy('module_code') as $moduleCode => $moduleTiers) {
            $ordered = $moduleTiers->sortBy(fn (array $tier): int => (int) $tier['min_quantity'])->values();

            if ((int) $ordered->first()['min_quantity'] !== 0) {
                throw new InvalidArgumentException("As faixas de [{$planCode}/{$moduleCode}] devem iniciar em zero.");
            }

            foreach ($ordered as $index => $tier) {
                foreach (['included_quantity', 'price_per_unit', 'overage_price_per_unit'] as $field) {
                    if (! isset($tier[$field]) || (int) $tier[$field] < 0) {
                        throw new InvalidArgumentException("O campo [{$field}] da faixa [{$planCode}/{$moduleCode}] deve ser maior ou igual a zero.");
                    }
                }

                $minimum = (int) $tier['min_quantity'];
                $maximum = $tier['max_quantity'] === null ? null : (int) $tier['max_quantity'];

                if ($maximum !== null && $maximum < $minimum) {
                    throw new InvalidArgumentException("A faixa [{$planCode}/{$moduleCode}] possui máximo menor que o mínimo.");
                }

                if ($index > 0) {
                    $previousMaximum = $ordered[$index - 1]['max_quantity'];

                    if ($previousMaximum === null || $minimum !== (int) $previousMaximum + 1) {
                        throw new InvalidArgumentException("As faixas de [{$planCode}/{$moduleCode}] possuem lacuna ou sobreposição.");
                    }
                }
            }
        }
    }
}
