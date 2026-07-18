<?php

namespace App\Actions\Corporation;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Illuminate\Support\Facades\DB;

class ActivateVenueModuleAction
{
    public function execute(Venue $venue, string $moduleCode, int $quantity = 1): VenueModule
    {
        return DB::transaction(function () use ($venue, $moduleCode, $quantity) {
            $module = VenueModule::firstOrNew([
                'venue_id' => $venue->id,
                'module_code' => $moduleCode,
            ]);

            $module->status = ModuleStatus::Active;
            $module->quantity = $quantity;
            $module->started_at = now();
            $module->ended_at = null;
            $module->save();

            return $module;
        });
    }
}
