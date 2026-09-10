<?php

namespace Database\Factories\Orders;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestType;
use App\Models\Orders\ServiceRequest;
use App\Models\Tenant\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'type' => ServiceRequestType::Message,
            'message' => fake()->sentence(),
            'status' => ServiceRequestStatus::Pending,
        ];
    }

    public function callToOrder(): static
    {
        return $this->state(['type' => ServiceRequestType::CallToOrder, 'message' => null]);
    }

    public function checkout(): static
    {
        return $this->state(['type' => ServiceRequestType::Checkout, 'message' => 'Solicitou fechamento de conta']);
    }

    public function acknowledged(): static
    {
        return $this->state(['status' => ServiceRequestStatus::Acknowledged, 'acknowledged_at' => now()]);
    }

    public function resolved(): static
    {
        return $this->state(['status' => ServiceRequestStatus::Resolved, 'resolved_at' => now()]);
    }
}
