<?php

namespace Tests\Feature\Subscription;

use App\Actions\Subscription\ProcessWebhookPaymentAction;
use App\Enums\GatewayWebhookEventStatus;
use App\Jobs\Subscription\ProcessGatewayWebhookJob;
use App\Models\Tenant\GatewayWebhookEvent;
use Illuminate\Support\Facades\Queue;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ProcessWebhookPaymentActionTest extends TestCase
{
    use RefreshAllDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        config(['subscription.payment.webhook_token' => 'test-token']);
    }

    public function test_dispatches_job_for_new_event(): void
    {
        Queue::fake();

        app(ProcessWebhookPaymentAction::class)->execute('fake', 'test-token', ['id' => 'evt_1']);

        Queue::assertPushedOn('payments', ProcessGatewayWebhookJob::class);
        $this->assertDatabaseHas('gateway_webhook_events', [
            'event_id' => 'evt_1',
            'status' => 'pending',
        ]);
    }

    public function test_does_not_redispatch_already_processed_event(): void
    {
        GatewayWebhookEvent::factory()->create([
            'gateway' => 'fake',
            'event_id' => 'evt_2',
            'status' => GatewayWebhookEventStatus::Processed,
        ]);

        Queue::fake();

        app(ProcessWebhookPaymentAction::class)->execute('fake', 'test-token', ['id' => 'evt_2']);

        Queue::assertNothingPushed();
    }

    public function test_redispatches_failed_event(): void
    {
        GatewayWebhookEvent::factory()->create([
            'gateway' => 'fake',
            'event_id' => 'evt_3',
            'status' => GatewayWebhookEventStatus::Failed,
            'error' => 'boom',
        ]);

        Queue::fake();

        app(ProcessWebhookPaymentAction::class)->execute('fake', 'test-token', ['id' => 'evt_3']);

        Queue::assertPushedOn('payments', ProcessGatewayWebhookJob::class);
    }

    public function test_redispatches_pending_event(): void
    {
        GatewayWebhookEvent::factory()->create([
            'gateway' => 'fake',
            'event_id' => 'evt_4',
            'status' => GatewayWebhookEventStatus::Pending,
        ]);

        Queue::fake();

        app(ProcessWebhookPaymentAction::class)->execute('fake', 'test-token', ['id' => 'evt_4']);

        Queue::assertPushedOn('payments', ProcessGatewayWebhookJob::class);
    }
}
