<?php

namespace Database\Factories\Tenant;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueSubscription>
 */
class VenueSubscriptionFactory extends Factory
{
    protected $model = VenueSubscription::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'corporation_subscription_id' => CorporationSubscription::factory(),
            'plan_catalog_id' => null,
            'affiliate_code_id' => null,
            'status' => SubscriptionStatus::Trial,
            'base_value' => 0,
            'modules_value' => 0,
            'metered_value' => 0,
            'dedicated_surcharge' => 0,
            'total_value' => 0,
            'started_at' => now(),
            'ended_at' => null,
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
