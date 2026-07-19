<?php

namespace App\Actions\Corporation;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\VenueModuleCache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeactivateVenueModuleAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    public function execute(Venue $venue, string $moduleCode): void
    {
        $code = ModuleCode::tryFrom($moduleCode);

        if (! $code) {
            throw new InvalidArgumentException("Invalid module code: {$moduleCode}");
        }

        DB::transaction(function () use ($venue, $code): void {
            VenueModule::query()
                ->where('venue_id', $venue->id)
                ->where('module_code', $code->value)
                ->update([
                    'status' => ModuleStatus::Inactive,
                    'ended_at' => now(),
                ]);

            $this->calculator->calculateVenue($venue, now()->format('Y-m'));
            VenueModuleCache::forget($venue);
        });
    }
}
