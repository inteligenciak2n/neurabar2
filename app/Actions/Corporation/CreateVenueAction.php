<?php

namespace App\Actions\Corporation;

use App\Enums\BillingMode;
use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueModule;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Support\Str;

class CreateVenueAction
{
    public function execute(Corporation $corporation, array $data): Venue
    {
        $slug = Str::slug($data['name']).'-'.Str::lower(Str::random(6));

        $venue = Venue::create([
            ...$data,
            'corporation_id' => $corporation->id,
            'call_waiter_slug' => $slug,
            'active' => true,
        ]);

        $corporationSubscription = $corporation->subscription;

        if (! $corporationSubscription) {
            $plan = PlanCatalog::firstOrCreate(
                ['code' => 'pro'],
                ['name' => 'Pro', 'monthly_price' => 99.90, 'active' => true, 'plan_type' => 'shared']
            );

            $corporationSubscription = CorporationSubscription::create([
                'corporation_id' => $corporation->id,
                'plan_catalog_id' => $plan->id,
                'billing_mode' => BillingMode::PerVenue,
                'status' => SubscriptionStatus::Trial,
                'billing_day' => config('billing.default_billing_day', 1),
                'grace_period_days' => config('billing.grace_period_days', 3),
                'started_at' => now(),
                'trial_ends_at' => now()->addDays(config('billing.trial_days', 14)),
                'currency' => config('billing.currency', 'BRL'),
            ]);
        }

        $plan = $corporationSubscription->planCatalog ?? PlanCatalog::firstOrCreate(
            ['code' => 'pro'],
            ['name' => 'Pro', 'monthly_price' => 99.90, 'active' => true, 'plan_type' => 'shared']
        );

        VenueSubscription::create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporationSubscription->id,
            'plan_catalog_id' => $plan->id,
            'status' => $corporationSubscription->status,
            'base_value' => $plan->monthly_price,
            'total_value' => $plan->monthly_price,
            'started_at' => now(),
            'trial_ends_at' => $corporationSubscription->trial_ends_at,
        ]);

        VenueModule::create([
            'venue_id' => $venue->id,
            'module_code' => ModuleCode::Menu->value,
            'status' => ModuleStatus::Active,
            'quantity' => 1,
            'started_at' => now(),
        ]);

        (new CreateVenueDefaultsAction)->execute($venue);

        if ($corporation->owner_id) {
            $venue->users()->attach($corporation->owner_id, ['role' => UserRole::Owner->value]);
        }

        return $venue;
    }
}
