<?php

namespace Database\Factories\Tenant;

use App\Enums\GatewayWebhookEventStatus;
use App\Models\Tenant\GatewayWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GatewayWebhookEvent>
 */
class GatewayWebhookEventFactory extends Factory
{
    protected $model = GatewayWebhookEvent::class;

    public function definition(): array
    {
        return [
            'gateway' => 'fake',
            'event_id' => 'evt_'.fake()->uuid(),
            'event_type' => 'payment.updated',
            'payload' => [],
            'status' => GatewayWebhookEventStatus::Pending,
            'received_at' => now(),
            'processed_at' => null,
            'error' => null,
        ];
    }
}
