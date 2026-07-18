<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueInvoice>
 */
class VenueInvoiceFactory extends Factory
{
    protected $model = VenueInvoice::class;

    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'venue_subscription_id' => null,
            'corporation_invoice_id' => null,
            'affiliate_code_id' => null,
            'period' => now()->format('Y-m'),
            'due_date' => now()->addDays(7),
            'status' => 'open',
            'is_finalized' => false,
            'base_value' => 0,
            'modules_value' => 0,
            'metered_value' => 0,
            'dedicated_surcharge' => 0,
            'discount_value' => 0,
            'total_value' => 0,
        ];
    }
}
