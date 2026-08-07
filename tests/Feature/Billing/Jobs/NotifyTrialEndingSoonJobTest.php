<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\NotifyTrialEndingSoonJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Notifications\Billing\TrialEndingSoon;
use Illuminate\Support\Facades\Notification;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class NotifyTrialEndingSoonJobTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_notifies_owner_when_trial_ends_within_three_days(): void
    {
        Notification::fake();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(2),
        ]);

        (new NotifyTrialEndingSoonJob)->handle();

        Notification::assertSentTo($corporation->owner, TrialEndingSoon::class);
    }

    public function test_does_not_notify_when_trial_already_expired(): void
    {
        Notification::fake();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->subDay(),
        ]);

        (new NotifyTrialEndingSoonJob)->handle();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_when_trial_ends_after_window(): void
    {
        Notification::fake();

        $corporation = Corporation::factory()->create();
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(5),
        ]);

        (new NotifyTrialEndingSoonJob)->handle();

        Notification::assertNothingSent();
    }
}
