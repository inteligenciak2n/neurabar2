<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\ExpireTrialsJob;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueSubscription;
use Carbon\Carbon;
use Tests\TestCase;

class ExpireTrialsJobTest extends TestCase
{
    public function test_it_expires_corporation_trial_when_due(): void
    {
        $subscription = CorporationSubscription::factory()->create([
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => Carbon::now()->subDay(),
        ]);

        (new ExpireTrialsJob)->handle();

        $this->assertDatabaseHas('corporation_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::PastDue->value,
        ]);
    }

    public function test_it_expires_venue_trial_when_due(): void
    {
        $subscription = VenueSubscription::factory()->create([
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => Carbon::now()->subDay(),
        ]);

        (new ExpireTrialsJob)->handle();

        $this->assertDatabaseHas('venue_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::PastDue->value,
        ]);
    }
}
