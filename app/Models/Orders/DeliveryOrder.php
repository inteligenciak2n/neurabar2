<?php

namespace App\Models\Orders;

use App\Concerns\BelongsToVenue;
use App\Enums\FulfillmentType;
use App\Models\Concerns\HasOperationalConnection;
use App\Models\Settings\DeliveryFeeZone;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrder extends Model
{
    use BelongsToVenue;
    use HasFactory;
    use HasOperationalConnection;
    use HasUuids;

    protected $fillable = [
        'venue_id',
        'attendance_id',
        'fulfillment_type',
        'customer_id',
        'customer_address_id',
        'delivery_fee_zone_id',
        'delivery_fee',
        'customer_name',
        'customer_phone',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
        'address_zip_code',
        'address_reference_point',
    ];

    protected function casts(): array
    {
        return [
            'fulfillment_type' => FulfillmentType::class,
            'delivery_fee' => 'decimal:2',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function deliveryFeeZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryFeeZone::class);
    }
}
