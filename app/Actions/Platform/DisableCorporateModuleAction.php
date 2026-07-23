<?php

namespace App\Actions\Platform;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationModule;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\CorporationModuleCache;
use App\Services\VenueModuleCache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DisableCorporateModuleAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    public function execute(Corporation $corporation, string $moduleCode): void
    {
        $code = ModuleCode::tryFrom($moduleCode);

        if (! $code) {
            throw new InvalidArgumentException("Invalid module code: {$moduleCode}");
        }

        DB::transaction(function () use ($corporation, $code): void {
            CorporationModule::query()
                ->where('corporation_id', $corporation->id)
                ->where('module_code', $code->value)
                ->update([
                    'status' => ModuleStatus::Inactive,
                    'ended_at' => now(),
                ]);

            $this->calculator->calculateCorporation($corporation, now()->format('Y-m'));
            CorporationModuleCache::forget($corporation);

            foreach ($corporation->venues as $venue) {
                VenueModuleCache::forget($venue);
            }
        });
    }
}
