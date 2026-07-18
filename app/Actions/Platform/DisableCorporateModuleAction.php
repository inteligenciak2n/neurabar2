<?php

namespace App\Actions\Platform;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use Illuminate\Support\Facades\DB;

class DisableCorporateModuleAction
{
    public function execute(Corporation $corporation, string $moduleCode): void
    {
        DB::transaction(function () use ($corporation, $moduleCode): void {
            CorporationModule::query()
                ->where('corporation_id', $corporation->id)
                ->where('module_code', $moduleCode)
                ->update([
                    'status' => ModuleStatus::Inactive,
                    'ended_at' => now(),
                ]);
        });
    }
}
