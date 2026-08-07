<?php

namespace App\Actions\Corporation;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Jobs\Venue\CreateVenueDefaultsJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use App\Services\Billing\SubscriptionCalculator;
use App\Services\VenueModuleCache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateVenueAction
{
    public function __construct(
        private readonly SubscriptionCalculator $calculator,
    ) {}

    public function execute(Corporation $corporation, array $data): Venue
    {
        $corporationSubscription = $corporation->subscription;

        if (! $corporationSubscription) {
            throw new InvalidArgumentException('A corporation não possui uma assinatura ativa para criar venues.');
        }

        $plan = $corporationSubscription->planCatalog;

        // Adicional cobrado apenas de quem opera em infraestrutura dedicada.
        $dedicatedSurcharge = $corporation->is_dedicated
            ? (int) ($plan?->dedicated_surcharge ?? 0)
            : 0;

        $slug = Str::slug($data['name']).'-'.Str::lower(Str::random(6));

        $venue = Venue::create([
            ...$data,
            'corporation_id' => $corporation->id,
            'affiliate_code_id' => $data['affiliate_code_id'] ?? $corporation->affiliate_code_id,
            'call_waiter_slug' => $slug,
            'active' => true,
        ]);

        VenueSubscription::create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporationSubscription->id,
            'affiliate_code_id' => $venue->affiliate_code_id,
            'plan_catalog_id' => $plan?->id,
            'status' => $corporationSubscription->status,
            'base_value' => $plan?->monthly_price ?? 0,
            'dedicated_surcharge' => $dedicatedSurcharge,
            'total_value' => ($plan?->monthly_price ?? 0) + $dedicatedSurcharge,
            'started_at' => now(),
            'trial_ends_at' => $corporationSubscription->trial_ends_at,
        ]);

        // Dezenas de inserts (cardápio, mesas, estações) não precisam segurar
        // o request de criação da venue.
        CreateVenueDefaultsJob::dispatch($venue);

        $this->propagateCorporateModules($corporation, $venue);

        if ($corporation->owner_id) {
            $venue->users()->attach($corporation->owner_id, ['role' => UserRole::Owner->value]);
        }

        return $venue;
    }

    private function propagateCorporateModules(Corporation $corporation, Venue $venue): void
    {
        $now = now();
        $hasModules = false;

        foreach ($corporation->activeModules as $module) {
            VenueModule::firstOrCreate(
                [
                    'venue_id' => $venue->id,
                    'module_code' => $module->module_code,
                ],
                [
                    'status' => $module->status,
                    'quantity' => 1,
                    'started_at' => $now,
                ]
            );

            $hasModules = true;
        }

        // Garante o módulo base mesmo que a corporation ainda não tenha módulos ativos.
        $menuExists = VenueModule::where('venue_id', $venue->id)
            ->where('module_code', ModuleCode::Menu->value)
            ->exists();

        if (! $menuExists) {
            VenueModule::create([
                'venue_id' => $venue->id,
                'module_code' => ModuleCode::Menu->value,
                'status' => ModuleStatus::Active,
                'quantity' => 1,
                'started_at' => $now,
            ]);

            $hasModules = true;
        }

        if ($hasModules) {
            $this->calculator->refreshVenueSnapshot($venue, $now->format('Y-m'));
            VenueModuleCache::forget($venue);
        }
    }
}
