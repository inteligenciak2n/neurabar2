<?php

namespace Tests\Feature\Billing\Notifications;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\User;
use App\Notifications\Billing\TrialEndingSoon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrialEndingSoonNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:migrate-all --fresh --force');
    }

    public function test_notification_sent_when_trial_ends_soon(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $corporation = Corporation::factory()->create(['owner_id' => $owner->id]);
        CorporationSubscription::factory()->create([
            'corporation_id' => $corporation->id,
            'status' => SubscriptionStatus::Trial,
            'trial_ends_at' => now()->addDays(2),
        ]);

        $owner->notify(new TrialEndingSoon($corporation));

        Notification::assertSentTo($owner, TrialEndingSoon::class);
    }
}
