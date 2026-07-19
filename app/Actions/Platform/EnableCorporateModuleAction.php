<?php

namespace App\Actions\Platform;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\ModuleCatalog;
use App\Services\Billing\SubscriptionCalculator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EnableCorporateModuleAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    public function execute(Corporation $corporation, string $moduleCode, ?float $customMonthlyPrice = null): CorporationModule
    {
        $code = ModuleCode::tryFrom($moduleCode);

        if (! $code) {
            throw new InvalidArgumentException("Invalid module code: {$moduleCode}");
        }

        $catalog = ModuleCatalog::where('code', $code->value)->where('active', true)->first();

        if (! $catalog) {
            throw new InvalidArgumentException("Module {$code->label()} is not available in the catalog.");
        }

        return DB::transaction(function () use ($corporation, $code, $customMonthlyPrice) {
            $module = CorporationModule::firstOrNew([
                'corporation_id' => $corporation->id,
                'module_code' => $code->value,
            ]);

            $module->status = ModuleStatus::Active;
            $module->started_at = now();
            $module->ended_at = null;

            if ($customMonthlyPrice !== null) {
                $module->custom_monthly_price = $customMonthlyPrice;
            }

            $module->save();

            $this->calculator->calculateCorporation($corporation, now()->format('Y-m'));

            return $module;
        });
    }
}
