<?php

namespace App\Actions\Platform;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use Illuminate\Support\Facades\DB;

class EnableCorporateModuleAction
{
    public function execute(Corporation $corporation, string $moduleCode, ?float $customMonthlyPrice = null): CorporationModule
    {
        return DB::transaction(function () use ($corporation, $moduleCode, $customMonthlyPrice) {
            $module = CorporationModule::firstOrNew([
                'corporation_id' => $corporation->id,
                'module_code' => $moduleCode,
            ]);

            $module->status = ModuleStatus::Active;
            $module->started_at = now();
            $module->ended_at = null;

            if ($customMonthlyPrice !== null) {
                $module->custom_monthly_price = $customMonthlyPrice;
            }

            $module->save();

            return $module;
        });
    }
}
