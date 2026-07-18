<?php

namespace App\Actions\Corporation;

use App\Enums\ModuleStatus;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use Illuminate\Support\Facades\DB;

class DeactivateVenueModuleAction
{
    public function execute(Venue $venue, string $moduleCode): void
    {
        DB::transaction(function () use ($venue, $moduleCode): void {
            VenueModule::query()
                ->where('venue_id', $venue->id)
                ->where('module_code', $moduleCode)
                ->update([
                    'status' => ModuleStatus::Inactive,
                    'ended_at' => now(),
                ]);
        });
    }
}
