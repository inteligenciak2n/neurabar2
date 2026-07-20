<?php

namespace Database\Factories\Tenant;

use App\Enums\InvoiceStatus;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationInvoice;
use App\Models\Tenant\CorporationSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporationInvoice>
 */
class CorporationInvoiceFactory extends Factory
{
    protected $model = CorporationInvoice::class;

    public function definition(): array
    {
        $corporation = Corporation::factory()->create();

        return [
            'corporation_id' => $corporation->id,
            'corporation_subscription_id' => CorporationSubscription::factory()->create([
                'corporation_id' => $corporation->id,
            ])->id,
            'affiliate_code_id' => null,
            'period' => now()->format('Y-m'),
            'due_date' => now()->addDays(7),
            'status' => InvoiceStatus::Open,
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
