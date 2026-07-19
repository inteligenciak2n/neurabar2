<?php

namespace Tests\Feature\Billing\Jobs;

use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\ExpireTrialsJob;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use App\Notifications\Billing\TrialExpired;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpireTrialsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:migrate-all --fresh --force');
    }

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

    public function test_it_notifies_owner_when_corporation_trial_expires(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $corporation = Corporation::factory()->create(['owner_id' => $owner->id]);
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => Carbon::now()->subDay(),
        ]);

        (new ExpireTrialsJob)->handle();

        Notification::assertSentTo($owner, TrialExpired::class);
    }
}
