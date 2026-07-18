<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\SuspendOverdueSubscriptionsJob;
use App\Models\Tenant\CorporationSubscription;
use Carbon\Carbon;
use Tests\TestCase;

class SuspendOverdueSubscriptionsJobTest extends TestCase
{
    public function test_it_suspends_corporation_past_due_after_grace_period(): void
    {
        $subscription = CorporationSubscription::factory()->create([
            'status' => SubscriptionStatus::PastDue,
            'trial_ends_at' => Carbon::now()->subDays(10),
            'grace_period_days' => 3,
        ]);

        (new SuspendOverdueSubscriptionsJob)->handle();

        $this->assertDatabaseHas('corporation_subscriptions', [
            'id' => $subscription->id,
            'status' => SubscriptionStatus::Suspended->value,
        ]);
    }
}
