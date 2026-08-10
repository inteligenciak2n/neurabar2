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
     * @return array{plans_updated: int, modules_updated: int, tiers_copied: int}
     */
    public function execute(array $planPrices, array $modulePrices, Carbon $effectiveFrom): array
    {
        return DB::connection('saas')->transaction(function () use ($planPrices, $modulePrices, $effectiveFrom): array {
            $result = [
                'plans_updated' => 0,
                'modules_updated' => 0,
                'tiers_copied' => 0,
            ];

            foreach ($planPrices as $code => $price) {
                $plan = PlanCatalog::query()->where('code', $code)->lockForUpdate()->first();

                if (! $plan) {
                    throw new InvalidArgumentException("Plano [{$code}] não encontrado.");
                }

                $sourceVersion = $plan->versions()
                    ->where('status', 'published')
                    ->with('usageTiers')
                    ->latest('effective_from')
                    ->first();

                if (! $sourceVersion || $sourceVersion->usageTiers->isEmpty()) {
                    throw new InvalidArgumentException("O plano [{$code}] não possui uma versão publicada completa para copiar.");
                }

                if ($sourceVersion->effective_from->gte($effectiveFrom)) {
                    throw new InvalidArgumentException("A nova vigência do plano [{$code}] deve ser posterior a {$sourceVersion->effective_from->toDateString()}.");
                }

                $previousPrice = (int) $plan->monthly_price;
                $version = $this->createVersion($plan, $sourceVersion, $price, $effectiveFrom);

                foreach ($sourceVersion->usageTiers as $tier) {
                    $version->usageTiers()->create($tier->only([
                        'module_code', 'min_quantity', 'max_quantity', 'included_quantity',
                        'price_per_unit', 'flat_price', 'overage_price_per_unit',
                        'overage_flat_fee', 'currency',
                    ]));
                    $result['tiers_copied']++;
                }

                $plan->versions()
                    ->where('status', 'published')
                    ->where('plan_catalog_versions.id', '!=', $version->id)
                    ->where(function ($query) use ($effectiveFrom): void {
                        $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $effectiveFrom);
                    })
                    ->update(['effective_until' => $effectiveFrom->copy()->subDay()]);

                $plan->update(['monthly_price' => $price]);
                AuditLogger::record(
                    'plan.price.updated',
                    $plan,
                    ['monthly_price' => $previousPrice],
                    ['monthly_price' => $price],
                );
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
}
