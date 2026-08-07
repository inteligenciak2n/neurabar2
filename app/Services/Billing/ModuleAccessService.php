<?php

namespace App\Services\Billing;

use App\Enums\ModuleAccessReason;
use App\Enums\ModuleCode;
use App\Models\Tenant\Venue;
use App\Services\CorporationModuleCache;

/**
 * Fonte única de verdade sobre acesso a módulo. Antes a mesma regra existia
 * duplicada (e divergente) em RequireModule e User::canAccessModule().
 */
class ModuleAccessService
{
    public function check(?Venue $venue, ModuleCode|string|null $module): ModuleAccessResult
    {
        $module = $module instanceof ModuleCode ? $module : ModuleCode::tryFrom((string) $module);

        if (! $module) {
            return ModuleAccessResult::denied(ModuleAccessReason::UnknownModule);
        }

        if (! $venue) {
            return ModuleAccessResult::denied(ModuleAccessReason::NoVenue, $module);
        }

        if (BillingStatusService::isBlocked($venue)) {
            return ModuleAccessResult::denied(ModuleAccessReason::BillingBlocked, $module);
        }

        $corporation = $venue->corporation;

        if (! $corporation || ! in_array($module->value, CorporationModuleCache::remember($corporation), true)) {
            return ModuleAccessResult::denied(ModuleAccessReason::NotContracted, $module);
        }

        $activeModules = $venue->activeModules();

        if (! in_array($module->value, $activeModules, true)) {
            return ModuleAccessResult::denied(ModuleAccessReason::NotActiveForVenue, $module);
        }

        foreach ($module->dependsOn() as $dependency) {
            if (! in_array($dependency->value, $activeModules, true)) {
                return ModuleAccessResult::denied(ModuleAccessReason::MissingDependency, $module, $dependency);
            }
        }

        return ModuleAccessResult::granted($module);
    }

    public function allows(?Venue $venue, ModuleCode|string|null $module): bool
    {
        return $this->check($venue, $module)->allowed;
    }
}
