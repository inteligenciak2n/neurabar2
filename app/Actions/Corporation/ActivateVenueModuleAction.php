<?php

namespace App\Actions\Corporation;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\VenueModuleCache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ActivateVenueModuleAction
{
    public function execute(Venue $venue, string $moduleCode, int $quantity = 1): VenueModule
    {
        $code = ModuleCode::tryFrom($moduleCode);

        if (! $code) {
            throw new InvalidArgumentException("Invalid module code: {$moduleCode}");
        }

        if (! $venue->corporation?->hasActiveModule($code)) {
            throw new InvalidArgumentException("Module {$code->label()} is not available in the corporation plan.");
        }

        return DB::transaction(function () use ($venue, $code, $quantity) {
            $module = VenueModule::firstOrNew([
                'venue_id' => $venue->id,
                'module_code' => $code->value,
            ]);

            $module->status = ModuleStatus::Active;
            $module->quantity = $quantity;
            $module->started_at = now();
            $module->ended_at = null;
            $module->save();

            VenueModuleCache::forget($venue);

            return $module;
        });
    }
}
