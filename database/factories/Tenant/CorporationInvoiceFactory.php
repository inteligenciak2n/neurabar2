<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporationInvoice>
 */
class CorporationInvoiceFactory extends Factory
{
    protected $model = CorporationInvoice::class;

    public function definition(): array
    {
        return [
            'corporation_id' => Corporation::factory(),
            'corporation_subscription_id' => null,
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
