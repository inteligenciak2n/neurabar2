<?php

namespace App\Actions\Subscription;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Services\Billing\BillingStatusService;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\VenueModuleCache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscribeModuleAction
{
    public function __construct(private readonly SubscriptionCalculator $calculator) {}

    public function execute(Venue $venue, string $moduleCode, int $quantity = 1): VenueModule
    {
        $code = ModuleCode::tryFrom($moduleCode);

        if (! $code) {
            throw new InvalidArgumentException("Invalid module code: {$moduleCode}");
        }

        if (BillingStatusService::isBlocked($venue)) {
            throw new InvalidArgumentException('Acesso suspenso por questões de faturamento.');
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
            $module->quantity = max(1, $quantity);
            $module->started_at ??= now();
            $module->ended_at = null;
            $module->save();

            $this->calculator->calculateVenue($venue, now()->format('Y-m'));
            VenueModuleCache::forget($venue);
            BillingStatusService::flushBlockedCache($venue);

            return $module;
        });
    }
}
