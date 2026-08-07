<?php

namespace Database\Factories\Tenant;

use App\Enums\BillingMode;
use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporationSubscription>
 */
class CorporationSubscriptionFactory extends Factory
{
    protected $model = CorporationSubscription::class;

    public function definition(): array
    {
        return [
            'corporation_id' => Corporation::factory(),
            'plan_catalog_id' => null,
            'affiliate_code_id' => null,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Trial,
            'billing_day' => 1,
            'grace_period_days' => 3,
            'started_at' => now(),
            'ended_at' => null,
            'trial_ends_at' => now()->addDays(14),
            'currency' => 'BRL',
        ];
    }
}
