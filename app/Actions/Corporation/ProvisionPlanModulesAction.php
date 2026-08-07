<?php

namespace App\Actions\Corporation;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\CorporationModuleCache;
use App\Services\VenueModuleCache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProvisionPlanModulesAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    /**
     * Ativa na corporation e na venue todos os módulos declarados no plano.
     */
    public function execute(Corporation $corporation, Venue $venue, PlanCatalog $plan): void
    {
        $moduleCodes = $plan->includedModuleCodes();

        if ($moduleCodes === []) {
            return;
        }

        $this->ensureCatalogsAreActive($moduleCodes);

        DB::transaction(function () use ($corporation, $venue, $moduleCodes): void {
            $now = now();

            foreach ($moduleCodes as $code) {
                CorporationModule::firstOrCreate(
                    [
                        'corporation_id' => $corporation->id,
                        'module_code' => $code,
                    ],
                    [
                        'status' => ModuleStatus::Trial->value,
                        'started_at' => $now,
                    ]
                );

                VenueModule::firstOrCreate(
                    [
                        'venue_id' => $venue->id,
                        'module_code' => $code,
                    ],
                    [
                        'status' => ModuleStatus::Trial->value,
                        'quantity' => 1,
                        'started_at' => $now,
                    ]
                );
            }

            $this->calculator->refreshVenueSnapshot($venue, $now->format('Y-m'));
            VenueModuleCache::forget($venue);
            CorporationModuleCache::forget($corporation);
        });
    }

    /**
     * @param  array<int, string>  $moduleCodes
     */
    private function ensureCatalogsAreActive(array $moduleCodes): void
    {
        $activeCodes = ModuleCatalog::query()
            ->whereIn('code', $moduleCodes)
            ->where('active', true)
            ->pluck('code')
            ->all();

        foreach ($moduleCodes as $code) {
            if (! in_array($code, $activeCodes, true)) {
                $label = ModuleCode::tryFrom($code)?->label() ?? $code;

                throw new InvalidArgumentException("Módulo {$label} não está disponível no catálogo.");
            }
        }
    }
}
